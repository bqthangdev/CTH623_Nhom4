# Hướng Dẫn Khởi Chạy Dự Án SmartShop

> Hướng dẫn chi tiết để cài đặt và khởi chạy SmartShop trên môi trường local.

---

## Yêu cầu hệ thống

| Phần mềm | Phiên bản tối thiểu |
|---|---|
| PHP | 8.2+ |
| Composer | 2.x |
| Node.js | 18+ |
| npm | 9+ |
| MySQL | 8.0+ |
| Python | 3.10+ |
| pip | 23+ |

---

## Bước 1 — Lấy mã nguồn

```bash
git clone <repository-url> smartshop
cd smartshop
```

---

## Bước 2 — Cài đặt PHP dependencies

```bash
composer install
```

---

## Bước 3 — Cấu hình môi trường

```bash
cp .env.example .env
php artisan key:generate
```

Mở file `.env` và cập nhật thông tin kết nối database:

```env
APP_NAME=SmartShop
APP_LOCALE=vi
APP_FAKER_LOCALE=vi_VN

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=smartshop
DB_USERNAME=root
DB_PASSWORD=

# URL của AI microservice
AI_SERVICE_URL=http://localhost:8001
AI_SERVICE_TIMEOUT=30
```

> **Lưu ý:** Nếu MySQL đang chạy trên cổng khác (ví dụ XAMPP dùng 3307), hãy thay `DB_PORT` cho phù hợp.

---

## Bước 4 — Tạo database

Đăng nhập vào MySQL và tạo database:

```sql
CREATE DATABASE smartshop CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Hoặc dùng lệnh:

```bash
mysql -u root -p -e "CREATE DATABASE smartshop CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

---

## Bước 5 — Migrate và seed dữ liệu mẫu

```bash
php artisan migrate --seed
```

Lệnh này sẽ tạo toàn bộ 14 bảng và chèn dữ liệu mẫu bao gồm:
- 1 tài khoản admin và 1 tài khoản khách hàng
- 6 danh mục sản phẩm (Thời trang, Điện tử, Gia dụng, Sách, Thể thao, Làm đẹp)
- 16 sản phẩm mẫu
- 3 mã giảm giá

---

## Bước 6 — Tạo symlink cho storage

```bash
php artisan storage:link
```

---

## Bước 7 — Cài đặt và build frontend assets

```bash
npm install
npm run build
```

Trong quá trình phát triển, dùng watch mode để tự động build khi thay đổi file:

```bash
npm run dev
```

---

## Bước 8 — Khởi chạy Laravel server

```bash
php artisan serve
```

Server sẽ chạy tại **http://localhost:8000**.

---

## Bước 9 — Khởi chạy AI Service

Mở một terminal mới, truy cập thư mục `ai-service`:

```bash
cd ai-service
pip install -r requirements.txt
uvicorn main:app --reload --port 8001
```

AI Service sẽ chạy tại **http://localhost:8001**.

> **Gợi ý:** Nên dùng virtual environment Python:
> ```bash
> python -m venv .venv
> .venv\Scripts\activate    # Windows
> source .venv/bin/activate  # Linux/macOS
> pip install -r requirements.txt
> ```

---

## Tài khoản mặc định

| Vai trò | Email | Mật khẩu |
|---|---|---|
| Admin | admin@smartshop.local | password |
| Khách hàng | customer@smartshop.local | password |

---

## URL truy cập

| Phân hệ | URL |
|---|---|
| Shop (trang chủ) | http://localhost:8000 |
| Admin Panel | http://localhost:8000/admin |
| AI Service API docs | http://localhost:8001/docs |

---

## Chạy tests

```bash
# Chạy toàn bộ test suite (dùng SQLite in-memory)
php artisan test

# Chạy test theo filter
php artisan test --filter CartTest

# Chạy với coverage report
php artisan test --coverage
```

> Tests sử dụng SQLite in-memory, không ảnh hưởng đến database local của bạn.

---

## Xử lý Queue Jobs (tùy chọn)

Trong môi trường local, queue driver mặc định là `database`. Để xử lý jobs:

```bash
php artisan queue:work
```

---

## Lệnh hữu ích

| Lệnh | Mục đích |
|---|---|
| `php artisan migrate:fresh --seed` | Reset database và seed lại toàn bộ dữ liệu |
| `php artisan cache:clear` | Xóa cache ứng dụng |
| `php artisan route:list` | Xem danh sách tất cả routes |
| `php artisan tinker` | Mở REPL tương tác với ứng dụng |
