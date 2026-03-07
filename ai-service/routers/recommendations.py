"""
Recommendations router.

Returns product IDs recommended for a given product (and optionally a user).

Production implementation should:
  - Use collaborative filtering on order history
  - Or use embedding similarity on product_embeddings table
  - Personalise with user_id when provided

This demo uses a seeded shuffle of the catalogue (excluding the source
product) to always return consistent results without requiring a model.
"""

import random

from fastapi import APIRouter
from pydantic import BaseModel

router = APIRouter()

_PRODUCT_IDS = list(range(1, 17))


class RecommendationResult(BaseModel):
    recommended_products: list[dict]


@router.get("/recommendations", response_model=RecommendationResult)
def get_recommendations(
    product_id: int,
    user_id: int | None = None,
    limit: int = 8,
):
    pool = [pid for pid in _PRODUCT_IDS if pid != product_id]
    rng = random.Random(product_id)
    rng.shuffle(pool)
    top = pool[:min(limit, len(pool))]

    return {
        "recommended_products": [
            {"id": pid, "score": round(0.95 - i * 0.05, 2)}
            for i, pid in enumerate(top)
        ]
    }
