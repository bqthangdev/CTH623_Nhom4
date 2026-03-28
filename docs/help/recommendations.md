# Phân tích chức năng Gợi ý sản phẩm tương tự — SmartShop

> Tài liệu này tập trung vào phần **"Sản phẩm tương tự"** hiển thị bên dưới trang chi tiết sản phẩm.

---

## Bối cảnh: Tại sao Recommendations khác Visual Search?

**Visual Search** (tìm kiếm bằng ảnh): Người dùng upload một ảnh mới → hệ thống dùng CLIP để trích xuất embedding từ ảnh đó → so sánh với sản phẩm trong DB.

**Similar Products** (gợi ý tương tự): Người dùng đang xem sản phẩm X → hệ thống lấy embedding **đã có sẵn** của sản phẩm X trong DB → so sánh với các sản phẩm khác.

**Điểm khác nhau quan trọng:** Recommendations **không cần chạy ảnh qua CLIP** vì embedding đã được tính trước (bởi `php artisan embeddings:generate`). Hệ thống chỉ cần đọc từ RAM và tính cosine similarity.

Hai chức năng dùng chung một bộ embedding, thuật toán cosine similarity giống nhau, chỉ khác ở đầu vào:

| | Visual Search | Similar Products |
|---|---|---|
| Đầu vào | Ảnh người dùng upload (bytes mới) | Product ID (embedding đã có sẵn) |
| Bước CLIP encode | Có — chạy `_extract_embedding()` | Không — đọc thẳng từ `_embeddings[id]` |
| Ngưỡng similarity | 0.60 (chặt hơn) | 0.40 (thoáng hơn) |
| Thêm bước đặc biệt | Không | Có — lọc đa dạng danh mục |

---

## 1. Chi tiết từng hàm trong recommendations.py

---

### Biến cấu hình toàn cục

```python
_RECOMMENDATION_THRESHOLD: float = float(os.getenv("RECOMMENDATION_THRESHOLD", "0.40"))
_MAX_PER_CATEGORY: int = 2
```

- `_RECOMMENDATION_THRESHOLD`: ngưỡng tối thiểu để một sản phẩm được gợi ý. Mặc định 0.40 — thoáng hơn ngưỡng Visual Search (0.60) vì gợi ý chấp nhận sản phẩm "cùng nhóm", không nhất thiết phải y hệt.
- `_MAX_PER_CATEGORY`: tối đa bao nhiêu sản phẩm cùng danh mục được xuất hiện trong kết quả — đảm bảo kết quả đa dạng.

---

### Endpoint `GET /api/recommendations/similar?product_id=X&limit=8`

**Đây là endpoint được gọi khi người dùng xem trang chi tiết sản phẩm.**

**Bước 1 — Kiểm tra dữ liệu:**
```python
embeddings = visual_search._embeddings   # dùng chung dict với Visual Search
if not embeddings or product_id not in embeddings:
    return {"recommended_products": [], "embedding_method": method}
```
Nếu sản phẩm X chưa được tạo embedding (chưa chạy `embeddings:generate`) → trả về rỗng. Laravel sẽ dùng fallback.

**Bước 2 — Lấy embedding của sản phẩm hiện tại:**
```python
query_vec = embeddings[product_id]   # vector 512 chiều, ĐÃ có sẵn trong RAM
```
Đây là điểm then chốt: **không cần encode ảnh mới**, chỉ đọc từ dict. Nhanh hơn rất nhiều so với Visual Search.

**Bước 3 — Loại trừ chính sản phẩm đó:**
```python
candidate_ids = [pid for pid in embeddings if pid != product_id]
```
Không thể gợi ý "sản phẩm X khi đang xem sản phẩm X".

**Bước 4 — Tính cosine similarity hàng loạt:**
```python
matrix = np.stack([embeddings[pid] for pid in candidate_ids])   # (n-1, 512)
scores = cosine_similarity([query_vec], matrix)[0]               # (n-1,)
```
Cách tính hoàn toàn giống Visual Search — một phép nhân ma trận duy nhất cho toàn bộ catalog.

**Bước 5 — Lọc ngưỡng và sắp xếp giảm dần:**
```python
scored = [
    {"id": candidate_ids[i], "score": round(float(scores[i]), 4)}
    for i in np.argsort(scores)[::-1]
    if scores[i] >= _RECOMMENDATION_THRESHOLD   # >= 0.40
]
```

