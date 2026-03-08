"""
Visual Search router.

Accepts an uploaded image and returns a ranked list of product IDs
based on color histogram similarity (cosine similarity).

Each product's embedding is a normalized 192-dim RGB color histogram
(3 channels × 64 bins), stored in the product_embeddings table.

Endpoints:
  POST /api/visual-search          — search by image
  POST /api/embeddings/generate    — compute and store an embedding
                                     (called by `php artisan embeddings:generate`)
"""

import io
import json
import os

import numpy as np
import pymysql
from fastapi import APIRouter, File, UploadFile
from PIL import Image
from pydantic import BaseModel
from sklearn.metrics.pairwise import cosine_similarity

router = APIRouter()

# In-memory embedding cache: { product_id: np.ndarray }
_embeddings: dict[int, np.ndarray] = {}


# --------------------------------------------------------------------------- #
# Helpers                                                                      #
# --------------------------------------------------------------------------- #

def _extract_embedding(image_bytes: bytes) -> np.ndarray:
    """Return a normalized 192-dim RGB color histogram vector."""
    img = Image.open(io.BytesIO(image_bytes)).convert("RGB").resize((224, 224))
    arr = np.array(img)
    hists = [
        np.histogram(arr[:, :, ch], bins=64, range=(0, 256))[0]
        for ch in range(3)
    ]
    vec = np.concatenate(hists).astype(np.float32)
    norm = np.linalg.norm(vec)
    return vec / norm if norm > 0 else vec


def _db_connect() -> pymysql.Connection:
    return pymysql.connect(
        host=os.getenv("DB_HOST", "127.0.0.1"),
        port=int(os.getenv("DB_PORT", 3306)),
        user=os.getenv("DB_USERNAME", "root"),
        password=os.getenv("DB_PASSWORD", ""),
        database=os.getenv("DB_DATABASE", "smartshop"),
        charset="utf8mb4",
    )


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
# Endpoints                                                                    #
# --------------------------------------------------------------------------- #

class SearchResult(BaseModel):
    products: list[dict]


class GenerateResult(BaseModel):
    success: bool
    product_id: int


@router.post("/visual-search", response_model=SearchResult)
async def visual_search(image: UploadFile = File(...)):
    """Return top-10 products most similar to the uploaded image."""
    if not _embeddings:
        return {"products": []}

    contents = await image.read()
    query_vec = _extract_embedding(contents)

    ids = list(_embeddings.keys())
    matrix = np.stack([_embeddings[pid] for pid in ids])
    scores = cosine_similarity([query_vec], matrix)[0]
    top_indices = np.argsort(scores)[::-1][:10]

    return {
        "products": [
            {"id": ids[i], "similarity_score": round(float(scores[i]), 4)}
            for i in top_indices
        ]
    }


@router.post("/embeddings/generate", response_model=GenerateResult)
async def generate_embedding(product_id: int, image: UploadFile = File(...)):
    """Compute and persist a color histogram embedding for a product image.

    product_id is passed as a query parameter.
    Called by `php artisan embeddings:generate` after seeding.
    Refreshes the in-memory cache automatically.
    """
    contents = await image.read()
    embedding = _extract_embedding(contents).tolist()

    conn = _db_connect()
    with conn.cursor() as cur:
        cur.execute(
            """
            INSERT INTO product_embeddings (product_id, embedding, created_at, updated_at)
            VALUES (%s, %s, NOW(), NOW())
            ON DUPLICATE KEY UPDATE embedding = VALUES(embedding), updated_at = NOW()
            """,
            (product_id, json.dumps(embedding)),
        )
    conn.commit()
    conn.close()

    _embeddings[product_id] = np.array(embedding, dtype=np.float32)
    return {"success": True, "product_id": product_id}
