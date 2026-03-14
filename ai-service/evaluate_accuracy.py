"""
evaluate_accuracy.py — Đánh giá độ chính xác offline cho SmartShop AI.

Chạy trực tiếp từ thư mục ai-service (không cần FastAPI server đang chạy):

    python evaluate_accuracy.py
    python evaluate_accuracy.py --top-k 1 3 5 10
    python evaluate_accuracy.py --rec-threshold 0.35 --vs-threshold 0.55

Yêu cầu:
  - DB_HOST, DB_DATABASE, DB_USERNAME, DB_PASSWORD đọc từ ../.env
  - Đã chạy `php artisan embeddings:generate` để có dữ liệu trong product_embeddings
  - Package: numpy, scikit-learn, pymysql, python-dotenv (đã có trong requirements.txt)

─────────────────────────────────────────────────────────────────────────────
Metrics giải thích:

  Visual Search — Category-Precision@K
    Với mỗi sản phẩm P có embedding, dùng chính embedding đó làm "query" (mô
    phỏng việc upload ảnh sản phẩm đó). Precision@K = tỷ lệ sản phẩm trong
    top-K kết quả cùng danh mục với P. Đây là protocol đánh giá offline chuẩn
    cho hệ thống visual retrieval.

  Recommendations (Similar) — Category-Precision@K + Threshold Coverage
    Cùng cơ chế như Visual Search nhưng áp dụng thêm RECOMMENDATION_THRESHOLD
    và category diversity (giống logic production). Thêm metric "coverage" để
    biết có bao nhiêu % sản phẩm trả về ít nhất 1 kết quả sau khi lọc.

  Recommendations (Personal) — Hit Rate@K + MRR
    Leave-One-Out: với mỗi user có ≥2 sản phẩm đã mua (có embedding), giữ lại
    sản phẩm mua GẦN NHẤT làm "test item", xây taste profile từ phần còn lại,
    kiểm tra xem test item có xuất hiện trong top-K gợi ý không.

    Hit Rate@K = (số user tìm thấy test item trong top-K) / (tổng user đánh giá)
    MRR        = trung bình 1/rank của test item (range 0–100%)
─────────────────────────────────────────────────────────────────────────────
"""

import argparse
import json
import os
import sys
from datetime import datetime, timezone
from pathlib import Path

import numpy as np
import pymysql
from dotenv import load_dotenv
from sklearn.metrics.pairwise import cosine_similarity

# Load .env từ thư mục gốc dự án (cùng pattern với main.py)
load_dotenv(dotenv_path=Path(__file__).parent.parent / ".env")

# Giá trị mặc định (đọc từ .env, có thể override qua CLI args)
_DEFAULT_VS_THRESHOLD  = float(os.getenv("VISUAL_SEARCH_THRESHOLD", "0.60"))
_DEFAULT_REC_THRESHOLD = float(os.getenv("RECOMMENDATION_THRESHOLD", "0.40"))
_DEFAULT_MAX_PER_CAT   = 2
_DEFAULT_TOP_KS        = [1, 3, 5, 8]


# ─────────────────────────────────────────────────────────────────────────── #
# DB helpers                                                                  #
# ─────────────────────────────────────────────────────────────────────────── #

def _db_connect() -> pymysql.Connection:
    return pymysql.connect(
        host=os.getenv("DB_HOST", "127.0.0.1"),
        port=int(os.getenv("DB_PORT", 3306)),
        user=os.getenv("DB_USERNAME", "root"),
        password=os.getenv("DB_PASSWORD", ""),
        database=os.getenv("DB_DATABASE", "smartshop"),
        charset="utf8mb4",
    )


def _load_embeddings(conn) -> dict[int, np.ndarray]:
    """Return {product_id: L2-normalized embedding vector}."""
    with conn.cursor() as cur:
        cur.execute("SELECT product_id, embedding FROM product_embeddings")
        rows = cur.fetchall()
    return {row[0]: np.array(json.loads(row[1]), dtype=np.float32) for row in rows}


def _load_category_map(conn) -> dict[int, int]:
    """Return {product_id: category_id} for active products."""
    with conn.cursor() as cur:
        cur.execute(
            "SELECT id, category_id FROM products WHERE deleted_at IS NULL AND status = 1"
        )
        rows = cur.fetchall()
    return {row[0]: row[1] for row in rows}


