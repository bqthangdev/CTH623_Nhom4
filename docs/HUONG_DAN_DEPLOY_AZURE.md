# Hướng Dẫn Deploy SmartShop lên Azure VPS

> Hướng dẫn triển khai SmartShop lên Azure Virtual Machine chạy **Ubuntu 22.04 LTS**.

---

## AI Service có bắt buộc phải chạy liên tục không?

**Không bắt buộc, nhưng nên có.**

Cả `VisualSearchService` và `RecommendationService` đều có cơ chế **fallback tự động** khi AI service không khả dụng:
- Visual search → trả về sản phẩm nổi bật
- Recommendations → trả về sản phẩm cùng danh mục

Tuy nhiên, để tính năng AI hoạt động đầy đủ, nên cấu hình AI service chạy liên tục bằng **Supervisor** (hướng dẫn ở Bước 7.3).

---

## Yêu cầu Azure VM

| Thông số | Khuyến nghị tối thiểu |
|---|---|
| Image | Ubuntu Server 22.04 LTS |
| Size | Standard B2s (2 vCPU, 4 GB RAM) trở lên |
| Disk | 30 GB SSD |
| Inbound ports | 22 (SSH), 80 (HTTP), 443 (HTTPS) |

**Lưu ý bảo mật:** Trong Azure Portal → VM → Networking, chỉ mở port 22 cho IP của bạn, không mở công khai.

---

## Bước 1 — Kết nối vào VM

```bash
ssh azureuser@<YOUR_VM_IP>
```

---

## Bước 2 — Cài đặt phần mềm cần thiết

### 2.1 Cập nhật hệ thống

```bash
sudo apt update && sudo apt upgrade -y
```

### 2.2 Cài PHP 8.2

```bash
sudo apt install -y software-properties-common
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update
sudo apt install -y php8.2 php8.2-fpm php8.2-mysql php8.2-mbstring \
    php8.2-xml php8.2-curl php8.2-zip php8.2-gd php8.2-intl \
    php8.2-bcmath php8.2-redis php8.2-sqlite3
```

### 2.3 Cài Composer

```bash
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

### 2.4 Cài Nginx

```bash
sudo apt install -y nginx
```

### 2.5 Cài MySQL 8

```bash
sudo apt install -y mysql-server
sudo mysql_secure_installation
```

Tạo database và user:

```bash
sudo mysql -u root -p
```

```sql
CREATE DATABASE smartshop CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'smartshop'@'localhost' IDENTIFIED BY 'STRONG_PASSWORD_HERE';
GRANT ALL PRIVILEGES ON smartshop.* TO 'smartshop'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### 2.6 Cài Node.js 20

```bash
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs
```

### 2.7 Cài Python 3.11

```bash
sudo apt install -y python3.11 python3.11-venv python3-pip
```

### 2.8 Cài Supervisor (quản lý process)

```bash
sudo apt install -y supervisor
```

---

## Bước 3 — Deploy mã nguồn

```bash
sudo mkdir -p /var/www/smartshop
sudo chown azureuser:www-data /var/www/smartshop

cd /var/www/smartshop
git clone <repository-url> .
```

### Cài đặt PHP dependencies (không bao gồm dev packages)

```bash
composer install --no-dev --optimize-autoloader
```

### Cài đặt và build frontend assets

```bash
npm ci
npm run build
rm -rf node_modules
```

---

## Bước 4 — Cấu hình môi trường

```bash
cp .env.example .env
php artisan key:generate
```

Chỉnh sửa file `.env`:

```bash
nano .env
```

```env
APP_NAME=SmartShop
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

APP_LOCALE=vi
APP_FAKER_LOCALE=vi_VN

LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=smartshop
DB_USERNAME=smartshop
DB_PASSWORD=STRONG_PASSWORD_HERE

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database

AI_SERVICE_URL=http://127.0.0.1:8001
AI_SERVICE_TIMEOUT=30
```

---

## Bước 5 — Thiết lập quyền và storage

