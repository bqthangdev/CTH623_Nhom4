# SmartShop — Copilot Instructions

## Tổng quan dự án

**SmartShop** là website thương mại điện tử xây dựng bằng **Laravel 11** cho học phần
CT573HT — Tiếp thị và Kinh doanh Kỹ thuật số, Thạc sĩ HTTT K32.

### Hai phân hệ chính
- **Admin Panel** (`/admin`) — quản trị nội bộ, middleware `auth + role:admin`
- **Shop (Frontend)** (`/`) — giao diện mua sắm khách hàng, middleware `auth` (tùy route)

### Tech stack
- **Backend:** PHP 8.2+, Laravel 11, MySQL 8, Redis
- **Frontend:** Blade, Alpine.js, Tailwind CSS 3, Livewire 3
- **Search:** Laravel Scout + Meilisearch
- **AI Service:** Python 3.11 + FastAPI (microservice riêng tại port 8001)
- **Queue:** Redis queue với Laravel Queue
- **Auth:** Laravel Breeze (session-based) + Sanctum (API token)

---

## Kiến trúc & Quy tắc chung

### Ngôn ngữ
- Code, tên biến, comment kỹ thuật: **tiếng Anh**
- UI text, thông báo người dùng, migration comments: **tiếng Việt được chấp nhận**

### Phân tầng (Layered Architecture)
```
Route → Controller → Service → Repository → Model → Database
```
- **Controller:** chỉ nhận request, gọi Service, trả response/view. Không chứa business logic.
- **Service:** chứa toàn bộ business logic. Một Service class = một domain (ProductService, OrderService...).
- **Repository:** truy vấn DB thông qua Eloquent. Controller/Service không gọi Model trực tiếp khi query phức tạp.
- **Model:** khai báo relationships, casts, fillable. Không chứa business logic.
- **Form Request:** validate input, không validate trong controller.

### Namespace convention
```
App\Http\Controllers\Admin\   → Controller phân hệ Admin
App\Http\Controllers\Shop\    → Controller phân hệ Shop
App\Http\Controllers\Api\     → API Controller
App\Services\                 → Service classes
App\Repositories\             → Repository classes
App\Models\                   → Eloquent models
App\Http\Requests\Admin\      → Form Request Admin
App\Http\Requests\Shop\       → Form Request Shop
App\Jobs\                     → Queue Jobs
App\Events\                   → Events
App\Listeners\                → Listeners
```

---

## Quy tắc đặt tên

### PHP / Laravel
| Thành phần | Convention | Ví dụ |
|---|---|---|
| Class | PascalCase | `ProductService`, `OrderController` |
| Method | camelCase | `getRelatedProducts()`, `placeOrder()` |
| Variable | camelCase | `$unitPrice`, `$cartItems` |
| Constant | UPPER_SNAKE_CASE | `MAX_CART_ITEMS` |
| Database table | snake_case, plural | `product_images`, `order_items` |
| Column | snake_case | `sale_price`, `is_active` |
| Route name | kebab-case với dot notation | `shop.products.show`, `admin.orders.index` |
| View file | kebab-case | `product-detail.blade.php` |
| Blade component | kebab-case | `<x-product-card />` |
| Event | Past tense | `OrderPlaced`, `ProductViewed` |
| Job | Verb phrase | `ProcessVisualSearch`, `SendOrderConfirmation` |
| Migration | snake_case timestamp | `2025_01_01_000000_create_products_table` |

### Routes
```php
// Web routes — dùng resource() hoặc đặt tên rõ ràng
Route::get('/products/{product:slug}', [ProductController::class, 'show'])->name('shop.products.show');

// Admin routes — prefix + name prefix
Route::prefix('admin')->name('admin.')->middleware(['auth', 'role:admin'])->group(function () {
    Route::resource('products', Admin\ProductController::class);
});

// API routes — versioning
Route::prefix('v1')->group(function () {
    Route::post('/visual-search', [VisualSearchController::class, 'search']);
});
```

---

## Quy tắc viết code

### Models
```php
class Product extends Model
{
    // Luôn khai báo fillable
    protected $fillable = ['name', 'slug', 'price', ...];

    // Sử dụng casts cho type safety
    protected $casts = [
        'price'      => 'decimal:2',
        'is_active'  => 'boolean',
        'attributes' => 'array',
    ];

    // Relationships viết sau fillable/casts
    public function category(): BelongsTo { ... }
    public function images(): HasMany { ... }

    // Scopes đặt tên scope + prefix 'scope'
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
```

### Controllers
```php
class ProductController extends Controller
{
    // Inject Service qua constructor
    public function __construct(
        private readonly ProductService $productService,
        private readonly RecommendationService $recommendationService,
    ) {}

    public function show(Product $product): View
    {
        // Chỉ gọi Service, không query trực tiếp
        $recommendations = $this->recommendationService->getForProduct($product);
        return view('shop.products.show', compact('product', 'recommendations'));
    }
}
```

### Services
```php
class OrderService
{
    public function __construct(
        private readonly OrderRepository $orderRepository,
        private readonly CartService $cartService,
    ) {}

    public function placeOrder(User $user, CheckoutRequest $request): Order
    {
        return DB::transaction(function () use ($user, $request) {
            // Business logic tại đây
        });
    }
}
```

### Form Requests
```php
class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'name'        => ['required', 'string', 'max:255'],
            'price'       => ['required', 'numeric', 'min:0'],
            'category_id' => ['required', 'exists:categories,id'],
            'images'      => ['required', 'array', 'min:1'],
            'images.*'    => ['image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }
}
```

