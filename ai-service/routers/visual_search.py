"""
Visual Search router.

Uses OpenAI CLIP (ViT-B/32) for semantic image understanding:

  - Each product embedding is the L2-normalised 512-dim vision feature
    returned by CLIP's image encoder, averaged over all product images.
  - Query images are encoded the same way at search time.
  - Zero-shot classification with CLIP's text encoder identifies the object
    category in the uploaded image and returns it as `detected_object`.

Endpoints:
  POST /api/visual-search          — search by image
  POST /api/embeddings/compute     — compute embedding + detected_object (no DB write)
  POST /api/embeddings/store       — store a pre-computed embedding vector

Environment variables:
  VISUAL_SEARCH_THRESHOLD  — minimum cosine similarity to include a result
                             (default: 0.60)
  CLIP_MODEL               — open_clip model name (default: ViT-B-32)
"""

import io
import json
import os
from typing import Optional

# Suppress the HuggingFace Hub unauthenticated-request noise that open_clip
# triggers during model loading.  "error" means only actual errors are logged.
os.environ.setdefault("HF_HUB_VERBOSITY", "error")

import numpy as np
import pymysql
from fastapi import APIRouter, File, UploadFile
from PIL import Image
from pydantic import BaseModel
from sklearn.metrics.pairwise import cosine_similarity

# CLIP must be installed for visual search to work.
try:
    import open_clip
    import torch
    _CLIP_AVAILABLE = True
except ImportError:
    _CLIP_AVAILABLE = False

router = APIRouter()

SIMILARITY_THRESHOLD: float = float(os.getenv("VISUAL_SEARCH_THRESHOLD", "0.60"))
CLIP_MODEL_NAME: str = os.getenv("CLIP_MODEL", "ViT-B-32")

# In-memory embedding cache: { product_id: np.ndarray }
_embeddings: dict[int, np.ndarray] = {}

# CLIP components (populated by load_clip_model())
_clip_model = None
_clip_preprocess = None
_clip_tokenizer = None
_clip_embedding_dim: int = 512  # updated at startup; 512 for ViT-B/32, 768 for ViT-L/14

# Pre-computed CLIP text embeddings for zero-shot classification
# Parallel lists: _text_labels[i] ↔ _text_embeddings[i]
_text_embeddings: Optional["torch.Tensor"] = None
_text_labels: list[str] = []

# Vietnamese category names → descriptive English prompts understood by CLIP.
# CLIP was trained on English captions, so descriptive prompts yield better
# zero-shot accuracy than raw Vietnamese names.
_CATEGORY_PROMPTS: dict[str, str] = {
    "Thời trang":  "a photo of fashion clothing or a garment, such as a shirt, dress, jacket, sock, pants, skirt, coat, swimwear, hat, scarf, gloves or belt on a hanger or folded",
    "Điện tử":     "a photo of a general electronic device such as a remote control, calculator, digital camera, smartwatch, USB drive, power bank or charging cable",
    "Gia dụng":    "a photo of a household appliance such as a doormat, fan, table lamp, rice cooker, thermos flask, blender, vacuum cleaner, air purifier, kettle, iron or storage box",
    "Sách":        "a photo of a book, paperback, hardcover book, textbook, novel or open book with text pages",
    "Thể thao":    "a photo of sports equipment or activewear such as a dumbbell, yoga mat, bicycle, football, basketball, running shoes, gym bag or sports jersey",
    "Làm đẹp":     "a photo of a beauty or cosmetics product such as lipstick, face cream, shampoo, perfume bottle, nail polish, skincare serum or makeup palette",
    "Điện thoại":  "a photo of a smartphone or mobile phone held in hand or on a surface, brands include iPhone, Samsung Galaxy, Google Pixel, Xiaomi, OPPO or Vivo",
    "Laptop":      "a photo of a laptop computer open showing keyboard and screen, or closed showing the lid, brands include MacBook, Dell, HP, Lenovo ThinkPad or Asus",
    "Tai nghe":    "a photo of headphones or earphones, over-ear headphones, in-ear earbuds, wired or wireless, brands include AirPods, Sony, JBL, Bose or Sennheiser",
    "Loa":         "a photo of a speaker or audio device, portable Bluetooth speaker, bookshelf speaker, cylindrical or box-shaped, brands include JBL, Sony, Bose, Marshall or Harman",
}


# --------------------------------------------------------------------------- #
# Startup loaders                                                              #
# --------------------------------------------------------------------------- #

