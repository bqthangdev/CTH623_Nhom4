# SmartShop — Website Thương Mại Điện Tử

Dự án học phần **CT573HT — Tiếp thị và Kinh doanh Kỹ thuật số**, Thạc sĩ HTTT K32.

SmartShop là nền tảng thương mại điện tử tích hợp AI xây dựng trên Laravel 12, cung cấp trải nghiệm mua sắm thông minh với tìm kiếm bằng hình ảnh và gợi ý sản phẩm cá nhân hóa.

---

## Tính năng chính

### Phân hệ Khách hàng (`/`)
- Duyệt sản phẩm theo danh mục, tìm kiếm full-text
- **Tìm kiếm bằng hình ảnh** — tải ảnh lên để tìm sản phẩm tương tự (AI visual search)
- Gợi ý sản phẩm liên quan được cá nhân hóa bởi AI
- Giỏ hàng, danh sách yêu thích
- Thanh toán với mã giảm giá, phí vận chuyển cố định 30.000đ, chọn phương thức COD / VNPay
- Xem lịch sử đơn hàng, xem đơn vị vận chuyển và mã vận đơn, hủy đơn hàng, đánh giá sản phẩm
- Xác thực người dùng (đăng ký, đăng nhập, đặt lại mật khẩu, cập nhật hồ sơ)

### Phân hệ Quản trị (`/admin`)
- Dashboard tổng quan (doanh thu, đơn hàng, khách hàng, sản phẩm sắp hết hàng)
- Quản lý danh mục, sản phẩm (CRUD + hình ảnh + thuộc tính)
- Quản lý đơn hàng — xác nhận, giao hàng (chọn đơn vị vận chuyển + nhập mã vận đơn), hủy đơn
- Quản lý đơn vị vận chuyển (CRUD — SPX Express, Viettel Post, Vietnam Post, J&T Express)
- Quản lý mã giảm giá (voucher theo % hoặc số tiền cố định)
- Quản lý banner trang chủ
- Quản lý phương thức thanh toán (bật/tắt, cấu hình cổng thanh toán)
- Quản lý khách hàng (xem hồ sơ, kích hoạt / vô hiệu hóa)

---

## Tech Stack

| Thành phần | Công nghệ |
|---|---|
| Backend | PHP 8.2+, Laravel 12 |
| Frontend | Blade, Alpine.js, Tailwind CSS 3 |
| Database | MySQL 8 |
| Auth | Laravel Breeze (session) + Sanctum (API token) |
| Queue | Laravel Queue (database driver) |
| AI Service | Python 3.11 + FastAPI (port 8001) |
| Real-time | Livewire 4 |

---

## Cấu trúc dự án

```
smartshop/
├── app/
│   ├── Console/
│   │   └── Commands/
│   │       └── GenerateProductEmbeddings.php  # php artisan embeddings:generate
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Admin/          # 9 controllers phân hệ quản trị
│   │   │   ├── Api/            # 4 API controllers (cart, order, visual-search, recommendations)
│   │   │   ├── Auth/           # 9 controllers xác thực (Breeze)
│   │   │   └── Shop/           # 9 controllers phân hệ khách hàng
│   │   ├── Middleware/
│   │   └── Requests/
│   │       ├── Admin/          # 13 Form Requests cho admin
│   │       ├── Api/            # 2 Form Requests cho API
│   │       ├── Auth/           # LoginRequest
│   │       └── Shop/           # 6 Form Requests cho shop
│   ├── Models/                 # 16 Eloquent models
│   ├── Repositories/           # ProductRepository, OrderRepository, ShippingCarrierRepository
│   ├── Services/               # 10 Service classes:
│   │                           #   CartService, CategoryService, DashboardService,
│   │                           #   OrderService, ProductService, RecommendationService,
│   │                           #   ReviewService, ShippingCarrierService,
│   │                           #   VisualSearchService, WishlistService
│   └── View/                   # View Composers
├── ai-service/                 # Microservice Python FastAPI
│   ├── main.py                 # FastAPI app, model loading, /health endpoint
│   ├── requirements.txt
│   └── routers/
│       ├── visual_search.py    # POST /api/visual-search, /api/embeddings/*
│       └── recommendations.py  # GET /api/recommendations/similar, /personal
├── database/
│   ├── factories/              # 1 Factory per model
│   ├── migrations/             # 10 migration files → 16 bảng ứng dụng
│   └── seeders/                # 8 seeders (User, Category, Product, Banner,
│                               #            PaymentMethod, ShippingCarrier,
│                               #            Order, DatabaseSeeder)
├── docs/
│   ├── AI_FEATURES.md          # Tài liệu tính năng AI (CLIP, visual search, recommendations)
│   ├── CODING_STANDARDS.md     # Quy tắc lập trình dự án
│   ├── HUONG_DAN_DEPLOY_AZURE.md
│   ├── HUONG_DAN_KHOI_CHAY.md  # Hướng dẫn khởi chạy local
│   └── TESTING_GUIDELINES.md
├── resources/
│   └── views/
│       ├── admin/              # 30 Blade views cho admin (9 modules)
│       ├── auth/               # 6 views xác thực
│       ├── components/         # 15 Blade components dùng chung
│       ├── layouts/            # app.blade.php, admin.blade.php, guest.blade.php
│       ├── profile/            # 1 view + 3 partials
│       └── shop/               # 10 Blade views cho shop (7 modules)
├── routes/
│   ├── web.php                 # Web routes (shop + admin, 30+ named routes)
│   ├── api.php                 # API routes (10 endpoints)
│   └── auth.php                # Auth routes (Breeze)
└── tests/
    ├── Feature/
    │   ├── Admin/              # AuthorizationTest, ProductManagementTest
    │   ├── Auth/               # 6 test files (Authentication, Registration, Password...)
    │   └── Shop/               # CartTest, CheckoutTest, VisualSearchTest
    └── Unit/
        ├── Models/             # ProductTest
        └── Services/           # CartServiceTest, RecommendationServiceTest
```