**Bước 6 — Lọc đa dạng danh mục:**
```python
category_map = _get_category_map()
diverse = _apply_diversity(scored, category_map, _MAX_PER_CATEGORY)
```
*(Giải thích chi tiết ở phần dưới)*

**Bước 7 — Trả về JSON:**
```json
{
  "recommended_products": [
    {"id": 5,  "score": 0.8731},
    {"id": 12, "score": 0.8243},
    {"id": 3,  "score": 0.7915}
  ],
  "embedding_method": "CLIP ViT-B-32 (512-dim)"
}
```

---

### Hàm `_get_category_map()`

```python
def _get_category_map() -> dict:
    # Query: SELECT id, category_id FROM products WHERE deleted_at IS NULL
    # Trả về: {product_id: category_id, ...}
```

**Tại sao cần hàm này?**

Để lọc đa dạng danh mục, cần biết mỗi sản phẩm thuộc danh mục nào. Hàm này query bảng `products` mỗi lần gọi — đơn giản, không cache (dữ liệu sản phẩm ít thay đổi trong khoảng request).

**Kết quả ví dụ:**
```python
{
    1: 3,   # sản phẩm id=1 thuộc danh mục id=3 (Áo nam)
    2: 3,   # sản phẩm id=2 thuộc danh mục id=3 (Áo nam)
    5: 7,   # sản phẩm id=5 thuộc danh mục id=7 (Quần)
    12: 7,  # sản phẩm id=12 thuộc danh mục id=7 (Quần)
    ...
}
```

---

### Hàm `_apply_diversity(scored_items, category_map, max_per_category)`

**Mục đích:** Tránh kết quả gợi ý toàn cùng một loại sản phẩm.

**Vấn đề nếu không có hàm này:**

Giả sử sản phẩm đang xem là "Áo khoác da đen". Tất cả áo khoác da khác sẽ có score rất cao → kết quả gợi ý toàn áo khoác da, không có quần, giày, phụ kiện. Trải nghiệm mua sắm kém.

**Cách hoạt động:**

```python
def _apply_diversity(scored_items, category_map, max_per_category):
    category_count = {}   # đếm số sản phẩm đã chọn theo từng danh mục
    result = []
    for item in scored_items:   # duyệt theo thứ tự score giảm dần
        cat = category_map.get(item["id"], 0)
        if category_count.get(cat, 0) < max_per_category:   # < 2
            result.append(item)
            category_count[cat] = category_count.get(cat, 0) + 1
    return result
```

**Ví dụ minh họa** với `max_per_category = 2`:

```
Danh sách sau khi sắp xếp score:
  id=5,  score=0.87, danh mục=Áo khoác    → chọn (áo khoác: 1/2)
  id=8,  score=0.84, danh mục=Áo khoác    → chọn (áo khoác: 2/2)
  id=2,  score=0.81, danh mục=Áo khoác    → BỎ QUA (áo khoác đã đủ 2)
  id=12, score=0.79, danh mục=Quần        → chọn (quần: 1/2)
  id=3,  score=0.76, danh mục=Áo khoác    → BỎ QUA (áo khoác đã đủ 2)
  id=7,  score=0.73, danh mục=Giày        → chọn (giày: 1/2)
  id=15, score=0.71, danh mục=Quần        → chọn (quần: 2/2)
  ...

Kết quả sau lọc: [id=5, id=8, id=12, id=7, id=15, ...]
→ Đa dạng hơn, không bị lặp danh mục
```

---

## 2. Luồng xử lý phía Laravel

### `ProductController::show()`

```php
public function show(Request $request, Product $product): View
{
    // ... load relations, record view ...

    $recommendations = $this->recommendationService->getForProduct($product);

    return view('shop.products.show', compact('product', 'recommendations', ...));
}
```

- Mỗi khi người dùng mở trang chi tiết sản phẩm, `getForProduct($product)` được gọi.
- Kết quả `$recommendations` được truyền thẳng vào Blade template.

---

### `RecommendationService::getForProduct()`

```php
public function getForProduct(Product $product, int $limit = 8): Collection
{
    try {
        return $this->fetchOrdered('/api/recommendations/similar', [
            'product_id' => $product->id,
            'limit'      => $limit,
        ]);
    } catch (\Exception $e) {
        Log::warning('AI similar-products failed, using fallback.', [...]);
        return $this->productRepository->getSameCategoryExcept(
            $product->category_id, $product->id, $limit
        );
    }
}
```

Giống với `VisualSearchService`, có **hai nhánh**:
- **Nhánh thành công:** gọi AI Service, nhận kết quả được xếp hạng bởi AI.
- **Nhánh lỗi (fallback):** Log warning → trả về sản phẩm cùng danh mục từ DB thông thường (không có AI).