def load_clip_model() -> None:
    """Load CLIP vision encoder, preprocessor, and tokenizer into memory.

    Called once at application startup. The first call downloads the model
    weights (~350 MB for ViT-B/32) to the system cache if not already present.
    """
    global _clip_model, _clip_preprocess, _clip_tokenizer, _clip_embedding_dim
    if not _CLIP_AVAILABLE:
        print("[visual_search] open-clip-torch not installed — visual search is unavailable.")
        return
    try:
        _clip_model, _, _clip_preprocess = open_clip.create_model_and_transforms(
            CLIP_MODEL_NAME, pretrained="openai", force_quick_gelu=True
        )
        _clip_model.eval()
        _clip_tokenizer = open_clip.get_tokenizer(CLIP_MODEL_NAME)
        # Detect actual output dimension (512 for ViT-B/32, 768 for ViT-L/14, etc.)
        _dummy = _clip_preprocess(Image.new("RGB", (224, 224))).unsqueeze(0)
        with torch.no_grad():
            _clip_embedding_dim = int(_clip_model.encode_image(_dummy).shape[1])
        print(f"[visual_search] CLIP model '{CLIP_MODEL_NAME}' loaded ({_clip_embedding_dim}-dim).")
    except Exception as exc:
        print(f"[visual_search] Failed to load CLIP model: {exc}")
        _clip_model = None


def load_category_labels() -> None:
    """Fetch category names from DB and pre-compute CLIP text embeddings.

    These are used for zero-shot image classification: when a user uploads
    an image the router compares the image embedding against all category
    text embeddings and returns the best-matching category name as
    `detected_object` in the search response.
    """
    global _text_embeddings, _text_labels
    if _clip_model is None or _clip_tokenizer is None:
        return
    try:
        conn = _db_connect()
        with conn.cursor() as cur:
            cur.execute("SELECT name FROM categories WHERE is_active = 1")
            rows = cur.fetchall()
        conn.close()

        names = [row[0] for row in rows]
        if not names:
            return

        # Use descriptive English prompts when available so CLIP understands
        # the label semantics. Unknown names fall back to "a photo of {name}".
        prompts = [
            _CATEGORY_PROMPTS.get(name, f"a photo of {name}")
            for name in names
        ]
        tokens = _clip_tokenizer(prompts)

        with torch.no_grad():
            text_feats = _clip_model.encode_text(tokens)   # (n, 512)
            text_feats = text_feats / text_feats.norm(dim=-1, keepdim=True)

        _text_embeddings = text_feats
        _text_labels = names
        print(f"[visual_search] Loaded {len(names)} category labels for zero-shot classification.")
    except Exception as exc:
        print(f"[visual_search] Could not load category labels: {exc}")


def load_embeddings() -> None:
    """Load all product embeddings from DB into the in-memory cache."""
    global _embeddings
    try:
        conn = _db_connect()
        with conn.cursor() as cur:
            cur.execute("SELECT product_id, embedding FROM product_embeddings")
            rows = cur.fetchall()
        conn.close()
        _embeddings = {
            row[0]: np.array(json.loads(row[1]), dtype=np.float32)
            for row in rows
        }
        print(f"[visual_search] Loaded {len(_embeddings)} product embeddings.")
    except Exception as exc:
        print(f"[visual_search] Could not load embeddings from DB: {exc}")
        _embeddings = {}


# --------------------------------------------------------------------------- #
# Embedding helpers                                                            #
# --------------------------------------------------------------------------- #

def _extract_embedding(image_bytes: bytes) -> np.ndarray:
    """Encode the image with CLIP and return the L2-normalised embedding vector.

    Raises RuntimeError when the CLIP model is not loaded.
    """
    if _clip_model is None or _clip_preprocess is None:
        raise RuntimeError("CLIP model is not loaded.")
    img = Image.open(io.BytesIO(image_bytes)).convert("RGB")
    tensor = _clip_preprocess(img).unsqueeze(0)   # (1, 3, 224, 224)
    with torch.no_grad():
        features = _clip_model.encode_image(tensor)           # (1, 512)
        features = features / features.norm(dim=-1, keepdim=True)
    return features[0].cpu().numpy().astype(np.float32)


def _detect_object(image_vec: np.ndarray) -> Optional[str]:
    """Return the most likely product category using CLIP zero-shot classification.

    Compares the image embedding against pre-computed CLIP text embeddings for
    each product category via softmax over dot-product similarities.  Returns
    None when CLIP is unavailable or when no single category achieves at least
    15 % probability (uniform random chance with n categories = 1/n).
    """
    if _text_embeddings is None or not _text_labels:
        return None

    image_tensor = torch.from_numpy(image_vec).unsqueeze(0)   # (1, 512)
    similarity = (image_tensor @ _text_embeddings.T).squeeze()  # (n_labels,)
    probs = similarity.softmax(dim=-1)

    best_idx = int(probs.argmax())
    best_prob = float(probs[best_idx])

    # Confidence gate: must beat uniform random baseline by a meaningful margin
    random_baseline = 1.0 / len(_text_labels)
    if best_prob < max(0.15, random_baseline * 1.5):
        return None

    return _text_labels[best_idx]


