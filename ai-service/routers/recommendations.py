"""
Recommendations router.

Two endpoints:

  GET /api/recommendations/similar?product_id=X&limit=8
    Returns products visually similar to a given product using cosine similarity
    on the in-memory color histogram embedding cache (loaded at startup).

  GET /api/recommendations/personal?user_id=Y&limit=8
    Returns products personalised to a user based on their purchase history.
    Queries order_items to build a taste profile (mean of purchased embeddings),
    then finds similar products they haven't bought yet.
    Falls back to an empty list when history or embeddings are unavailable.
"""

import numpy as np
from fastapi import APIRouter
from pydantic import BaseModel
from sklearn.metrics.pairwise import cosine_similarity

from routers import visual_search  # access _embeddings dynamically

router = APIRouter()


class RecommendationResult(BaseModel):
    recommended_products: list[dict]


# --------------------------------------------------------------------------- #
# Endpoints                                                                    #
# --------------------------------------------------------------------------- #

@router.get("/recommendations/similar", response_model=RecommendationResult)
def get_similar(product_id: int, limit: int = 8):
    """Products visually similar to a given product (embedding cosine similarity)."""
    embeddings = visual_search._embeddings
    if not embeddings or product_id not in embeddings:
        return {"recommended_products": []}

    candidate_ids = [pid for pid in embeddings if pid != product_id]
    if not candidate_ids:
        return {"recommended_products": []}

    query_vec = embeddings[product_id]
    matrix    = np.stack([embeddings[pid] for pid in candidate_ids])
    scores    = cosine_similarity([query_vec], matrix)[0]
    top_idxs  = np.argsort(scores)[::-1][:limit]

    return {
        "recommended_products": [
            {"id": candidate_ids[i], "score": round(float(scores[i]), 4)}
            for i in top_idxs
        ]
    }


@router.get("/recommendations/personal", response_model=RecommendationResult)
def get_personal(user_id: int, limit: int = 8):
    """Products personalised to a user's purchase taste profile."""
    embeddings = visual_search._embeddings
    if not embeddings:
        return {"recommended_products": []}

    purchased_ids = _get_purchased_ids(user_id)
    if not purchased_ids:
        return {"recommended_products": []}

    # Build taste profile: mean embedding of purchased products
    purchased_vecs = [embeddings[pid] for pid in purchased_ids if pid in embeddings]
    if not purchased_vecs:
        return {"recommended_products": []}

    taste = np.mean(purchased_vecs, axis=0)
    norm  = np.linalg.norm(taste)
    if norm > 0:
        taste /= norm

    # Rank products the user hasn't purchased yet
    candidate_ids = [pid for pid in embeddings if pid not in set(purchased_ids)]
    if not candidate_ids:
        return {"recommended_products": []}

    matrix   = np.stack([embeddings[pid] for pid in candidate_ids])
    scores   = cosine_similarity([taste], matrix)[0]
    top_idxs = np.argsort(scores)[::-1][:limit]

    return {
        "recommended_products": [
            {"id": candidate_ids[i], "score": round(float(scores[i]), 4)}
            for i in top_idxs
        ]
    }


# --------------------------------------------------------------------------- #
# Helpers                                                                      #
# --------------------------------------------------------------------------- #

def _get_purchased_ids(user_id: int) -> list[int]:
    """Return distinct product IDs from the user's order history."""
    try:
        conn = visual_search._db_connect()
        with conn.cursor() as cur:
            cur.execute(
                """
                SELECT DISTINCT oi.product_id
                FROM order_items oi
                JOIN orders o ON o.id = oi.order_id
                WHERE o.user_id = %s AND o.deleted_at IS NULL
                """,
                (user_id,),
            )
            rows = cur.fetchall()
        conn.close()
        return [row[0] for row in rows]
    except Exception as exc:
        print(f"[recommendations] DB query failed: {exc}")
        return []
