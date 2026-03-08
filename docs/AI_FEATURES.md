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
   ├── POST /api/embeddings/generate
   ├── GET  /api/recommendations/similar
   └── GET  /api/recommendations/personal
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
| Artisan command – sinh embeddings | `app/Console/Commands/GenerateProductEmbeddings.php` |

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

### AI Service endpoints

**`POST /api/visual-search`** — nhận `multipart/form-data` với field `image`.

```json
// Response
{
    "products": [
        { "id": 5,  "similarity_score": 0.9821 },
        { "id": 12, "similarity_score": 0.9643 },
        { "id": 3,  "similarity_score": 0.9105 }
    ]
}
```

Danh sách trả về tối đa **10 sản phẩm**, sắp xếp theo `similarity_score` giảm dần.
Nếu chưa có embedding nào trong DB, trả về danh sách rỗng và Laravel fallback về
sản phẩm nổi bật.

**`POST /api/embeddings/generate?product_id={id}`** — nhận `multipart/form-data` với
field `image`. Tính embedding cho ảnh sản phẩm và lưu vào DB, sau đó cập nhật
cache in-memory. Được gọi bởi `php artisan embeddings:generate`.

### Thuật toán embedding

Vector đặc trưng là **color histogram chuẩn hoá L2** (192 chiều):
- Ảnh được resize về 224×224 pixel và chuyển sang RGB.
- Tính histogram 64 bins cho mỗi trong 3 kênh màu (R, G, B).
- Ghép 3 histogram thành vector 192 chiều, chuẩn hoá theo L2-norm.

Độ tương đồng giữa ảnh truy vấn và từng sản phẩm được tính bằng **cosine similarity**
trên ma trận embedding đã load vào bộ nhớ lúc khởi động.

### Sinh embeddings cho sản phẩm

Sau khi chạy `php artisan migrate --seed` và khởi động AI Service, chạy:

```bash
php artisan embeddings:generate
```

Lệnh này đọc ảnh đầu tiên của từng sản phẩm từ storage, gọi endpoint
`/api/embeddings/generate`, và lưu kết quả vào bảng `product_embeddings`.

### Fallback

Khi AI Service lỗi, `VisualSearchService` bắt exception, ghi log warning, và gọi
`ProductRepository::getFeatured(10)` để trả về sản phẩm nổi bật thay thế. Người dùng
vẫn nhìn thấy danh sách sản phẩm, không báo lỗi.

---

## 3. Gợi ý sản phẩm (Recommendations)

### Mô tả

Có hai chế độ gợi ý riêng biệt:

| Vị trí | Chế độ | AI endpoint |
|---|---|---|
| Trang chi tiết sản phẩm | Sản phẩm tương tự | `GET /api/recommendations/similar` |
| Trang chủ (người dùng đã đăng nhập) | Cá nhân hoá theo lịch sử mua | `GET /api/recommendations/personal` |

### Luồng xử lý — Sản phẩm tương tự (trang chi tiết)

```
Người dùng xem trang sản phẩm
        │
        ▼
[Web] GET /products/{product:slug}
 Shop\ProductController@show
 ├── Load product (images, category, attributes, reviews)
 ├── Ghi nhận lượt xem: ProductService::recordView()
 └── Gọi RecommendationService::getForProduct(product, limit)
             │
             ├─ [Thành công]
             │   GET /api/recommendations/similar?product_id=X&limit=8
             │   ← { recommended_products: [{id, score}, ...] }
             │   → ProductRepository::getByIds() (giữ thứ tự AI)
             │   └── Trả về view shop.products.show với $recommendations
             │
             └─ [Thất bại / timeout]
                 Log warning
                 Fallback: ProductRepository::getSameCategoryExcept()
```

### Luồng xử lý — Cá nhân hoá (trang chủ)