def _load_category_names(conn) -> dict[int, str]:
    """Return {category_id: category_name}."""
    with conn.cursor() as cur:
        cur.execute("SELECT id, name FROM categories WHERE is_active = 1")
        rows = cur.fetchall()
    return {row[0]: row[1] for row in rows}


def _load_purchase_history(conn) -> dict[int, list[tuple]]:
    """Return {user_id: [(product_id, order_created_at), ...]} sorted DESC by date."""
    with conn.cursor() as cur:
        cur.execute(
            """
            SELECT o.user_id, oi.product_id, o.created_at
            FROM order_items oi
            JOIN orders o ON o.id = oi.order_id
            WHERE o.deleted_at IS NULL
            ORDER BY o.user_id, o.created_at DESC
            """
        )
        rows = cur.fetchall()
    history: dict[int, list] = {}
    for user_id, product_id, created_at in rows:
        history.setdefault(user_id, []).append((product_id, created_at))
    return history


# ─────────────────────────────────────────────────────────────────────────── #
# Shared utility                                                              #
# ─────────────────────────────────────────────────────────────────────────── #

def _apply_diversity(scored: list, category_map: dict, max_per_cat: int) -> list:
    """Limit results to max_per_cat products per category (mirrors production logic)."""
    count: dict[int, int] = {}
    result = []
    for item in scored:
        cat = category_map.get(item["id"], 0)
        if count.get(cat, 0) < max_per_cat:
            result.append(item)
            count[cat] = count.get(cat, 0) + 1
    return result


def _sep(title: str) -> None:
    print(f"\n{'─' * 64}")
    print(f"  {title}")
    print(f"{'─' * 64}")


def _bar(value: float, width: int = 30) -> str:
    filled = int(round(value / 100 * width))
    return "█" * filled + "░" * (width - filled)


# ─────────────────────────────────────────────────────────────────────────── #
# 1. Visual Search — Category-Precision@K                                     #
# ─────────────────────────────────────────────────────────────────────────── #

def eval_visual_search(
    embeddings: dict,
    category_map: dict,
    category_names: dict,
    top_ks: list[int],
    threshold: float,
) -> None:
    _sep("1. Visual Search — Category-Precision@K")

    # Chỉ đánh giá sản phẩm có cả embedding lẫn category
    ids = [pid for pid in embeddings if pid in category_map]
    n   = len(ids)

    print(f"\n  Embeddings hiện có : {len(embeddings)}")
    print(f"  Sản phẩm có category: {n}")
    print(f"  Threshold           : {threshold}")

    if n < 2:
        print("\n  ⚠  Chưa đủ sản phẩm để đánh giá (cần ≥ 2).")
        return

    matrix     = np.stack([embeddings[pid] for pid in ids])
    sim_matrix = cosine_similarity(matrix)  # (n, n)

    precisions_raw = {k: [] for k in top_ks}
    precisions_thr = {k: [] for k in top_ks}
    above_threshold = 0

    for i, query_pid in enumerate(ids):
        query_cat = category_map[query_pid]
        scores    = sim_matrix[i].copy()
        scores[i] = -1.0  # loại chính nó

        order          = np.argsort(scores)[::-1]
        sorted_pids    = [ids[j] for j in order]
        sorted_scores  = scores[order]

        if sorted_scores[0] >= threshold:
            above_threshold += 1

        # Precision@K không lọc
        for k in top_ks:
            top_pids = sorted_pids[:k]
            match    = sum(1 for p in top_pids if category_map.get(p) == query_cat)
            precisions_raw[k].append(match / k)

        # Precision@K sau khi lọc threshold
        filtered = [sorted_pids[j] for j in range(len(sorted_pids)) if sorted_scores[j] >= threshold]
        for k in top_ks:
            bucket = filtered[:k]
            if not bucket:
                precisions_thr[k].append(0.0)
            else:
                match = sum(1 for p in bucket if category_map.get(p) == query_cat)
                precisions_thr[k].append(match / len(bucket))

    coverage = above_threshold / n * 100
    print(f"  Coverage (≥1 kết quả trên threshold): {coverage:.1f}%  {_bar(coverage)}")

    print(f"\n  {'K':<5} {'Không lọc':>12}  {'Sau threshold':>13}   Diễn giải")
    print(f"  {'─'*5} {'─'*12}  {'─'*13}   {'─'*40}")
    for k in top_ks:
        p_raw = np.mean(precisions_raw[k]) * 100
        p_thr = np.mean(precisions_thr[k]) * 100
        note  = "↑ threshold giúp tăng chính xác" if p_thr > p_raw else ""
        print(f"  {k:<5} {p_raw:>10.1f}%  {p_thr:>11.1f}%   {note}")

    print(f"\n  → Precision@K = % kết quả top-K cùng danh mục với query.")
    print(f"    100% = tất cả top-K đều cùng danh mục (hoàn hảo).")

    # Per-category breakdown
    print(f"\n  Per-category Precision@{top_ks[-1]} (không lọc):")
    cat_precisions: dict[int, list] = {}
    for i, query_pid in enumerate(ids):
        query_cat = category_map[query_pid]
        scores    = sim_matrix[i].copy()
        scores[i] = -1.0
        order     = np.argsort(scores)[::-1]
        top_pids  = [ids[j] for j in order[:top_ks[-1]]]
        match     = sum(1 for p in top_pids if category_map.get(p) == query_cat)
        cat_precisions.setdefault(query_cat, []).append(match / top_ks[-1])

    for cat_id, values in sorted(cat_precisions.items(), key=lambda x: -np.mean(x[1])):
        p   = np.mean(values) * 100
        n_p = len(values)
        name = category_names.get(cat_id, f"cat_{cat_id}")
        print(f"    {name:<20} {p:>6.1f}%  (n={n_p})  {_bar(p, 20)}")


