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
   ├── POST /api/embeddings/compute
   ├── POST /api/embeddings/store
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
| `POST` | `/api/visual-search` | Endpoint JSON cho AJAX (yêu cầu Sanctum auth, throttle 20/phút) |

### AI Service endpoints

**`POST /api/visual-search`** — nhận `multipart/form-data` với field `image`.

```json
// Response
{
    "products": [
        { "id": 5,  "similarity_score": 0.9821 },
        { "id": 12, "similarity_score": 0.9643 },
        { "id": 3,  "similarity_score": 0.9105 }
    ],
    "detected_object": "Điện thoại",
    "embedding_method": "CLIP ViT-B-32 (512-dim)"
}
```

Danh sách trả về tối đa **10 sản phẩm**, sắp xếp theo `similarity_score` giảm dần,
và **chỉ bao gồm sản phẩm đạt ngưỡng tương đồng tối thiểu** (`VISUAL_SEARCH_THRESHOLD`,
mặc định **0.60** với CLIP, **0.55** với histogram fallback). Nếu không có sản phẩm nào vượt ngưỡng — hoặc chưa có embedding trong DB
— endpoint trả về danh sách rỗng và Laravel fallback về sản phẩm nổi bật.

**`POST /api/embeddings/compute`** — nhận `multipart/form-data` với field `image`.
Tính và trả về vector embedding (dạng JSON) mà **không lưu vào DB**.
Sử dụng kết hợp với `/store` để tập hợp embedding từ nhiều ảnh trước khi tính trung bình.

**`POST /api/embeddings/store`** — nhận JSON `{product_id, embedding: [...]}`.  
Lưu vector embedding đã tính sẵn vào DB và cập nhật cache in-memory.

**`POST /api/embeddings/generate?product_id={id}`** — nhận `multipart/form-data` với
field `image`. Tính embedding cho một ảnh và lưu trực tiếp (legacy). Vẫn hoạt động
nhưng khướng nghị dùng luồng compute → average → store cho sản phẩm nhiều ảnh.

### Thuật toán embedding

Vector đặc trưng là **CLIP image embedding chuẩn hoá L2** (512 chiều):

| Thành phần | Model | Chiều |
|---|---|---|
| CLIP ViT-B/32 image encoder | `openai/ViT-B-32` | 512 |
| Fallback: spatial color histogram | (không có GPU/torch) | 576 |

- Ảnh được encode qua **CLIP ViT-B/32** — mô hình vision-language pre-trained trên
  400 triệu cặp ảnh-văn bản từ internet. Embedding nắm bắt nội dung ngữ nghĩa
  (hình dạng, loại đồ vật, ngữ cảnh) thay vì chỉ màu sắc pixel.
- Nếu `open-clip-torch` / `torch` chưa được cài, service tự động fallback về
  576-dim spatial color histogram (không cần thay đổi code).

Độ tương đồng giữa ảnh truy vấn và từng sản phẩm được tính bằng **cosine similarity**
trên ma trận embedding đã load vào bộ nhớ lúc khởi động. Chỉ sản phẩm có score
≥ `SIMILARITY_THRESHOLD` (mặc định **0.60** với CLIP) mới được đưa vào kết quả.

### Nhận diện đồ vật (Zero-Shot Classification)

Song song với tìm kiếm, service phân loại ảnh upload để trả về tên danh mục
sản phẩm được nhận diện (`detected_object`) — hiển thị ngay trước kết quả tìm kiếm.

**Luồng:**
1. Khi khởi động, service đọc tất cả tên danh mục từ DB và encode chúng
   thành vector 512-dim bằng CLIP **text encoder** (dùng prompt tiếng Anh mô tả
   danh mục để đảm bảo CLIP hiểu đúng ngữ nghĩa).
2. Khi search, tính **dot-product similarity** giữa embedding ảnh và tất cả
   vector văn bản → **softmax** → tắt cả xác suất → danh mục tạt cao nhất.
3. Nếu xác suất cao nhất vượt ngưỡng (tối thiểu 15%) thì trả về tên danh mục,
   ngược lại trả về `null`.

#### Điều chỉnh ngưỡng tương đồng

Thêm vào file `.env` của AI Service:

```env
VISUAL_SEARCH_THRESHOLD=0.60   # Giảm xuống (0.50) để kết quả rộng hơn,
                                # Tăng lên (0.75) để kết quả khắt khe hơn
CLIP_MODEL=ViT-B-32             # Hoặc ViT-L-14 để chính xác hơn (chậm hơn)
```

### Sinh embeddings cho sản phẩm

> **Lưu ý:** Lần đầu chạy sau khi cài `open-clip-torch`, AI Service sẽ tự động tải
> model CLIP ViT-B/32 (~350 MB) về cache của hệ thống. Cần kết nối internet.

Sau khi chạy `php artisan migrate --seed` và khởi động AI Service, chạy:

```bash
pip install -r ai-service/requirements.txt  # cài open-clip-torch nếu chưa có
php artisan embeddings:generate
```

Lệnh này xử lý từng sản phẩm theo luồng:
1. Gọi `/api/embeddings/compute` cho **mỗi ảnh** — trả về vector CLIP 512-dim.
2. Tính **trung bình cộng** các vector (PHP), sau đó chuẩn hoá L2.
3. Lưu vector trung bình vào DB qua `/api/embeddings/store`.