def _active_embedding_method() -> str:
    """Return a human-readable label for the embedding method currently in use."""
    if _clip_model is not None:
        return f"CLIP {CLIP_MODEL_NAME} ({_clip_embedding_dim}-dim)"
    return "unavailable"


def _db_connect() -> pymysql.Connection:
    return pymysql.connect(
        host=os.getenv("DB_HOST", "127.0.0.1"),
        port=int(os.getenv("DB_PORT", 3306)),
        user=os.getenv("DB_USERNAME", "root"),
        password=os.getenv("DB_PASSWORD", ""),
        database=os.getenv("DB_DATABASE", "smartshop"),
        charset="utf8mb4",
    )


# --------------------------------------------------------------------------- #
# Pydantic models                                                              #
# --------------------------------------------------------------------------- #

class SearchResult(BaseModel):
    products: list[dict]
    detected_object: Optional[str] = None
    embedding_method: str = "unknown"


class ComputeResult(BaseModel):
    embedding: list[float]
    dimension: int
    detected_object: Optional[str] = None
    embedding_method: str = "unknown"


class StoreEmbeddingRequest(BaseModel):
    product_id: int
    embedding: list[float]


class GenerateResult(BaseModel):
    success: bool
    product_id: int


# --------------------------------------------------------------------------- #
# Endpoints                                                                    #
# --------------------------------------------------------------------------- #

@router.post("/visual-search", response_model=SearchResult)
async def visual_search(image: UploadFile = File(...)):
    """Return products visually similar to the uploaded image.

    Uses CLIP semantic embeddings (512-dim).

    Response fields:
      detected_object — recognized product category name (null if unavailable)
      products        — up to 10 products above SIMILARITY_THRESHOLD, ranked
                        by cosine similarity score descending
    """
    if not _embeddings:
        return {"products": [], "detected_object": None}

    contents = await image.read()
    try:
        query_vec = _extract_embedding(contents)
    except RuntimeError:
        return {"products": [], "detected_object": None, "embedding_method": "unavailable"}
    expected_dim = query_vec.shape[0]

    # Skip cached embeddings with a different dimension (stale after re-generate)
    ids = [pid for pid in _embeddings if _embeddings[pid].shape[0] == expected_dim]
    if not ids:
        return {"products": [], "detected_object": None}

    matrix = np.stack([_embeddings[pid] for pid in ids])
    scores = cosine_similarity([query_vec], matrix)[0]

    top_indices = np.argsort(scores)[::-1][:10]
    products = [
        {"id": ids[i], "similarity_score": round(float(scores[i]), 4)}
        for i in top_indices
        if scores[i] >= SIMILARITY_THRESHOLD
    ]

    return {
        "products":         products,
        "detected_object":  _detect_object(query_vec),
        "embedding_method": _active_embedding_method(),
    }


@router.post("/embeddings/compute", response_model=ComputeResult)
async def compute_embedding(image: UploadFile = File(...)):
    """Compute and return the embedding vector without storing it.

    Also classifies the image and returns `detected_object`.
    Used by `php artisan embeddings:generate` before averaging per-image vectors.
    """
    contents = await image.read()
    vec = _extract_embedding(contents)
    return {
        "embedding":        vec.tolist(),
        "dimension":        int(vec.shape[0]),
        "detected_object":  _detect_object(vec),
        "embedding_method": _active_embedding_method(),
    }


@router.post("/embeddings/store", response_model=GenerateResult)
async def store_embedding(payload: StoreEmbeddingRequest):
    """Persist a pre-computed embedding for a product and refresh the cache.

    Accepts JSON body: {"product_id": int, "embedding": [float, ...]}.
    Called by `php artisan embeddings:generate` after averaging per-image vectors.
    """
    conn = _db_connect()
    with conn.cursor() as cur:
        cur.execute(
            """
            INSERT INTO product_embeddings (product_id, embedding, created_at, updated_at)
            VALUES (%s, %s, NOW(), NOW())
            ON DUPLICATE KEY UPDATE embedding = VALUES(embedding), updated_at = NOW()
            """,
            (payload.product_id, json.dumps(payload.embedding)),
        )
    conn.commit()
    conn.close()

    _embeddings[payload.product_id] = np.array(payload.embedding, dtype=np.float32)
    return {"success": True, "product_id": payload.product_id}