---

## Khởi chạy nhanh

Xem [docs/HUONG_DAN_KHOI_CHAY.md](docs/HUONG_DAN_KHOI_CHAY.md) để biết hướng dẫn chi tiết.

```bash
# 1. Cài đặt dependencies
composer install
npm install && npm run build

# 2. Thiết lập môi trường
cp .env.example .env
php artisan key:generate

# 3. Migrate & seed dữ liệu mẫu
php artisan migrate --seed

# 4. Khởi chạy server
php artisan serve

# 5. Khởi chạy AI service (terminal riêng)
cd ai-service
pip install -r requirements.txt
uvicorn main:app --reload --port 8001

# 6. Sinh embeddings sản phẩm (chạy một lần sau khi seed)
php artisan embeddings:generate
```

### Tài khoản mặc định

| Vai trò | Email | Mật khẩu |
|---|---|---|
| Admin | admin@smartshop.local | password |
| Khách hàng | customer@smartshop.local | password |
| Demo (AI test) | alice@smartshop.local | password |
| Demo (AI test) | bob@smartshop.local | password |
| Demo (AI test) | carol@smartshop.local | password |
| Demo (AI test) | dave@smartshop.local | password |

> Các tài khoản demo có lịch sử mua hàng thiết kế sẵn để kiểm thử tính năng gợi ý cá nhân hóa AI.

### URL truy cập

| Phân hệ | URL |
|---|---|
| Shop (trang chủ) | http://localhost:8000 |
| Admin Panel | http://localhost:8000/admin |
| AI Service (API docs) | http://localhost:8001/docs |

---

## Database

16 bảng ứng dụng được tổ chức theo nhóm:

| Nhóm | Bảng |
|---|---|
| Người dùng | `users` |
| Danh mục | `categories` |
| Sản phẩm | `products`, `product_images`, `product_attributes`, `product_embeddings` |
| Đơn hàng | `vouchers`, `orders`, `order_items` |
| Tương tác | `cart_items`, `wishlists`, `reviews`, `banners`, `user_activities` |
| Thanh toán | `payment_methods` |
| Vận chuyển | `shipping_carriers` |

> Ngoài ra còn có các bảng hệ thống Laravel: `sessions`, `cache`, `cache_locks`, `jobs`, `job_batches`, `failed_jobs`, `password_reset_tokens`.

---

## API Endpoints

### Web API (`/api`, yêu cầu Sanctum token trừ recommendations)

| Method | Endpoint | Auth | Mô tả |
|--------|----------|------|-------|
| GET | `/api/cart` | ✓ | Lấy giỏ hàng |
| POST | `/api/cart/items` | ✓ | Thêm sản phẩm vào giỏ |
| PATCH | `/api/cart/items/{cartItem}` | ✓ | Cập nhật số lượng |
| DELETE | `/api/cart/items/{cartItem}` | ✓ | Xóa sản phẩm khỏi giỏ |
| GET | `/api/cart/count` | ✓ | Số lượng trong giỏ |
| GET | `/api/orders` | ✓ | Danh sách đơn hàng |
| GET | `/api/orders/{id}` | ✓ | Chi tiết đơn hàng |
| POST | `/api/visual-search` | ✓ | Tìm kiếm bằng hình ảnh (rate: 20/phút) |
| GET | `/api/products/{product}/recommendations` | — | Gợi ý sản phẩm (rate: 60/phút) |

### AI Service API (`http://localhost:8001/api`)

| Method | Endpoint | Mô tả |
|--------|----------|-------|
| POST | `/api/visual-search` | Tìm kiếm hình ảnh (CLIP ViT-B/32) |
| POST | `/api/embeddings/compute` | Tính embedding cho ảnh |
| POST | `/api/embeddings/store` | Lưu embedding vào DB |
| POST | `/api/embeddings/generate` | Tính và lưu embedding (legacy) |
| GET | `/api/recommendations/similar` | Sản phẩm tương tự |
| GET | `/api/recommendations/personal` | Gợi ý cá nhân hóa |
| GET | `/health` | Health check |

---

## Tài liệu kỹ thuật

- [Quy tắc lập trình](docs/CODING_STANDARDS.md)
- [Hướng dẫn kiểm thử](docs/TESTING_GUIDELINES.md)
- [Tài liệu Chức năng AI](docs/AI_FEATURES.md)
- [Hướng dẫn khởi chạy](docs/HUONG_DAN_KHOI_CHAY.md)
- [Hướng dẫn triển khai Azure](docs/HUONG_DAN_DEPLOY_AZURE.md)
