# CTH623 - Nhóm 4
Môn học: Tiếp thị & Kinh doanh Kỹ thuật số
Dự án MedusaJS - E-commerce Platform

## Yêu cầu hệ thống
- Node.js 18+ 
- Docker và Docker Compose
- npm hoặc yarn

### Khởi động Database
```bash
docker-compose up -d
```

### Chạy migrations (ghi chú, chưa rõ có dùng không)
```bash
npx medusa migrations run
```

### Tạo admin user (ghi chú, chưa rõ có dùng không)
```bash
npx medusa user -e admin@medusa-test.com -p supersecret
```

## Thông tin Database

- **Host:** localhost
- **Port:** 5432
- **Database:** cth623_nhom4
- **User:** postgres
- **Password:** postgres
- **Database URL:** DATABASE_URL=postgres://postgres:postgres@localhost:5432/cth623_nhom4