Do embedding là trung bình tất cả ảnh của sản phẩm, tìm kiếm bằng bất kỳ ảnh nào của
sản phẩm đều cho score cao — khắc phục trưỜng hợp sản phẩm có nhiều góc chụp khác nhau.

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

**Similar products:**
1. Tính cosine similarity giữa embedding CLIP của `product_id` và tất cả sản phẩm còn lại.
2. Lọc bỏ kết quả có score < `RECOMMENDATION_THRESHOLD` (mặc định **0.40**).
3. Áp dụng **category diversity** — tối đa `MAX_PER_CATEGORY` (mặc định **2**) sản phẩm
   mỗi danh mục để tránh kết quả đơn điệu.
4. Trả về top-`limit` sau khi đa dạng hóa.

**Personalized:**
1. Truy vấn `order_items JOIN orders` để lấy lịch sử mua kèm ngày đặt hàng.
2. Xây dựng **recency-weighted taste profile**: mỗi embedding sản phẩm đã mua được nhân
   với trọng số `exp(-days_old / 30)` — sản phẩm mua gần đây đóng góp nhiều hơn.
3. Chuẩn hoá L2 vector trung bình có trọng số.
4. Tính cosine similarity với tất cả sản phẩm chưa mua → lọc theo threshold → đa dạng hóa.

**Xử lý sparse history (cold-start):** Khi người dùng có ít hơn 3 sản phẩm đã mua
có embedding, taste profile không đủ đáng tin cậy. Hệ thống tự động **pha trộn**
cosine similarity với tín hiệu **phổ biến toàn site** (`popularity blending`):

| Số sản phẩm đã mua | Trọng số similarity | Trọng số popularity |
|---|---|---|
| 1 | 70% | 30% |
| 2 | 85% | 15% |
| ≥ 3 | 100% | 0% |

Threshold vẫn áp dụng trên **cosine similarity thuần** (không phải điểm blended)
để đảm bảo ngưỡng chất lượng ngữ nghĩa tối thiểu.

**Lưu ý:** Laravel giữ nguyên thứ tự mà AI trả về khi map sang Eloquent Collection:

```php
// RecommendationService::fetchOrdered()
$idOrder  = array_flip($ids);
return $this->productRepository->getByIds($ids)
    ->sortBy(fn (Product $product) => $idOrder[$product->id] ?? PHP_INT_MAX)
    ->values();
```

### Encoding kết quả

Cả hai endpoint trả về trường `embedding_method` (giống Visual Search) để theo dõi
encoder đang hoạt động:

```json
{
    "recommended_products": [
        { "id": 7, "score": 0.8921 },
        { "id": 2, "score": 0.8530 }
    ],
    "embedding_method": "CLIP ViT-B-32 (512-dim)"
}
```

### Fallback

- **Similar products:** fallback về `getSameCategoryExcept()` (cùng danh mục, random)
  khi AI trả lỗi hoặc danh sách rỗng (tất cả sản phẩm dưới ngưỡng threshold).
- **Personalized:** fallback về `getFeatured()` (sản phẩm nổi bật) khi AI lỗi,
  người dùng chưa có lịch sử mua, hoặc không đủ sản phẩm vượt ngưỡng.

#### Điều chỉnh ngưỡng và diversity

Thêm vào file `.env` của AI Service:

```env
RECOMMENDATION_THRESHOLD=0.40  # Giảm (0.30) để có nhiều kết quả hơn,
                                 # Tăng (0.55) để chỉ lấy kết quả rất tương tự
```

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

## 5. Nâng cấp độ chính xác thêm

Phần này mô tả những cải tiến có thể thực hiện để tiếp tục nâng cao chất lượng.

### Visual Search

CLIP ViT-B/32 hoạt động tốt với hầu hết loại sản phẩm. Để cải thiện thêm:

- **CLIP ViT-L/14:** đặt `CLIP_MODEL=ViT-L-14` trong `.env` — vector 768-dim, chính
  xác hơn nhưng tốc độ chậm hơn. Sau khi đổi model, chạy lại `php artisan
  embeddings:generate` để tái tạo toàn bộ embedding.
- **CLIP đa ngôn ngữ (mCLIP):** hữu ích nếu tên sản phẩm viết bằng tiếng Việt —
  dùng `multilingual-clip` từ HuggingFace.
- **Image + text search:** kết hợp CLIP image embedding (tìm theo ảnh) với CLIP
  text embedding của tên sản phẩm (tìm theo từ khóa) trong cùng một vector space.

### Recommendations

- **Đã triển khai:**
  - Score threshold (`RECOMMENDATION_THRESHOLD`) — loại bỏ sản phẩm không đủ tương đồng.
  - Recency-weighted taste profile — mua gần đây ảnh hưởng nhiều hơn.
  - Category diversity — tối đa 2 sản phẩm/danh mục.
  - **Popularity blending** — giảm cold-start khi người dùng có ít lịch sử mua.
- **Cải tiến tiếp theo:**
  - **CLIP ViT-L/14:** cùng cấu hình như Visual Search — phần chất lượng embedding
    cải thiện tự động cho cả hai tính năng.
  - **Collaborative filtering chuyên sâu:** dùng matrix factorization (SVD, ALS) trên
    toàn bộ `order_items` để nắm bắt pattern "người mua X cũng mua Y" ở quy mô lớn.
