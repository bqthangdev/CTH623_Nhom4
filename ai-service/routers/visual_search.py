"""
Visual Search router.

Accepts an uploaded image and returns a ranked list of product IDs.

Production implementation should:
  - Load a feature extractor (e.g. CLIP / ResNet) once at startup
  - Compare the query image embedding against stored product embeddings
  - Return top-K matches sorted by cosine similarity

This demo returns a deterministic shuffled list based on the image file size
so the endpoint always responds quickly without requiring a GPU / heavy model.
"""

import hashlib
import random

from fastapi import APIRouter, File, UploadFile
from pydantic import BaseModel

router = APIRouter()

# --------------------------------------------------------------------------- #
# Simulated product catalogue — replace with DB query in production           #
# --------------------------------------------------------------------------- #
_PRODUCT_IDS = list(range(1, 17))  # matches ProductSeeder: 16 products


class SearchResult(BaseModel):
    products: list[dict]


@router.post("/visual-search", response_model=SearchResult)
async def visual_search(image: UploadFile = File(...)):
    contents = await image.read()

    # Deterministic but "unique" ordering per image
    seed = int(hashlib.md5(contents).hexdigest(), 16) % (2**32)
    rng = random.Random(seed)
    ranked = _PRODUCT_IDS.copy()
    rng.shuffle(ranked)
    top_k = ranked[:10]

    return {
        "products": [
            {"id": pid, "similarity_score": round(1.0 - i * 0.07, 2)}
            for i, pid in enumerate(top_k)
        ]
    }