### Blade Views
```blade
{{-- Luôn escape output với {{ }} trừ khi cần HTML --}}
{{ $product->name }}

{{-- Dùng {!! !!} chỉ khi output là HTML đã được sanitize --}}
{!! $product->description_html !!}

{{-- Component với named slots --}}
<x-product-card :product="$product" :show-badge="true" />

{{-- Tránh logic phức tạp trong view, dùng View Composer hoặc Component --}}
```

### API Responses
```php
// Thành công
return response()->json([
    'success' => true,
    'data'    => ProductResource::collection($products),
    'meta'    => ['total' => $products->total()],
], 200);

// Lỗi
return response()->json([
    'success' => false,
    'message' => 'Sản phẩm không tồn tại.',
    'errors'  => [],
], 404);
```

---

## Bảo mật (Security)

### Luôn thực hiện
- Dùng **Form Request** để validate tất cả input từ người dùng
- Dùng **Eloquent** hoặc **Query Builder với binding** — tuyệt đối không nối chuỗi SQL
- Dùng `authorize()` trong Form Request hoặc `$this->authorize()` trong Controller
- Upload file: kiểm tra MIME type thực sự (không chỉ extension), lưu ngoài `public/`
- Sensitive data trong `.env`, không hardcode
- CSRF token cho mọi form POST/PUT/DELETE
- Rate limiting cho API endpoint: `throttle:60,1`
- Sanitize HTML input trước khi lưu DB (dùng `strip_tags()` hoặc HTMLPurifier)

### Tuyệt đối không
- Không dùng `$request->all()` trực tiếp với `fill()` / `create()` — dùng `$request->validated()`
- Không hiển thị stack trace cho user ở production
- Không lưu password plaintext
- Không gọi AI Service với dữ liệu chưa validate

---

## AI Service Integration

### Visual Search
```php
// app/Services/VisualSearchService.php
class VisualSearchService
{
    public function search(UploadedFile $image): Collection
    {
        $response = Http::timeout(config('services.ai.timeout', 30))
            ->attach('image', $image->getContent(), $image->getClientOriginalName())
            ->post(config('services.ai.url') . '/api/visual-search');

        if ($response->failed()) {
            // Fallback: trả về sản phẩm nổi bật
            return Product::active()->featured()->limit(10)->get();
        }

        $productIds = collect($response->json('products'))->pluck('id');
        return Product::whereIn('id', $productIds)->active()->get();
    }
}
```

### Recommendations
```php
// Gọi AI Service, nếu lỗi fallback về content-based đơn giản
class RecommendationService
{
    public function getForProduct(Product $product, int $limit = 8): Collection
    {
        try {
            return $this->fetchFromAiService($product, $limit);
        } catch (Exception) {
            return $this->fallbackRecommendations($product, $limit);
        }
    }

    private function fallbackRecommendations(Product $product, int $limit): Collection
    {
        return Product::where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->active()
            ->inRandomOrder()
            ->limit($limit)
            ->get();
    }
}
```

---

## Database

### Migration rules
```php
// Luôn có timestamps()
// Luôn có softDeletes() cho dữ liệu quan trọng (products, orders, users)
// Foreign key đặt sau column, có onDelete constraint
$table->foreignId('category_id')->constrained()->cascadeOnDelete();

// Index cho column thường query
$table->index(['status', 'created_at']);
$table->fullText(['name', 'description']); // cho search
```

### Seeders
- `DatabaseSeeder` gọi các seeder riêng
- Mỗi bảng có 1 Seeder class và 1 Factory class
- Demo data phải nhất quán: đủ để test mọi feature

---

## Queue Jobs

```php
class ProcessVisualSearch implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 60;

    // Xử lý lỗi và retry
    public function failed(Throwable $exception): void
    {
        Log::error('Visual search job failed', [
            'error' => $exception->getMessage(),
        ]);
    }
}
```

---

## Frontend (Blade + Alpine.js)

### Alpine.js cho interactive components
```html
<!-- Giỏ hàng mini với Alpine.js -->
<div x-data="cartComponent()" x-init="init()">
    <button @click="addToCart({{ $product->id }})">
        Thêm vào giỏ
    </button>
</div>

<script>
function cartComponent() {
    return {
        count: 0,
        init() { this.count = this.$store.cart.count },
        addToCart(productId) {
            fetch('/api/cart/items', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                body: JSON.stringify({ product_id: productId, quantity: 1 })
            }).then(r => r.json()).then(data => { this.count = data.cart_count });
        }
    }
}
</script>
```

### Tailwind CSS
- Dùng utility classes trực tiếp trong Blade
- Custom classes vào `resources/css/app.css` chỉ khi thực sự cần
- Responsive: mobile-first, breakpoints `sm:` `md:` `lg:`

---

## Logging & Error Handling

```php
// Dùng context logging
Log::info('Order placed', ['order_id' => $order->id, 'user_id' => $user->id]);
Log::error('AI service failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);

// Custom Exception Handler tại app/Exceptions/Handler.php
// API: trả JSON error
// Web: redirect về trang lỗi thân thiện
```

---

## Điều không được làm

- Không đặt business logic trong Blade template
- Không query DB trong vòng lặp (N+1 problem) — dùng `with()` eager loading
- Không dùng `sleep()` hay blocking call trong request — dùng Queue
- Không hardcode URL, domain, API key trong code
- Không bỏ qua exception — log hoặc xử lý
- Không tạo migration mới để sửa migration cũ — tạo migration thay đổi mới
- Không commit file `.env`