# ─────────────────────────────────────────────────────────────────────────── #
# 2. Recommendations (Similar) — Threshold Coverage + Diversity              #
# ─────────────────────────────────────────────────────────────────────────── #

def eval_recommendations_similar(
    embeddings: dict,
    category_map: dict,
    top_ks: list[int],
    threshold: float,
    max_per_cat: int,
) -> None:
    _sep("2. Recommendations (Similar) — After Threshold + Diversity")

    ids = [pid for pid in embeddings if pid in category_map]
    n   = len(ids)

    print(f"\n  Sản phẩm đánh giá  : {n}")
    print(f"  Threshold          : {threshold}")
    print(f"  Max per category   : {max_per_cat}")

    if n < 2:
        print("\n  ⚠  Chưa đủ sản phẩm để đánh giá.")
        return

    matrix     = np.stack([embeddings[pid] for pid in ids])
    sim_matrix = cosine_similarity(matrix)

    result_counts: list[int]   = []
    unique_cats_counts: list[int] = []
    avg_score_list: list[float]   = []
    precisions: dict[int, list]   = {k: [] for k in top_ks}

    for i, query_pid in enumerate(ids):
        query_cat = category_map[query_pid]
        scores    = sim_matrix[i].copy()
        scores[i] = -1.0

        order  = np.argsort(scores)[::-1]
        scored = [
            {"id": ids[j], "score": float(scores[j])}
            for j in order
            if scores[j] >= threshold
        ]
        diverse = _apply_diversity(scored, category_map, max_per_cat)[:8]

        result_counts.append(len(diverse))
        if diverse:
            cats = {category_map.get(item["id"], 0) for item in diverse}
            unique_cats_counts.append(len(cats))
            avg_score_list.append(np.mean([item["score"] for item in diverse]))

        for k in top_ks:
            bucket = diverse[:k]
            if not bucket:
                precisions[k].append(0.0)
            else:
                match = sum(1 for item in bucket if category_map.get(item["id"]) == query_cat)
                precisions[k].append(match / len(bucket))

    zero_count = sum(1 for c in result_counts if c == 0)
    print(f"\n  Avg kết quả trả về   : {np.mean(result_counts):.1f} / 8")
    print(f"  Sản phẩm có 0 kết quả: {zero_count} ({zero_count/n*100:.1f}%)")
    if unique_cats_counts:
        print(f"  Avg category đa dạng : {np.mean(unique_cats_counts):.1f}")
    if avg_score_list:
        print(f"  Avg similarity score : {np.mean(avg_score_list):.4f}")

    print(f"\n  Category-Precision@K (sau threshold + diversity):")
    print(f"  {'K':<5} {'Precision@K':>12}")
    print(f"  {'─'*5} {'─'*12}")
    for k in top_ks:
        p = np.mean(precisions[k]) * 100
        print(f"  {k:<5} {p:>10.1f}%  {_bar(p, 25)}")

    # Phân bố số kết quả
    print(f"\n  Phân bố số kết quả sau threshold+diversity (limit=8):")
    for count in range(0, 9):
        n_p = sum(1 for c in result_counts if c == count)
        bar = _bar(n_p / n * 100, 20)
        print(f"    {count} kết quả: {n_p:4d} sản phẩm  {bar}")


