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
- Thanh toán với mã giảm giá, chọn phương thức COD / VNPay
- Quản lý đơn hàng, đánh giá sản phẩm
- Xác thực người dùng (đăng ký, đăng nhập, cập nhật hồ sơ)

### Phân hệ Quản trị (`/admin`)
- Dashboard tổng quan (doanh thu, đơn hàng, khách hàng)
- Quản lý danh mục, sản phẩm (CRUD + hình ảnh + thuộc tính)
- Quản lý đơn hàng (cập nhật trạng thái, xem chi tiết)
- Quản lý mã giảm giá (voucher)
- Quản lý banner trang chủ
- Quản lý khách hàng (kích hoạt / vô hiệu hóa)

---

## Tech Stack

| Thành phần | Công nghệ |
|---|---|
| Backend | PHP 8.2+, Laravel 12 |
| Frontend | Blade, Alpine.js, Tailwind CSS 3 |
| Database | MySQL 8 |
| Auth | Laravel Breeze (session) + Sanctum (API) |
| Queue | Laravel Queue (database driver) |
| AI Service | Python 3.11 + FastAPI (port 8001) |
| Real-time | Livewire 3 |

---

## Cấu trúc dự án

```
smartshop/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Admin/          # Controllers phân hệ quản trị
│   │   │   ├── Api/            # API Controllers (cart, order, visual search)
│   │   │   ├── Auth/           # Xác thực (Breeze)
│   │   │   └── Shop/           # Controllers phân hệ khách hàng
│   │   ├── Middleware/
│   │   └── Requests/
│   │       ├── Admin/          # Form Requests cho admin
│   │       └── Shop/           # Form Requests cho shop
│   ├── Models/                 # 14 Eloquent models
│   ├── Repositories/           # ProductRepository, OrderRepository
│   ├── Services/               # CartService, OrderService, ProductService,
│   │                           # RecommendationService, VisualSearchService
│   └── View/                   # View Composers
├── ai-service/                 # Microservice Python FastAPI
│   ├── main.py
│   ├── requirements.txt
│   └── routers/                # visual_search, recommendations
├── database/
│   ├── factories/              # Model factories
│   ├── migrations/             # 7 migration files, 14 tables
│   └── seeders/                # UserSeeder, CategorySeeder, ProductSeeder, VoucherSeeder
├── docs/
│   ├── CODING_STANDARDS.md
│   ├── TESTING_GUIDELINES.md
│   └── HUONG_DAN_KHOI_CHAY.md # Hướng dẫn khởi chạy dự án
├── resources/
│   └── views/
│       ├── admin/              # ~20 Blade views cho admin
│       ├── auth/               # Views xác thực
│       ├── components/         # Shared Blade components
│       ├── layouts/            # app.blade.php, admin.blade.php
│       └── shop/               # ~9 Blade views cho shop
└── routes/
    ├── web.php                 # Web routes (shop + admin)
    └── api.php                 # API routes (v1/cart, v1/orders, v1/visual-search)
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

### URL truy cập

| Phân hệ | URL |
|---|---|
| Shop (trang chủ) | http://localhost:8000 |
| Admin Panel | http://localhost:8000/admin |
| AI Service (API docs) | http://localhost:8001/docs |

---

## Database

14 bảng được tổ chức theo nhóm:

| Nhóm | Bảng |
|---|---|
| Người dùng | `users` |
| Danh mục | `categories` |
| Sản phẩm | `products`, `product_images`, `product_attributes`, `product_embeddings` |
| Đơn hàng | `vouchers`, `orders`, `order_items` |
| Tương tác | `cart_items`, `wishlists`, `reviews`, `banners`, `user_activities` |

---

## Tài liệu kỹ thuật

- [Quy tắc lập trình](docs/CODING_STANDARDS.md)
- [Hướng dẫn kiểm thử](docs/TESTING_GUIDELINES.md)
- [Tài liệu Chức năng AI](docs/AI_FEATURES.md)
- [Hướng dẫn khởi chạy](docs/HUONG_DAN_KHOI_CHAY.md)
