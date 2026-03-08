# SmartShop — Tài liệu Chức năng AI

> Mô tả chi tiết cách hoạt động của hai chức năng AI trong dự án SmartShop:
> **Tìm kiếm bằng hình ảnh** và **Gợi ý sản phẩm**.

---

## Mục lục

1. [Tổng quan kiến trúc](#1-tổng-quan-kiến-trúc)
2. [Tìm kiếm bằng hình ảnh (Visual Search)](#2-tìm-kiếm-bằng-hình-ảnh-visual-search)
3. [Gợi ý sản phẩm (Recommendations)](#3-gợi-ý-sản-phẩm-recommendations)
4. [Cấu hình AI Service](#4-cấu-hình-ai-service)
5. [Nâng cấp lên Production](#5-nâng-cấp-lên-production)

---

## 1. Tổng quan kiến trúc

Cả hai chức năng AI đều theo mô hình **microservice**: Laravel gọi sang một service Python
riêng biệt qua HTTP. Nếu AI Service không phản hồi hoặc trả lỗi, Laravel tự động chuyển
sang **fallback** để đảm bảo giao diện vẫn hoạt động bình thường.

```
Laravel App (port 8000)
        │
        │  HTTP (timeout configurable)
        ▼
AI Service – FastAPI (port 8001)
   ├── POST /api/visual-search
   └── GET  /api/recommendations
```

### Các file liên quan

| Thành phần | File |
|---|---|
| AI Service entrypoint | `ai-service/main.py` |
| Visual Search router (Python) | `ai-service/routers/visual_search.py` |
| Recommendations router (Python) | `ai-service/routers/recommendations.py` |
| Visual Search Service (Laravel) | `app/Services/VisualSearchService.php` |
| Recommendations Service (Laravel) | `app/Services/RecommendationService.php` |
| API Controller – Visual Search | `app/Http/Controllers/Api/VisualSearchController.php` |
| API Controller – Recommendations | `app/Http/Controllers/Api/RecommendationController.php` |
| Shop Controller – Visual Search | `app/Http/Controllers/Shop/VisualSearchController.php` |
| Shop Controller – Products (gọi gợi ý) | `app/Http/Controllers/Shop/ProductController.php` |

---

## 2. Tìm kiếm bằng hình ảnh (Visual Search)

### Mô tả

Cho phép người dùng tải lên một ảnh sản phẩm để tìm kiếm các sản phẩm tương tự trong
cửa hàng. Thay vì gõ từ khóa, người dùng chụp ảnh hoặc chọn ảnh từ thiết bị.

### Luồng xử lý

```
Người dùng upload ảnh
        │
        ▼
[Web] POST /visual-search
 Shop\VisualSearchController@search
 ├── Validate: required|image|mimes:jpg,jpeg,png,webp|max:4096
 └── Gọi VisualSearchService::search()
             │
             ├─ [Thành công]
             │   POST http://ai-service/api/visual-search  (multipart/form-data)
             │   ← { products: [{id, similarity_score}, ...] }
             │   Lấy danh sách ID → ProductRepository::getByIds()
             │   └── Trả về view shop.visual-search với $results
             │
             └─ [Thất bại / timeout]
                 Log warning
                 Fallback: ProductRepository::getFeatured(10)
                 └── Trả về view shop.visual-search với $results (sản phẩm nổi bật)
```

### Routes

| Method | URI | Mục đích |
|---|---|---|
| `GET` | `/visual-search` | Hiển thị trang tìm kiếm (form upload) |
| `POST` | `/visual-search` | Xử lý ảnh, trả về kết quả (Web form) |
| `POST` | `/api/v1/visual-search` | Endpoint JSON cho AJAX (yêu cầu auth, throttle 10/min) |

### AI Service endpoint

**`POST /api/visual-search`** — nhận `multipart/form-data` với field `image`.

```json
// Response
{
    "products": [
        { "id": 5,  "similarity_score": 1.00 },
        { "id": 12, "similarity_score": 0.93 },
        { "id": 3,  "similarity_score": 0.86 }
    ]
}
```

Danh sách trả về tối đa **10 sản phẩm**, sắp xếp theo `similarity_score` giảm dần.

**Cài đặt demo hiện tại:** tính MD5 hash của nội dung ảnh → dùng làm seed ngẫu nhiên
→ shuffle danh sách product ID → lấy top 10. Kết quả mang tính deterministic (cùng
ảnh luôn ra cùng kết quả) nhưng không dựa trên nội dung thực của ảnh.

### Fallback

Khi AI Service lỗi, `VisualSearchService` bắt exception, ghi log warning, và gọi
`ProductRepository::getFeatured(10)` để trả về sản phẩm nổi bật thay thế. Người dùng
vẫn nhìn thấy danh sách sản phẩm, không báo lỗi.

---

## 3. Gợi ý sản phẩm (Recommendations)

### Mô tả

Hiển thị danh sách sản phẩm gợi ý ở trang chi tiết sản phẩm. Gợi ý được cá nhân hoá
theo `user_id` nếu người dùng đã đăng nhập.

### Luồng xử lý

```
Người dùng xem trang sản phẩm
        │
        ▼
[Web] GET /products/{product:slug}
 Shop\ProductController@show
 ├── Load product (images, category, attributes, reviews)
 ├── Ghi nhận lượt xem: ProductService::recordView()
 └── Gọi RecommendationService::getForProduct(product, userId)
             │
             ├─ [Thành công]
             │   GET http://ai-service/api/recommendations
             │       ?product_id=X&user_id=Y&limit=8
             │   ← { recommended_products: [{id, score}, ...] }
             │   Lấy danh sách ID (giữ đúng thứ tự AI)
             │   → ProductRepository::getByIds()
             │   └── Trả về view shop.products.show với $recommendations
             │
             └─ [Thất bại / timeout]
                 Log warning
                 Fallback: ProductRepository::getSameCategoryExcept(
                               category_id, product_id, limit)
                 └── Trả về view shop.products.show với $recommendations
```

### Routes

| Method | URI | Mục đích |
|---|---|---|
| `GET` | `/api/v1/products/{product}/recommendations` | Endpoint JSON (public, throttle 60/min) |

> Chức năng gợi ý trên trang sản phẩm không có route riêng — dữ liệu được nhúng trực
> tiếp vào trang khi render server-side qua `ProductController@show`.

### AI Service endpoint

**`GET /api/recommendations`** — query params:

| Param | Kiểu | Bắt buộc | Mô tả |
|---|---|---|---|
| `product_id` | `int` | Có | ID của sản phẩm đang xem |
| `user_id` | `int` | Không | ID người dùng để cá nhân hoá |
| `limit` | `int` | Không (mặc định 8) | Số lượng gợi ý tối đa |

```json
// Response
{
    "recommended_products": [
        { "id": 7,  "score": 0.95 },
        { "id": 2,  "score": 0.90 },
        { "id": 14, "score": 0.85 }
    ]
}
```

**Lưu ý quan trọng:** Laravel giữ nguyên **thứ tự** mà AI Service trả về khi map sang
Eloquent Collection (không dùng `whereIn` thông thường vì SQL không đảm bảo thứ tự).

```php
// RecommendationService::fetchFromAiService()
$products = $this->productRepository->getByIds($ids)->keyBy('id');
return collect($ids)->map(fn ($id) => $products[$id] ?? null)->filter()->values();
```

**Cài đặt demo hiện tại:** dùng `product_id` làm seed ngẫu nhiên → shuffle pool (loại
trừ chính sản phẩm hiện tại) → lấy top-K. Mỗi sản phẩm luôn có cùng danh sách gợi ý,
không phụ thuộc vào `user_id`.

### Fallback

Khi AI Service lỗi, `RecommendationService` ghi log warning và gọi
`ProductRepository::getSameCategoryExcept()` để lấy các sản phẩm cùng danh mục.

---

## 4. Cấu hình AI Service

Cấu hình trong `config/services.php`, đọc từ `.env`:

```php
'ai' => [
    'url'     => env('AI_SERVICE_URL', 'http://localhost:8001'),
    'timeout' => env('AI_SERVICE_TIMEOUT', 30),
],
```

| Biến môi trường | Mặc định | Mô tả |
|---|---|---|
| `AI_SERVICE_URL` | `http://localhost:8001` | Base URL của AI Service |
| `AI_SERVICE_TIMEOUT` | `30` (giây) | Timeout HTTP cho Visual Search |

> Timeout của Recommendations được hardcode là `10` giây trong `RecommendationService`
> để trang sản phẩm không bị chờ lâu.

---

## 5. Nâng cấp lên Production

Phần này mô tả những gì cần thay thế trong AI Service để chuyển từ demo sang production.

### Visual Search

Thay phần shuffle demo bằng pipeline embedding thực:

1. **Lúc khởi động** (`startup_event`): load model CLIP hoặc ResNet một lần, đọc toàn bộ
   embeddings từ bảng `product_embeddings` vào memory.
2. **Khi nhận request**: trích xuất embedding của ảnh upload, tính cosine similarity với
   tất cả product embeddings, trả về top-K.

```python
# Ví dụ cấu trúc production (ai-service/routers/visual_search.py)
@router.on_event("startup")
async def load_model():
    app.state.model = load_clip_model()
    app.state.embeddings = load_product_embeddings_from_db()

@router.post("/visual-search")
async def visual_search(image: UploadFile = File(...)):
    embedding = extract_embedding(app.state.model, await image.read())
    scores = cosine_similarity(embedding, app.state.embeddings)
    top_k = get_top_k(scores, k=10)
    return {"products": top_k}
```

### Recommendations

Thay phần shuffle demo bằng một trong hai phương pháp:

- **Embedding similarity:** tính cosine similarity giữa embedding của `product_id` với
  các sản phẩm còn lại trong bảng `product_embeddings`.
- **Collaborative filtering:** phân tích bảng `order_items` để tìm "người mua X cũng
  mua Y", kết hợp `user_id` để cá nhân hoá.

Bảng `product_embeddings` (đã có trong schema) lưu vector đặc trưng cho mỗi sản phẩm,
sẵn sàng cho cả hai phương pháp trên.
