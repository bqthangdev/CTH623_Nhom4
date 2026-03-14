"""
Recommendations router.

Accuracy improvements vs. baseline:
  - Score threshold    : only products with cosine similarity >= RECOMMENDATION_THRESHOLD
                         (env RECOMMENDATION_THRESHOLD, default 0.40) are returned.
  - Recency weighting  : personal taste profile weights recent purchases more heavily
                         via exponential decay (half-life RECENCY_HALF_LIFE_DAYS, default 30).
  - Category diversity : at most MAX_PER_CATEGORY (default 2) products per category
                         to avoid homogeneous result sets.
  - Popularity blending: sparse-history cold-start is mitigated by blending in a
                         site-wide popularity signal (weight decreases as history grows).
  - CF blending        : item-item collaborative filtering (co-purchase signal) is
                         blended with CLIP similarity. CF weight grows proportionally
                         with history size, reaching CF_BLEND_WEIGHT (env, default 0.35)
                         at >= SPARSE_HISTORY_THRESHOLD purchases.
  - Encoder tracking   : embedding_method field in every response mirrors Visual Search.

Three endpoints:

  GET /api/recommendations/similar?product_id=X&limit=8
    Returns products visually similar to a given product via CLIP cosine similarity,
    filtered by threshold and diversified across categories.

  GET /api/recommendations/personal?user_id=Y&limit=8
    Returns products personalised to a user. Blends three signals proportionally:
      - CLIP cosine similarity     — content-based semantic signal
      - Item-item CF (co-purchase) — behaviour-based signal (what similar users buy)
      - Site-wide popularity       — cold-start fallback for sparse history
    Blending weights (ratio = min(1, history_size / SPARSE_HISTORY_THRESHOLD)):
      clip_w = 1 - cf_w - pop_w
      cf_w   = ratio * CF_BLEND_WEIGHT   (0 → 0.35 as history grows)
      pop_w  = (1 - ratio) * 0.30        (0.30 → 0 as history grows)

  GET /api/recommendations/collaborative?user_id=Y&limit=8
    Pure item-item collaborative filtering based on co-purchase frequency.
    Returns products frequently bought with items the user already owns.
    Falls back to popularity ranking when no co-purchase data is available.
    Does not require CLIP embeddings.
"""

import os
import numpy as np
from datetime import datetime, timezone
from fastapi import APIRouter
from pydantic import BaseModel
from sklearn.metrics.pairwise import cosine_similarity

from routers import visual_search  # access _embeddings, _active_embedding_method, _db_connect

router = APIRouter()

_RECOMMENDATION_THRESHOLD: float = float(os.getenv("RECOMMENDATION_THRESHOLD", "0.40"))
_MAX_PER_CATEGORY: int            = 2
_RECENCY_HALF_LIFE_DAYS: float    = 30.0  # exponential decay half-life for taste profile
_SPARSE_HISTORY_THRESHOLD: int    = 3     # blend with popularity when history < this
_CF_BLEND_WEIGHT: float           = float(os.getenv("CF_BLEND_WEIGHT", "0.35"))  # max CF signal weight


class RecommendationResult(BaseModel):
    recommended_products: list[dict]
    embedding_method: str = "unknown"


# --------------------------------------------------------------------------- #
# Endpoints                                                                    #
# --------------------------------------------------------------------------- #