---

### `RecommendationService::fetchOrdered()`

Đây là hàm dùng chung cho cả gợi ý tương tự và gợi ý cá nhân.

**Từng bước:**

1. HTTP GET đến AI Service với timeout (mặc định 10 giây):
   ```php
   $response = Http::timeout(10)->get(config('services.ai.url') . $path, $params);
   ```

2. Nếu response lỗi → ném `RuntimeException` → controller nhảy vào `catch`, dùng fallback.

3. Parse JSON, lấy danh sách `recommended_products`:
   ```php
   $items    = collect($response->json('recommended_products', []));
   $ids      = $items->pluck('id')->filter()->all();
   $scoreMap = $items->keyBy('id')->map(fn($item) => $item['score'] ?? null);
   ```

4. Gọi `ProductRepository::getByIds($ids)` — lấy đầy đủ thông tin sản phẩm từ DB MySQL (tên, giá, ảnh, v.v.).

5. **Giữ nguyên thứ tự AI trả về** — quan trọng! Eloquent query không đảm bảo thứ tự theo `IN (...)`:
   ```php
   $idOrder = array_flip($ids);   // [id => vị_trí]
   return $this->productRepository->getByIds($ids)
       ->sortBy(fn($product) => $idOrder[$product->id] ?? PHP_INT_MAX)
       ->each(fn($p) => $p->similarity_score = $scoreMap->get($p->id))
       ->values();
   ```

6. Gắn `similarity_score` (score từ AI) vào từng Product object — dùng để hiển thị badge trên UI nếu cần.

---

## 3. So sánh thuật toán với Visual Search

Cả hai đều dùng **cùng một thuật toán cosine similarity**, cùng bộ embedding, chỉ khác ở điểm xuất phát:

```
Visual Search:
  Ảnh upload (bytes) → CLIP encode → query_vec (512-dim) → cosine với toàn bộ catalog

Similar Products:
  Product ID → đọc _embeddings[id] → query_vec (512-dim) → cosine với toàn bộ catalog
                    ↑
            KHÔNG cần CLIP encode vì đã có sẵn
```

**Hình dung trong không gian vector:**

Mỗi sản phẩm là một điểm trong không gian 512 chiều. Khi đang xem sản phẩm X, hệ thống tìm các sản phẩm có điểm **gần nhất với X** trong không gian đó.

```
                        [Áo khoác da đen]  ← sản phẩm đang xem (= query_vec)
                               ●
                          ↗   ↑   ↖
                     ●        ●        ●
               [Áo khoác]  [Áo da]  [Áo len]
               score=0.87  score=0.83  score=0.76
```

Tất cả các sản phẩm "áo" có xu hướng nằm gần nhau trong không gian 512 chiều vì CLIP được huấn luyện để hiểu nội dung ảnh — ảnh áo và ảnh áo giống nhau hơn ảnh áo và ảnh tủ lạnh.

---

## 4. Ngưỡng similarity và ý nghĩa

| Ngưỡng | Chức năng | Lý do |
|---|---|---|
| 0.60 | Visual Search | Tìm kiếm cần chính xác cao — người dùng upload ảnh cụ thể, muốn kết quả sát |
| 0.40 | Similar Products | Gợi ý chấp nhận độ "tương tự" rộng hơn — chỉ cần cùng nhóm sản phẩm |

**Ví dụ minh họa:**
```
Người dùng đang xem: Áo khoác da đen

score=0.87 → Áo khoác da nâu     ✓ cả hai ngưỡng chấp nhận
score=0.72 → Áo bomber da        ✓ cả hai ngưỡng chấp nhận
score=0.55 → Áo denim             ✓ chỉ Similar Products (0.40) chấp nhận
                                  ✗ Visual Search (0.60) loại bỏ
score=0.31 → Quần jeans           ✗ cả hai loại bỏ
```

---

## 5. Biến môi trường

| Biến | Mặc định | Mô tả |
|---|---|---|
| `RECOMMENDATION_THRESHOLD` | `0.40` | Ngưỡng cosine similarity tối thiểu để xuất hiện trong gợi ý |
| `AI_SERVICE_URL` | `http://localhost:8001` | Địa chỉ AI Service, cấu hình trong Laravel `.env` |
| `AI_SERVICE_TIMEOUT` | `10` | Timeout HTTP cho recommendations (ngắn hơn Visual Search — 10s vs 30s) |