```bash
# Quyền thư mục
sudo chown -R azureuser:www-data /var/www/smartshop
sudo chmod -R 755 /var/www/smartshop
sudo chmod -R 775 /var/www/smartshop/storage
sudo chmod -R 775 /var/www/smartshop/bootstrap/cache

# Symlink storage
php artisan storage:link
```

---

## Bước 6 — Migrate và seed database

```bash
cd /var/www/smartshop
php artisan migrate --force --seed
```

### Tối ưu cache cho production

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## Bước 7 — Cấu hình các service

### 7.1 Nginx

Tạo file cấu hình:

```bash
sudo nano /etc/nginx/sites-available/smartshop
```

```nginx
server {
    listen 80;
    server_name yourdomain.com www.yourdomain.com;
    root /var/www/smartshop/public;
    index index.php;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    client_max_body_size 10M;
}
```

Kích hoạt site:

```bash
sudo ln -s /etc/nginx/sites-available/smartshop /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

### 7.2 Cài SSL với Let's Encrypt

```bash
sudo apt install -y certbot python3-certbot-nginx
sudo certbot --nginx -d yourdomain.com -d www.yourdomain.com
```

Certbot sẽ tự động cập nhật cấu hình Nginx và thêm HTTPS. Kiểm tra tự động gia hạn:

```bash
sudo certbot renew --dry-run
```

### 7.3 AI Service với Supervisor

Tạo virtual environment cho Python:

```bash
cd /var/www/smartshop/ai-service
python3.11 -m venv .venv
source .venv/bin/activate
pip install -r requirements.txt
deactivate
```

Tạo cấu hình Supervisor:

```bash
sudo nano /etc/supervisor/conf.d/smartshop-ai.conf
```

```ini
[program:smartshop-ai]
command=/var/www/smartshop/ai-service/.venv/bin/uvicorn main:app --host 127.0.0.1 --port 8001 --workers 2
directory=/var/www/smartshop/ai-service
user=azureuser
autostart=true
autorestart=true
stderr_logfile=/var/log/supervisor/smartshop-ai.err.log
stdout_logfile=/var/log/supervisor/smartshop-ai.out.log
```

### 7.4 Laravel Queue Worker với Supervisor

```bash
sudo nano /etc/supervisor/conf.d/smartshop-queue.conf
```

```ini
[program:smartshop-queue]
command=php /var/www/smartshop/artisan queue:work --sleep=3 --tries=3 --max-time=3600
directory=/var/www/smartshop
user=azureuser
numprocs=1
autostart=true
autorestart=true
stderr_logfile=/var/log/supervisor/smartshop-queue.err.log
stdout_logfile=/var/log/supervisor/smartshop-queue.out.log
```

Khởi động Supervisor:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start all
sudo supervisorctl status
```

---

## Bước 8 — Kiểm tra

```bash
# Kiểm tra Nginx
sudo systemctl status nginx

# Kiểm tra PHP-FPM
sudo systemctl status php8.2-fpm

# Kiểm tra AI service
sudo supervisorctl status smartshop-ai

# Kiểm tra queue worker
sudo supervisorctl status smartshop-queue

# Kiểm tra Laravel
cd /var/www/smartshop && php artisan about
```

Truy cập `https://yourdomain.com` để xác nhận website hoạt động.

---

## Cập nhật code (Deployment mới)

Khi có code mới, chạy trình tự sau:

```bash
cd /var/www/smartshop

git pull origin main

composer install --no-dev --optimize-autoloader

php artisan migrate --force

php artisan config:cache
php artisan route:cache
php artisan view:cache

sudo supervisorctl restart smartshop-queue
```

---

## Xử lý sự cố thường gặp

| Vấn đề | Kiểm tra |
|---|---|
| 500 Internal Server Error | `tail -n 50 storage/logs/laravel.log` |
| Nginx 502 Bad Gateway | `sudo systemctl status php8.2-fpm` |
| AI service không phản hồi | `sudo supervisorctl status smartshop-ai` → `curl http://127.0.0.1:8001/health` |
| Queue không xử lý | `sudo supervisorctl status smartshop-queue` |
| Permission denied | `sudo chown -R azureuser:www-data storage bootstrap/cache` |