# ─────────────────────────────────────────────────────────────────────────── #
# 3. Recommendations (Personal) — Leave-One-Out                              #
# ─────────────────────────────────────────────────────────────────────────── #

def eval_personal_recommendations(
    embeddings: dict,
    category_map: dict,
    purchase_history: dict,
    top_ks: list[int],
) -> None:
    _sep("3. Recommendations (Personal) — Leave-One-Out")

    # Build eligible users: ≥2 distinct purchased products with embeddings
    eligible: dict[int, list[tuple]] = {}
    for user_id, history in purchase_history.items():
        # Deduplicate — keep earliest date per product for accumulation
        seen: dict[int, datetime] = {}
        for pid, dt in history:
            if pid not in seen:
                seen[pid] = dt
        valid = sorted(
            [(pid, dt) for pid, dt in seen.items() if pid in embeddings],
            key=lambda x: x[1],
            reverse=True,  # mới nhất trước
        )
        if len(valid) >= 2:
            eligible[user_id] = valid

    print(f"\n  Users có ≥2 lần mua (có embedding): {len(eligible)}")

    if len(eligible) == 0:
        print("\n  ⚠  Chưa đủ dữ liệu lịch sử mua để đánh giá Leave-One-Out.")
        print(f"     → Mua thêm sản phẩm với ít nhất 2 tài khoản khác nhau")
        print(f"       (mỗi tài khoản cần ≥2 đơn hàng có sản phẩm khác nhau).")
        return

    hit_rates      = {k: 0 for k in top_ks}
    reciprocal_ranks: list[float] = []
    evaluated      = 0

    for user_id, history in eligible.items():
        held_out_pid     = history[0][0]       # sản phẩm mua gần nhất
        training_history = history[1:]         # phần còn lại

        # Xây taste profile có recency-weighting (giống production)
        now           = datetime.now(timezone.utc)
        weighted_vecs = []
        training_pids = set()

        for pid, created_at in training_history:
            training_pids.add(pid)
            if pid not in embeddings:
                continue
            if created_at.tzinfo is None:
                created_at = created_at.replace(tzinfo=timezone.utc)
            days_old = max(0.0, (now - created_at).total_seconds() / 86400)
            weight   = np.exp(-days_old / 30.0)
            weighted_vecs.append(embeddings[pid] * weight)

        if not weighted_vecs:
            continue

        taste = np.sum(weighted_vecs, axis=0)
        norm  = np.linalg.norm(taste)
        if norm > 0:
            taste /= norm

        # Candidates = tất cả sản phẩm chưa mua (bao gồm held_out_pid)
        candidate_ids = [
            pid for pid in embeddings
            if pid not in training_pids
        ]
        if held_out_pid not in candidate_ids:
            candidate_ids.append(held_out_pid)

        if len(candidate_ids) < 2:
            continue

        cand_matrix = np.stack([embeddings[pid] for pid in candidate_ids])
        scores      = cosine_similarity([taste], cand_matrix)[0]
        ranked_pids = [candidate_ids[j] for j in np.argsort(scores)[::-1]]

        try:
            rank = ranked_pids.index(held_out_pid) + 1  # 1-indexed
        except ValueError:
            continue

        reciprocal_ranks.append(1.0 / rank)
        for k in top_ks:
            if rank <= k:
                hit_rates[k] += 1
        evaluated += 1

    if not reciprocal_ranks:
        print("  ⚠  Không tính được metrics.")
        return

    print(f"  Users được đánh giá: {evaluated}\n")
    print(f"  {'K':<6} {'Hit Rate@K':>12}   {'Biểu đồ'}")
    print(f"  {'─'*6} {'─'*12}   {'─'*30}")
    for k in top_ks:
        hr  = hit_rates[k] / evaluated * 100
        bar = _bar(hr, 25)
        print(f"  {k:<6} {hr:>10.1f}%   {bar}")

    mrr = np.mean(reciprocal_ranks) * 100
    print(f"\n  MRR (Mean Reciprocal Rank): {mrr:.1f}%")

    print(f"\n  → Hit Rate@K: % user mà sản phẩm mua gần nhất nằm trong top-K gợi ý.")
    print(f"    MRR: trung bình nghịch đảo của rank — càng cao càng tốt.")
    print(f"    Baseline ngẫu nhiên cho {len(embeddings)} sản phẩm: {100/len(embeddings):.2f}%")


