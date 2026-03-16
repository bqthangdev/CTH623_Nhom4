# Google Analytics 4 — Hướng dẫn tích hợp

Tài liệu mô tả cách SmartShop tích hợp Google Analytics 4 (GA4) để theo dõi hành vi người dùng và các sự kiện thương mại điện tử.

---

## 1. Thiết lập tài khoản GA4

### 1.1. Tạo Property

1. Truy cập [analytics.google.com](https://analytics.google.com) và đăng nhập bằng tài khoản Google.
2. Nhấn **Admin** (bánh răng góc trái dưới) → **Create** → **Property**.
3. Đặt tên property (ví dụ: `SmartShop Production`), chọn múi giờ **Vietnam** và tiền tệ **Vietnamese Dong (VND)**.
4. Chọn **Web** làm platform, nhập URL trang web và tên luồng dữ liệu.
5. Sau khi tạo, bạn sẽ thấy **Measurement ID** có dạng `G-XXXXXXXXXX`.

### 1.2. Lấy Measurement ID

- Vào **Admin** → **Data Streams** → chọn web stream của bạn → copy **Measurement ID** (`G-XXXXXXXXXX`).

---

## 2. Cấu hình trong dự án

### 2.1. Thêm vào `.env`

```dotenv
GOOGLE_ANALYTICS_ID=G-XXXXXXXXXX
```

Để tắt tracking (ví dụ môi trường `local` phát triển), để trống:

```dotenv
GOOGLE_ANALYTICS_ID=
```

> **Lưu ý bảo mật:** Measurement ID (`G-...`) là public key, không phải secret — có thể commit vào `.env.example`. Tuy nhiên không bao giờ commit file `.env` thật.

### 2.2. Cấu hình trong `config/services.php`

```php
'google_analytics' => [
    'id' => env('GOOGLE_ANALYTICS_ID'),
],
```

Truy cập trong code: `config('services.google_analytics.id')`.

---

## 3. Kiến trúc tích hợp

### 3.1. Blade Component `<x-analytics />`

**File:** `resources/views/components/analytics.blade.php`

Component vô danh này:
- Đọc Measurement ID từ config
- Nếu ID không được cấu hình (`null` hoặc rỗng) → không render gì (an toàn cho môi trường local)
- Inject đoạn script GA4 (`gtag.js`) vào `<head>`

```blade
@php $gaId = config('services.google_analytics.id') @endphp
@if($gaId)
<script async src="https://www.googletagmanager.com/gtag/js?id={{ $gaId }}"></script>
<script>
    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);}
    gtag('js', new Date());
    gtag('config', '{{ $gaId }}');
</script>
@endif
```

### 3.2. Vị trí trong layout

Component được đặt trong `<head>` của hai layout:

| Layout | Phạm vi trang |
|---|---|
| `layouts/app.blade.php` | Toàn bộ trang shop (home, sản phẩm, giỏ hàng, đơn hàng...) — cả guest lẫn user đã đăng nhập |
| `layouts/guest.blade.php` | Trang xác thực (đăng nhập, đăng ký,...) — luôn là guest |

```blade
@vite(['resources/css/app.css', 'resources/js/app.js'])
<x-analytics />
```

> **Admin panel không tích hợp GA4.** Layout `layouts/admin.blade.php` không có `<x-analytics />` — traffic quản trị viên không ảnh hưởng số liệu trang khách hàng.

---

## 4. Các sự kiện được theo dõi

Dự án triển khai **GA4 Ecommerce Events** theo chuẩn [Google Analytics Ecommerce](https://developers.google.com/analytics/devguides/collection/ga4/ecommerce).

### 4.1. Page View (tự động)

GA4 tự động ghi nhận mọi lần tải trang (`page_view`) khi cấu hình `gtag('config', ...)`. Không cần code thêm.

### 4.2. `view_item` — Xem chi tiết sản phẩm

**Trigger:** Khi trang `shop.products.show` được tải.  
**File:** `resources/views/shop/products/show.blade.php`

```javascript
gtag('event', 'view_item', {
    currency: 'VND',
    value: <giá hiệu lực>,
    items: [{
        item_id: '<product_id>',
        item_name: '<tên sản phẩm>',
        item_category: '<tên danh mục>',
        price: <giá hiệu lực>,
        quantity: 1
    }]
});
```

### 4.3. `add_to_cart` — Thêm vào giỏ hàng

**Trigger:** Khi người dùng nhấn "Thêm vào giỏ hàng" thành công (API trả về `success: true`).  
**File:** `resources/views/shop/products/show.blade.php`

```javascript
gtag('event', 'add_to_cart', {
    currency: 'VND',
    value: <giá> * <số lượng>,
    items: [{
        item_id: '<product_id>',
        item_name: '<tên sản phẩm>',
        item_category: '<tên danh mục>',
        price: <giá>,
        quantity: <số lượng>
    }]
});
```

### 4.4. `begin_checkout` — Bắt đầu thanh toán

**Trigger:** Khi trang `shop.checkout.index` được tải.  
**File:** `resources/views/shop/checkout/index.blade.php`

```javascript
gtag('event', 'begin_checkout', {
    currency: 'VND',
    value: <tổng giỏ hàng + phí vận chuyển>,
    items: [
        // danh sách tất cả sản phẩm trong giỏ
    ]
});
```

### 4.5. `purchase` — Đặt hàng thành công

**Trigger:** Khi trang `shop.orders.show` được tải **ngay sau khi đặt hàng** (session flash `order_just_placed` khớp với `$order->id`). Sự kiện chỉ bắn **một lần duy nhất** — các lần xem lại trang đơn hàng sau đó không tính.  
**File:** `resources/views/shop/orders/show.blade.php`

```javascript
gtag('event', 'purchase', {
    transaction_id: '<order_id>',
    currency: 'VND',
    value: <final_amount>,
    shipping: <shipping_fee>,
    tax: 0,
    items: [
        // danh sách sản phẩm đã đặt
    ]
});
```

**Cơ chế chống trùng lặp:**  
`CheckoutController::store()` flash `order_just_placed` với `$order->id`. Session flash tự xóa sau một request — đảm bảo sự kiện `purchase` chỉ bắn đúng một lần.

---

## 5. Kiểm tra hoạt động

### 5.1. GA4 DebugView (khuyên dùng)

1. Cài extension **Google Analytics Debugger** cho Chrome.
2. Bật extension → tải trang web → vào **GA4 Admin > DebugView**.
3. Các sự kiện sẽ xuất hiện theo thời gian thực trong vòng vài giây.

### 5.2. Kiểm tra bằng Network tab

1. Mở DevTools → **Network** → lọc `collect?` hoặc `gtag`.
2. Duyệt sản phẩm, thêm vào giỏ, checkout → quan sát các request gửi đến `https://www.google-analytics.com/g/collect`.

### 5.3. Kiểm tra trên môi trường local

Trên môi trường local, nếu muốn kiểm tra GA4, hãy thêm Measurement ID vào `.env`:

```dotenv
GOOGLE_ANALYTICS_ID=G-XXXXXXXXXX   # ID của property test
```

Không dùng ID production để tránh làm nhiễu số liệu thật.

---

## 6. Báo cáo trong GA4

| Báo cáo | Đường dẫn trong GA4 | Dữ liệu |
|---|---|---|
| Lượt xem theo trang | Reports > Engagement > Pages | Page views tất cả trang |
| Sự kiện thương mại điện tử | Reports > Monetization > Ecommerce | view_item, add_to_cart, purchase |
| Phễu mua hàng | Explore > Funnel exploration | view_item → add_to_cart → begin_checkout → purchase |
| Doanh thu | Reports > Monetization > Overview | Tổng doanh thu từ purchase |

---

## 7. Tùy chỉnh thêm (tuỳ chọn)

### 7.1. Track tìm kiếm

Thêm vào `shop.products.index` khi `$request->has('q')`:

```blade
@if(request('q'))
@push('scripts')
<script>
gtag('event', 'search', { search_term: {{ Illuminate\Support\Js::from(request('q')) }} });
</script>
@endpush
@endif
```

### 7.2. Track xem danh mục

Thêm sự kiện `view_item_list` vào `shop.categories.show` và `shop.products.index`.

### 7.3. Ẩn IP người dùng (GDPR)

Thêm vào `analytics.blade.php`:

```javascript
gtag('config', '{{ $gaId }}', { anonymize_ip: true });
```

### 7.4. Chặn tracking cho admin

Đã được xử lý — admin layout (`layouts/admin.blade.php`) không include `<x-analytics />`.  
Nếu admin cũng dùng shop layout, có thể thêm điều kiện:

```blade
@if(!auth()->user()?->isAdmin())
    <x-analytics />
@endif
```

---

## 8. Cấu trúc file liên quan

```
app/Http/Controllers/Shop/
    CheckoutController.php        # flash order_just_placed sau khi đặt hàng
config/
    services.php                  # google_analytics.id
resources/views/
    components/
        analytics.blade.php       # <x-analytics /> — inject gtag.js
    layouts/
        app.blade.php             # dùng <x-analytics /> trong <head> (shop)
        guest.blade.php           # dùng <x-analytics /> trong <head> (auth pages)
    shop/
        products/show.blade.php   # view_item + add_to_cart events
        checkout/index.blade.php  # begin_checkout event
        orders/show.blade.php     # purchase event (chỉ lần đầu)
.env.example                      # GOOGLE_ANALYTICS_ID=
```