@router.get("/recommendations/similar", response_model=RecommendationResult)
def get_similar(product_id: int, limit: int = 8):
    """Products visually similar to a given product (CLIP cosine similarity)."""
    embeddings = visual_search._embeddings
    method     = visual_search._active_embedding_method()

    if not embeddings or product_id not in embeddings:
        return {"recommended_products": [], "embedding_method": method}

    candidate_ids = [pid for pid in embeddings if pid != product_id]
    if not candidate_ids:
        return {"recommended_products": [], "embedding_method": method}

    query_vec = embeddings[product_id]
    matrix    = np.stack([embeddings[pid] for pid in candidate_ids])
    scores    = cosine_similarity([query_vec], matrix)[0]

    # Filter by threshold and sort descending
    scored = [
        {"id": candidate_ids[i], "score": round(float(scores[i]), 4)}
        for i in np.argsort(scores)[::-1]
        if scores[i] >= _RECOMMENDATION_THRESHOLD
    ]

    # Apply category diversity
    category_map = _get_category_map()
    diverse = _apply_diversity(scored, category_map, _MAX_PER_CATEGORY)

    return {
        "recommended_products": diverse[:limit],
        "embedding_method": method,
    }


@router.get("/recommendations/personal", response_model=RecommendationResult)
def get_personal(user_id: int, limit: int = 8):
    """Products personalised to a user via unified CLIP + CF + popularity blending.

    Three signals are blended with weights that adjust proportionally to purchase
    history size (ratio = min(1, history_size / SPARSE_HISTORY_THRESHOLD)):

      clip_w = 1 - cf_w - pop_w          (always dominant)
      cf_w   = ratio * CF_BLEND_WEIGHT   (0 → 0.35 as history grows)
      pop_w  = (1 - ratio) * 0.30        (0.30 → 0 as history grows)

    The RECOMMENDATION_THRESHOLD is still enforced on the raw CLIP score alone
    so only semantically relevant products are returned regardless of CF/pop rank.
    """
    embeddings = visual_search._embeddings
    method     = visual_search._active_embedding_method()

    if not embeddings:
        return {"recommended_products": [], "embedding_method": method}

    purchased_with_dates = _get_purchased_with_dates(user_id)
    if not purchased_with_dates:
        return {"recommended_products": [], "embedding_method": method}

    # Build recency-weighted taste profile
    now           = datetime.now(timezone.utc)
    weighted_vecs = []
    purchased_ids = set()

    for product_id, created_at in purchased_with_dates:
        purchased_ids.add(product_id)
        if product_id not in embeddings:
            continue
        if created_at.tzinfo is None:
            created_at = created_at.replace(tzinfo=timezone.utc)
        days_old = max(0.0, (now - created_at).total_seconds() / 86400)
        weight   = np.exp(-days_old / _RECENCY_HALF_LIFE_DAYS)
        weighted_vecs.append(embeddings[product_id] * weight)

    if not weighted_vecs:
        return {"recommended_products": [], "embedding_method": method}

    taste = np.sum(weighted_vecs, axis=0)
    norm  = np.linalg.norm(taste)
    if norm > 0:
        taste /= norm

    candidate_ids = [pid for pid in embeddings if pid not in purchased_ids]
    if not candidate_ids:
        return {"recommended_products": [], "embedding_method": method}

    matrix = np.stack([embeddings[pid] for pid in candidate_ids])
    scores = cosine_similarity([taste], matrix)[0]  # raw CLIP similarity

    # ── Unified three-signal blending ───────────────────────────────────── #
    # Weights sum to 1.  CF weight increases and popularity weight decreases
    # as history grows — the more data we have, the more we trust behaviour
    # signals over popularity heuristics.
    #
    #  history | clip_w | cf_w  | pop_w   (cf_max=0.35, pop_max=0.30)
    #  1       |  0.68  | 0.12  |  0.20
    #  2       |  0.67  | 0.23  |  0.10
    #  3+      |  0.65  | 0.35  |  0.00
    history_size = len(weighted_vecs)
    ratio  = min(1.0, history_size / _SPARSE_HISTORY_THRESHOLD)
    cf_w   = ratio * _CF_BLEND_WEIGHT
    pop_w  = (1.0 - ratio) * 0.30
    clip_w = 1.0 - cf_w - pop_w

    # CF scores — item-item co-purchase signal
    copurchase_matrix = _get_copurchase_matrix()
    cf_scores = _compute_cf_scores(purchased_ids, candidate_ids, copurchase_matrix)

    # Popularity scores (only computed when pop_w > 0 to save a DB round-trip)
    pop_scores = np.zeros(len(candidate_ids), dtype=np.float32)
    if pop_w > 0:
        pop_counts = _get_popularity_counts()
        max_pop    = max(pop_counts.values(), default=1)
        pop_scores = np.array(
            [pop_counts.get(pid, 0) / max_pop for pid in candidate_ids],
            dtype=np.float32,
        )

    blended   = clip_w * scores + cf_w * cf_scores + pop_w * pop_scores
    order_idx = np.argsort(blended)[::-1]

    # Filter by raw CLIP threshold — quality gate independent of blended rank
    scored = [
        {"id": candidate_ids[i], "score": round(float(scores[i]), 4)}
        for i in order_idx
        if scores[i] >= _RECOMMENDATION_THRESHOLD
    ]

    category_map = _get_category_map()
    diverse = _apply_diversity(scored, category_map, _MAX_PER_CATEGORY)

    return {
        "recommended_products": diverse[:limit],
        "embedding_method": method,
    }