# ─────────────────────────────────────────────────────────────────────────── #
# Main                                                                        #
# ─────────────────────────────────────────────────────────────────────────── #

def main() -> None:
    parser = argparse.ArgumentParser(
        description="Đánh giá độ chính xác offline của SmartShop AI.",
        formatter_class=argparse.ArgumentDefaultsHelpFormatter,
    )
    parser.add_argument(
        "--top-k", nargs="+", type=int, default=_DEFAULT_TOP_KS,
        metavar="K",
        help="Danh sách giá trị K cho Precision@K và Hit Rate@K",
    )
    parser.add_argument(
        "--vs-threshold", type=float, default=_DEFAULT_VS_THRESHOLD,
        help="Ngưỡng tương đồng Visual Search",
    )
    parser.add_argument(
        "--rec-threshold", type=float, default=_DEFAULT_REC_THRESHOLD,
        help="Ngưỡng tương đồng Recommendations",
    )
    parser.add_argument(
        "--max-per-cat", type=int, default=_DEFAULT_MAX_PER_CAT,
        help="Số sản phẩm tối đa mỗi danh mục (diversity)",
    )
    args = parser.parse_args()

    top_ks = sorted(set(args.top_k))

    print("=" * 64)
    print("  SmartShop AI — Offline Accuracy Evaluation")
    print(f"  {datetime.now().strftime('%Y-%m-%d %H:%M:%S')}")
    print("=" * 64)
    print(f"\n  DB       : {os.getenv('DB_DATABASE', 'smartshop')}@{os.getenv('DB_HOST', '127.0.0.1')}")
    print(f"  top_ks   : {top_ks}")
    print(f"  VS thr   : {args.vs_threshold}   REC thr: {args.rec_threshold}")

    # ── Connect ──────────────────────────────────────────────────────────── #
    try:
        conn = _db_connect()
    except Exception as exc:
        print(f"\n  ✗ Không thể kết nối DB: {exc}")
        sys.exit(1)

    # ── Load data ─────────────────────────────────────────────────────────── #
    print("\n  Đang tải dữ liệu...", end="", flush=True)
    embeddings       = _load_embeddings(conn)
    category_map     = _load_category_map(conn)
    category_names   = _load_category_names(conn)
    purchase_history = _load_purchase_history(conn)
    conn.close()

    print(f" xong.")
    print(f"  Embeddings       : {len(embeddings)}")
    print(f"  Sản phẩm active  : {len(category_map)}")
    print(f"  Users có đơn hàng: {len(purchase_history)}")

    if not embeddings:
        print("\n  ✗ Không có embedding nào. Chạy: php artisan embeddings:generate")
        sys.exit(1)

    # ── Run evaluations ───────────────────────────────────────────────────── #
    eval_visual_search(
        embeddings, category_map, category_names,
        top_ks, args.vs_threshold,
    )
    eval_recommendations_similar(
        embeddings, category_map,
        top_ks, args.rec_threshold, args.max_per_cat,
    )
    eval_personal_recommendations(
        embeddings, category_map, purchase_history,
        top_ks,
    )

    print("\n" + "=" * 64)
    print("  Đánh giá hoàn tất.")
    print("=" * 64 + "\n")


if __name__ == "__main__":
    main()