```
Người dùng đã đăng nhập truy cập trang chủ
        │
        ▼
[Web] GET /
 Shop\HomeController@index
 ├── Banner::active(), ProductRepository::getFeatured(8)
 └── RecommendationService::getPersonalized(userId, 8)
             │
             ├─ [Thành công]
             │   GET /api/recommendations/personal?user_id=Y&limit=8
             │   ← { recommended_products: [{id, score}, ...] }
             │   → ProductRepository::getByIds() (giữ thứ tự AI)
             │   └── Trả về view shop.home với $personalizedProducts
             │
             └─ [Thất bại / không có lịch sử]
                 Log warning / trả về danh sách rỗng
                 Fallback: ProductRepository::getFeatured()
                 └── Mục "Gợi ý cho bạn" ẩn nếu danh sách rỗng
```

> Người dùng chưa đăng nhập: `$personalizedProducts` là `collect()` rỗng, mục
> "Gợi ý cho bạn" không hiển thị trên trang chủ.

### Routes

| Method | URI | Mục đích |
|---|---|---|
| `GET` | `/api/products/{product}/recommendations` | Endpoint JSON — sản phẩm tương tự |

### AI Service endpoints

**`GET /api/recommendations/similar`** — query params:

| Param | Kiểu | Bắt buộc | Mô tả |
|---|---|---|---|
| `product_id` | `int` | Có | ID sản phẩm cần tìm tương tự |
| `limit` | `int` | Không (mặc định 8) | Số lượng kết quả |

**`GET /api/recommendations/personal`** — query params:

| Param | Kiểu | Bắt buộc | Mô tả |
|---|---|---|---|
| `user_id` | `int` | Có | ID người dùng |
| `limit` | `int` | Không (mặc định 8) | Số lượng kết quả |

```json
// Response (cả hai endpoint)
{
    "recommended_products": [
        { "id": 7,  "score": 0.9821 },
        { "id": 2,  "score": 0.9530 },
        { "id": 14, "score": 0.9104 }
    ]
}
```

### Thuật toán

**Similar products:** cosine similarity giữa embedding của `product_id` và tất cả sản
phẩm còn lại trong cache in-memory.

**Personalized:** truy vấn `order_items` để lấy danh sách sản phẩm đã mua → tính
vector trung bình (taste profile) → cosine similarity với tất cả sản phẩm chưa mua
→ top-K.

**Lưu ý:** Laravel giữ nguyên thứ tự mà AI trả về khi map sang Eloquent Collection:

```php
// RecommendationService::fetchOrdered()
$products = $this->productRepository->getByIds($ids)->keyBy('id');
return collect($ids)->map(fn ($id) => $products[$id] ?? null)->filter()->values();
```

### Fallback

- **Similar products:** fallback về `getSameCategoryExcept()` (cùng danh mục, random).
- **Personalized:** fallback về `getFeatured()` (sản phẩm nổi bật). Nếu AI không có
  embedding nào trong cache, trả về danh sách rỗng → Laravel fallback tự động.

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

## 5. Nâng cấp độ chính xác

Phần này mô tả những cải tiến có thể thực hiện để nâng cao chất lượng tìm kiếm.

### Visual Search

Color histogram hoạt động tốt với sự tương đồng về màu sắc, nhưng bỏ qua hình dạng
và ngữ nghĩa. Để cải thiện:

- **Deep feature extractor:** thay `_extract_embedding()` bằng MobileNetV2 hoặc
  EfficientNet từ `torchvision`. Vector đặc trưng từ penultimate layer (~1280 chiều)
  nắm bắt được hình dạng và ngữ nghĩa sản phẩm.
- **CLIP embeddings:** dùng `open-clip-torch` để có embedding đa phương thức
  (ảnh + văn bản), hữu ích khi muốn tìm kiếm bằng cả ảnh lẫn từ khoá.

Kiến trúc tổng thể (startup, cosine similarity, fallback) **không cần thay đổi** —
chỉ cần thay hàm `_extract_embedding()` trong `visual_search.py`.

### Recommendations

Thay phần shuffle demo bằng một trong hai phương pháp nâng cao hơn:

- **Embedding similarity:** đã triển khai. Có thể nâng độ chính xác bằng cách dùng
  deep feature extractor (MobileNetV2, CLIP) thay color histogram — xem mục Visual Search.
- **Collaborative filtering chuyên sâu:** dùng matrix factorization (SVD, ALS) trên
  toàn bộ `order_items` để nắm bắt pattern "người mua X cũng mua Y" ở quy mô lớn hơn.