@router.get("/recommendations/collaborative", response_model=RecommendationResult)
def get_collaborative(user_id: int, limit: int = 8):
    """Pure item-item collaborative filtering based on co-purchase frequency.

    For each product the user has bought, aggregates how often every other
    product was purchased in the same order across all users. Candidates are
    ranked by this co-purchase score and diversified across categories.

    Falls back to site-wide popularity ranking when there is no co-purchase
    data for the user's history. Does not require CLIP embeddings.
    """
    method = visual_search._active_embedding_method()

    purchased_with_dates = _get_purchased_with_dates(user_id)
    purchased_ids = {pid for pid, _ in purchased_with_dates}

    all_product_ids = _get_all_product_ids()
    candidate_ids   = [pid for pid in all_product_ids if pid not in purchased_ids]

    if not candidate_ids:
        return {"recommended_products": [], "embedding_method": method}

    copurchase_matrix = _get_copurchase_matrix()
    cf_scores = _compute_cf_scores(purchased_ids, candidate_ids, copurchase_matrix)

    # Fallback: no co-purchase signal → rank by site-wide popularity
    if cf_scores.max() == 0:
        pop_counts = _get_popularity_counts()
        max_pop    = max(pop_counts.values(), default=1)
        cf_scores  = np.array(
            [pop_counts.get(pid, 0) / max_pop for pid in candidate_ids],
            dtype=np.float32,
        )

    order_idx    = np.argsort(cf_scores)[::-1]
    category_map = _get_category_map()
    scored = [
        {"id": candidate_ids[i], "score": round(float(cf_scores[i]), 4)}
        for i in order_idx
        if cf_scores[i] > 0
    ]
    diverse = _apply_diversity(scored, category_map, _MAX_PER_CATEGORY)

    return {
        "recommended_products": diverse[:limit],
        "embedding_method": method,
    }


# --------------------------------------------------------------------------- #
# Helpers                                                                      #
# --------------------------------------------------------------------------- #

def _get_purchased_with_dates(user_id: int) -> list[tuple]:
    """Return list of (product_id, order_created_at) from the user's order history."""
    try:
        conn = visual_search._db_connect()
        with conn.cursor() as cur:
            cur.execute(
                """
                SELECT oi.product_id, o.created_at
                FROM order_items oi
                JOIN orders o ON o.id = oi.order_id
                WHERE o.user_id = %s AND o.deleted_at IS NULL
                ORDER BY o.created_at DESC
                """,
                (user_id,),
            )
            rows = cur.fetchall()
        conn.close()
        return list(rows)
    except Exception as exc:
        print(f"[recommendations] DB query failed: {exc}")
        return []


def _get_all_product_ids() -> list[int]:
    """Return all non-deleted product IDs (used by the collaborative endpoint)."""
    try:
        conn = visual_search._db_connect()
        with conn.cursor() as cur:
            cur.execute("SELECT id FROM products WHERE deleted_at IS NULL")
            rows = cur.fetchall()
        conn.close()
        return [int(row[0]) for row in rows]
    except Exception as exc:
        print(f"[recommendations] all-product-ids query failed: {exc}")
        return []


def _get_copurchase_matrix() -> dict:
    """Return item-item co-purchase frequency: {product_a: {product_b: order_count}}.

    Counts how many distinct orders contain both product A and product B.
    Only considers non-deleted orders.
    """
    try:
        conn = visual_search._db_connect()
        with conn.cursor() as cur:
            cur.execute(
                """
                SELECT oi1.product_id AS pa, oi2.product_id AS pb,
                       COUNT(DISTINCT oi1.order_id) AS cnt
                FROM   order_items oi1
                JOIN   order_items oi2
                       ON  oi1.order_id    = oi2.order_id
                       AND oi1.product_id != oi2.product_id
                JOIN   orders o ON o.id = oi1.order_id
                WHERE  o.deleted_at IS NULL
                GROUP  BY oi1.product_id, oi2.product_id
                """
            )
            rows = cur.fetchall()
        conn.close()
        matrix: dict = {}
        for pa, pb, cnt in rows:
            matrix.setdefault(int(pa), {})[int(pb)] = int(cnt)
        return matrix
    except Exception as exc:
        print(f"[recommendations] co-purchase matrix query failed: {exc}")
        return {}


def _compute_cf_scores(
    purchased_ids: set,
    candidate_ids: list,
    copurchase_matrix: dict,
) -> "np.ndarray":
    """Compute normalised [0, 1] item-item CF scores for each candidate product.

    For each candidate, sums its co-purchase counts with every product the
    user has already bought, then normalises by the maximum observed sum.
    """
    scores = np.zeros(len(candidate_ids), dtype=np.float32)
    for i, cid in enumerate(candidate_ids):
        scores[i] = float(
            sum(copurchase_matrix.get(pid, {}).get(cid, 0) for pid in purchased_ids)
        )
    max_score = scores.max()
    if max_score > 0:
        scores /= max_score
    return scores


def _get_category_map() -> dict:
    """Return {product_id: category_id} for all active products."""
    try:
        conn = visual_search._db_connect()
        with conn.cursor() as cur:
            cur.execute(
                "SELECT id, category_id FROM products WHERE deleted_at IS NULL"
            )
            rows = cur.fetchall()
        conn.close()
        return {row[0]: row[1] for row in rows}
    except Exception as exc:
        print(f"[recommendations] category map query failed: {exc}")
        return {}


def _get_popularity_counts() -> dict:
    """Return {product_id: total_order_count} across all non-deleted orders.

    Used to blend cosine similarity with a site-wide popularity signal when
    the user's purchase history is too sparse to build a reliable taste profile.
    """
    try:
        conn = visual_search._db_connect()
        with conn.cursor() as cur:
            cur.execute(
                """
                SELECT oi.product_id, COUNT(*) AS cnt
                FROM order_items oi
                JOIN orders o ON o.id = oi.order_id
                WHERE o.deleted_at IS NULL
                GROUP BY oi.product_id
                """
            )
            rows = cur.fetchall()
        conn.close()
        return {row[0]: int(row[1]) for row in rows}
    except Exception as exc:
        print(f"[recommendations] popularity query failed: {exc}")
        return {}


def _apply_diversity(scored_items: list, category_map: dict, max_per_category: int) -> list:
    """Limit results to max_per_category products per category for diverse recommendations."""
    category_count: dict = {}
    result = []
    for item in scored_items:
        cat = category_map.get(item["id"], 0)
        if category_count.get(cat, 0) < max_per_category:
            result.append(item)
            category_count[cat] = category_count.get(cat, 0) + 1
    return result

