# SmartShop — Coding Standards & Conventions

> Tài liệu này định nghĩa quy tắc viết code cho dự án SmartShop.
> Mọi thành viên cần đọc và tuân thủ trước khi bắt đầu viết code.

---

## Mục lục

1. [Nguyên tắc tổng quát](#1-nguyên-tắc-tổng-quát)
2. [Cấu trúc & Phân tầng](#2-cấu-trúc--phân-tầng)
3. [Quy tắc đặt tên](#3-quy-tắc-đặt-tên)
4. [Models](#4-models)
5. [Controllers](#5-controllers)
6. [Services](#6-services)
7. [Repositories](#7-repositories)
8. [Form Requests](#8-form-requests)
9. [Routes](#9-routes)
10. [Migrations & Database](#10-migrations--database)
11. [Blade Views](#11-blade-views)
12. [API](#12-api)
13. [Bảo mật](#13-bảo-mật)
14. [Git Workflow](#14-git-workflow)

---

## 1. Nguyên tắc tổng quát

### Mục tiêu
Code phải **dễ đọc, dễ maintain, dễ test**. Ưu tiên sự rõ ràng hơn sự thông minh.

### Ngôn ngữ trong code
| Loại | Ngôn ngữ |
|---|---|
| Tên class, method, variable | **Tiếng Anh** |
| Comment kỹ thuật | **Tiếng Anh** |
| UI text (Blade) | **Tiếng Việt** |
| Thông báo lỗi người dùng | **Tiếng Việt** |
| Migration comment | Tiếng Việt chấp nhận được |
| Commit message | **Tiếng Việt hoặc Tiếng Anh** |

### Bộ công cụ bắt buộc
- **PHP CS Fixer** — format code PHP (PSR-12)
- **PHPStan level 6+** — static analysis
- **ESLint + Prettier** — format JS/CSS

```bash
# Chạy trước khi commit
./vendor/bin/pint              # Laravel Pint (PHP formatter)
./vendor/bin/phpstan analyse   # Static analysis
npm run lint                   # ESLint
```

---

## 2. Cấu trúc & Phân tầng

### Luồng xử lý yêu cầu

```
HTTP Request
    │
    ▼
Middleware (auth, role, throttle)
    │
    ▼
Controller
    │   → chỉ nhận request, gọi Service, trả response
    ▼
Service (Business Logic)
    │   → validate logic, orchestrate
    ▼
Repository (Data Access)
    │   → Eloquent queries
    ▼
Model / Database
```

### Nguyên tắc phân tầng

| Tầng | Được làm | Không được làm |
|---|---|---|
| **Controller** | Nhận request, gọi Service, trả view/JSON | Query DB, business logic |
| **Service** | Business logic, gọi Repository | Query DB trực tiếp (trừ query đơn giản) |
| **Repository** | Eloquent queries phức tạp | Business logic |
| **Model** | fillable, casts, relationships, scopes | Business logic |
| **Blade** | Hiển thị dữ liệu | Business logic, query DB |

### Single Responsibility
Mỗi class chỉ làm một việc. Nếu một Service dài hơn 200 dòng, cân nhắc tách thành các Service nhỏ hơn.

---

## 3. Quy tắc đặt tên

### Classes & Files

| Thành phần | Pattern | Ví dụ |
|---|---|---|
| Model | `PascalCase`, số ít | `Product`, `OrderItem` |
| Controller | `PascalCase` + `Controller` | `ProductController`, `OrderController` |
| Service | `PascalCase` + `Service` | `ProductService`, `VisualSearchService` |
| Repository | `PascalCase` + `Repository` | `ProductRepository`, `OrderRepository` |
| Form Request | Verb + Resource + `Request` | `StoreProductRequest`, `UpdateOrderRequest` |
| Event | PascalCase, past tense | `OrderPlaced`, `ProductViewed` |
| Listener | PascalCase, verb phrase | `SendOrderConfirmation`, `UpdateStock` |
| Job | PascalCase, verb phrase | `ProcessVisualSearch`, `GenerateInvoice` |
| Resource | `PascalCase` + `Resource` | `ProductResource`, `OrderCollection` |
| Policy | `PascalCase` + `Policy` | `ProductPolicy`, `OrderPolicy` |

### Methods

| Loại method | Pattern | Ví dụ |
|---|---|---|
| Getter | `get` + noun | `getRelatedProducts()` |
| Checker | `is/has/can` + adj | `isActive()`, `hasStock()` |
| Action | verb + noun | `placeOrder()`, `cancelOrder()` |
| Scope | `scope` + PascalCase | `scopeActive()`, `scopeFeatured()` |
| Relationship | noun (snake_case) | `category()`, `orderItems()` |

### Variables & Properties
```php
// camelCase cho variable
$unitPrice = $product->price;
$cartItems = collect($items);

// UPPER_SNAKE_CASE cho constants
const MAX_CART_ITEMS = 100;
const DEFAULT_SHIPPING_FEE = 30000;

// Boolean: đặt tên rõ ý nghĩa true/false
$isActive = true;
$hasDiscount = $product->sale_price > 0;
$canCheckout = $cartItems->isNotEmpty();
```

### Database

```
Tables    : snake_case, plural           → products, order_items, product_images
Columns   : snake_case                   → sale_price, is_active, created_at
Foreign key: {table_singular}_id         → category_id, user_id
Pivot table: alphabetical order          → product_tag (not tag_product)
Index name : idx_{table}_{column}        → idx_products_status
```

### Routes
```php
// Shop routes: shop.{resource}.{action}
route('shop.products.index')   // /products
route('shop.products.show')    // /products/{slug}
route('shop.cart.index')       // /cart

// Admin routes: admin.{resource}.{action}
route('admin.products.index')  // /admin/products
route('admin.orders.show')     // /admin/orders/{order}

// API routes: api.{version}.{resource}.{action}
route('api.v1.products.search') // /api/v1/products/search
```

---

## 4. Models

### Template chuẩn

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    // 1. Bảng (chỉ khai báo nếu khác convention)
    // protected $table = 'products';

    // 2. Fillable — khai báo rõ ràng, không dùng $guarded = []
    protected $fillable = [
        'category_id',
        'name',
        'slug',
        'description',
        'price',
        'sale_price',
        'stock',
        'status',
        'is_featured',
    ];

    // 3. Casts — type safety
    protected $casts = [
        'price'       => 'decimal:2',
        'sale_price'  => 'decimal:2',
        'is_featured' => 'boolean',
        'status'      => 'boolean',
    ];

    // 4. Relationships
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    public function primaryImage(): HasOne
    {
        return $this->hasOne(ProductImage::class)->where('is_primary', true);
    }

    // 5. Scopes
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', true);
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    public function scopeInStock(Builder $query): Builder
    {
        return $query->where('stock', '>', 0);
    }

    // 6. Accessors / Mutators (dùng PHP 8 syntax)
    public function getEffectivePriceAttribute(): float
    {
        return $this->sale_price > 0 ? $this->sale_price : $this->price;
    }
}
```

### Quy tắc Model

- `$fillable` phải khai báo tường minh. **Không dùng** `protected $guarded = []`.
- `$casts` phải khai báo đúng type, nhất là `boolean`, `decimal`, `array`, `json`.
- Không đặt business logic trong Model. Chỉ khai báo relationships, scopes, accessors.
- Luôn dùng `SoftDeletes` cho: `User`, `Product`, `Order`, `Category`.
- Không eager load mặc định trong `$with` trừ khi thực sự cần thiết.

---

## 5. Controllers

### Template chuẩn

```php
<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Http\Requests\Shop\StoreReviewRequest;
use App\Models\Product;
use App\Services\ProductService;
use App\Services\RecommendationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ProductController extends Controller
{
    // Constructor injection (readonly)
    public function __construct(
        private readonly ProductService $productService,
        private readonly RecommendationService $recommendationService,
    ) {}

    // Dùng Route Model Binding khi có thể
    public function show(Product $product): View
    {
        abort_if(! $product->status, 404);

        $this->productService->recordView($product);
        $recommendations = $this->recommendationService->getForProduct($product);

        return view('shop.products.show', compact('product', 'recommendations'));
    }

    public function index(): View
    {
        $products = $this->productService->getListForShop(request());

        return view('shop.products.index', compact('products'));
    }
}
```

### Quy tắc Controller

- **Mỗi method tối đa 15 dòng.** Nếu dài hơn, tách logic vào Service.
- **Không query DB** trực tiếp trong Controller.
- Dùng **Route Model Binding** thay vì `Product::findOrFail($id)`.
- Luôn trả về **type hint** (`View`, `JsonResponse`, `RedirectResponse`).
- Dùng **Form Request** để validate — không validate bằng `$request->validate()` trong controller method.
- Nhóm controller theo phân hệ: `Admin\`, `Shop\`, `Api\`.

---

## 6. Services

### Template chuẩn

```php
<?php

namespace App\Services;

use App\Models\Order;
use App\Models\User;
use App\Http\Requests\Shop\CheckoutRequest;
use App\Repositories\OrderRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderService
{
    public function __construct(
        private readonly OrderRepository $orderRepository,
        private readonly CartService $cartService,
        private readonly StockService $stockService,
    ) {}

    public function placeOrder(User $user, CheckoutRequest $request): Order
    {
        return DB::transaction(function () use ($user, $request) {
            $cartItems = $this->cartService->getItems($user);

            // Kiểm tra tồn kho
            $this->stockService->validateStock($cartItems);

            // Tạo đơn hàng
            $order = $this->orderRepository->create([
                'user_id'          => $user->id,
                'total'            => $this->cartService->getTotal($user),
                'shipping_address' => $request->validated('shipping_address'),
                'payment_method'   => $request->validated('payment_method'),
            ]);

            // Tạo order items & giảm tồn kho
            foreach ($cartItems as $item) {
                $this->orderRepository->addItem($order, $item);
                $this->stockService->decrease($item->product, $item->quantity);
            }

            // Xóa giỏ hàng
            $this->cartService->clear($user);

            return $order;
        });
    }
}
```

### Quy tắc Service

- Một Service = một domain (ví dụ `ProductService`, `OrderService`).
- Luôn dùng `DB::transaction()` cho các thao tác ghi nhiều bảng.
- **Không return View** từ Service. Service chỉ trả về data.
- Xử lý exception: catch ở Service nếu muốn fallback, throw lên nếu muốn Controller handle.
- Log các bước quan trọng và lỗi với context đầy đủ.

---

## 7. Repositories

### Template chuẩn

```php
<?php

namespace App\Repositories;

use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

class ProductRepository
{
    public function getForShop(Request $request, int $perPage = 20): LengthAwarePaginator
    {
        return Product::with(['primaryImage', 'category'])
            ->active()
            ->inStock()
            ->when($request->category, fn ($q, $slug) => $q->whereHas('category', fn ($q) => $q->where('slug', $slug)))
            ->when($request->min_price, fn ($q, $price) => $q->where('price', '>=', $price))
            ->when($request->max_price, fn ($q, $price) => $q->where('price', '<=', $price))
            ->when($request->sort === 'price_asc', fn ($q) => $q->orderBy('price'))
            ->when($request->sort === 'price_desc', fn ($q) => $q->orderByDesc('price'))
            ->when($request->sort === 'newest', fn ($q) => $q->latest())
            ->paginate($perPage);
    }

    public function findBySlug(string $slug): ?Product
    {
        return Product::with(['images', 'category', 'reviews.user'])
            ->where('slug', $slug)
            ->active()
            ->first();
    }
}
```

### Quy tắc Repository

- Luôn dùng **eager loading** (`with()`) khi biết trước relationship cần dùng.
- **Không đặt business logic** trong Repository.
- Tham số filter/sort dùng `when()` để tránh query thừa.
- Mỗi method chỉ làm **một truy vấn**.

---

## 8. Form Requests

### Template chuẩn

```php
<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Luôn kiểm tra authorization tường minh
        return $this->user()->role === 'admin';
    }

    public function rules(): array
    {
        return [
            'name'        => ['required', 'string', 'max:255'],
            'slug'        => ['required', 'string', Rule::unique('products', 'slug')->ignore($this->product)],
            'price'       => ['required', 'numeric', 'min:0', 'max:999999999'],
            'sale_price'  => ['nullable', 'numeric', 'min:0', 'lt:price'],
            'category_id' => ['required', 'exists:categories,id'],
            'stock'       => ['required', 'integer', 'min:0'],
            'images'      => ['sometimes', 'array', 'max:10'],
            'images.*'    => ['image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'description' => ['nullable', 'string', 'max:10000'],
            'status'      => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'price.required'       => 'Vui lòng nhập giá sản phẩm.',
            'category_id.exists'   => 'Danh mục không tồn tại.',
            'images.*.max'         => 'Mỗi ảnh không được vượt quá 2MB.',
        ];
    }
}
```

### Quy tắc Form Request

- **Tất cả input** từ user phải qua Form Request.
- `authorize()` phải **kiểm tra quyền thực sự**, không được chỉ `return true`.
- Dùng `$request->validated()` trong Controller/Service, **không** dùng `$request->all()`.
- Validate file upload: kiểm tra `mimes` (không dùng `mime_types` vì dễ bị bypass), giới hạn `max`.
- Thông báo lỗi (`messages()`) phải bằng **tiếng Việt**.

---

## 9. Routes

### Cấu trúc file routes

```
routes/
├── web.php         → Tất cả web route (shop + admin)
├── api.php         → API routes (v1)
└── channels.php    → Broadcast channels
```

### Quy tắc routes

```php
// ✅ Đúng: Route Model Binding với custom key
Route::get('/products/{product:slug}', [ProductController::class, 'show'])
    ->name('shop.products.show');

// ✅ Đúng: Resource route trong admin
Route::resource('products', Admin\ProductController::class)
    ->except(['show']);

// ✅ Đúng: Group với middleware + prefix
Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth', 'role:admin'])
    ->group(function () {
        Route::resource('products', Admin\ProductController::class);
        Route::resource('orders', Admin\OrderController::class)->only(['index', 'show', 'update']);
    });

// ❌ Sai: Không đặt tên route
Route::get('/san-pham/{slug}', [ProductController::class, 'show']);

// ❌ Sai: Logic trong route closure
Route::get('/products', function () {
    return Product::all(); // Không được
});
```

- Mọi route **phải có tên** (`->name()`).
- Dùng `Route::resource()` khi có đủ CRUD actions.
- Route admin phải trong group middleware `['auth', 'role:admin']`.
- API routes phải có versioning prefix `/v1`.

---

## 10. Migrations & Database

### Template migration

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();

            // Foreign keys
            $table->foreignId('category_id')
                ->constrained()
                ->cascadeOnDelete();

            // Columns
            $table->string('name', 255);
            $table->string('slug', 255)->unique();
            $table->text('description')->nullable();
            $table->decimal('price', 15, 2)->unsigned();
            $table->decimal('sale_price', 15, 2)->unsigned()->nullable();
            $table->unsignedInteger('stock')->default(0);
            $table->boolean('status')->default(true);
            $table->boolean('is_featured')->default(false);

            // Timestamps & SoftDelete
            $table->timestamps();
            $table->softDeletes(); // Dữ liệu quan trọng luôn dùng soft delete
        });

        // Indexes sau khi tạo bảng
        Schema::table('products', function (Blueprint $table) {
            $table->index(['status', 'is_featured']);
            $table->index(['category_id', 'status']);
            $table->fullText(['name', 'description']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
```

### Quy tắc migration

- **Không sửa migration cũ đã commit** — tạo migration mới để alter table.
- Luôn có `timestamps()`.
- Luôn có `softDeletes()` cho: `users`, `products`, `orders`, `categories`.
- Foreign key: dùng `constrained()` với cascade phù hợp.
- Thêm index cho cột thường dùng trong `WHERE`, `ORDER BY`, `JOIN`.
- **Không** dùng `ENUM` — dùng `tinyInteger` hoặc `string` với validation.
- Decimal cho tiền: `decimal(15, 2)`.

---

## 11. Blade Views

### Cấu trúc views

```
resources/views/
├── layouts/
│   ├── app.blade.php        → Layout shop
│   └── admin.blade.php      → Layout admin
├── components/              → Shared Blade components
│   ├── product-card.blade.php
│   ├── pagination.blade.php
│   └── alert.blade.php
├── shop/
│   ├── home.blade.php
│   ├── products/
│   │   ├── index.blade.php
│   │   └── show.blade.php
│   └── cart/
│       └── index.blade.php
└── admin/
    ├── dashboard.blade.php
    └── products/
        ├── index.blade.php
        ├── create.blade.php
        └── edit.blade.php
```

### Quy tắc Blade

```blade
{{-- ✅ Luôn escape output --}}
{{ $product->name }}
{{ number_format($product->price) }} đ

{{-- ✅ Chỉ dùng {!! !!} cho HTML đã sanitize --}}
{!! clean($product->description) !!}

{{-- ✅ Component pattern --}}
<x-product-card :product="$product" :show-badge="true" />

{{-- ✅ Alpine.js cho interactivity nhỏ --}}
<div x-data="{ open: false }">
    <button @click="open = !open">Menu</button>
    <div x-show="open">...</div>
</div>

{{-- ❌ Không query trong view --}}
{{-- {{ App\Models\Product::all() }} --}}

{{-- ❌ Không PHP logic phức tạp trong view --}}
{{-- @php $x = ...; @endphp (chỉ dùng cho trường hợp thực sự cần) --}}
```

---

## 12. API

### Response format thống nhất

```php
// Thành công - danh sách
return response()->json([
    'success' => true,
    'data'    => ProductResource::collection($products),
    'meta'    => [
        'total'        => $products->total(),
        'per_page'     => $products->perPage(),
        'current_page' => $products->currentPage(),
        'last_page'    => $products->lastPage(),
    ],
]);

// Thành công - đơn lẻ
return response()->json([
    'success' => true,
    'data'    => new ProductResource($product),
]);

// Lỗi client (4xx)
return response()->json([
    'success' => false,
    'message' => 'Sản phẩm không tồn tại.',
], 404);

// Lỗi validation
return response()->json([
    'success' => false,
    'message' => 'Dữ liệu không hợp lệ.',
    'errors'  => $validator->errors(),
], 422);
```

### API Resource

```php
class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'name'        => $this->name,
            'slug'        => $this->slug,
            'price'       => (float) $this->price,
            'sale_price'  => $this->sale_price ? (float) $this->sale_price : null,
            'image_url'   => $this->primaryImage?->url,
            'category'    => new CategoryResource($this->whenLoaded('category')),
            'in_stock'    => $this->stock > 0,
        ];
    }
}
```

### Quy tắc API

- Rate limiting bắt buộc: `throttle:60,1` cho public, `throttle:600,1` cho authenticated.
- Mọi API đều phải có **API Resource** — không return Model trực tiếp.
- Dùng `whenLoaded()` trong Resource để tránh N+1.
- Validate token với `auth:sanctum` middleware.

---

## 13. Bảo mật

### Checklist bảo mật

| Mục | Yêu cầu |
|---|---|
| Input validation | Mọi input qua Form Request, dùng `validated()` |
| SQL Injection | Luôn dùng Eloquent/Query Builder binding |
| XSS | Blade `{{ }}` cho output, sanitize khi lưu HTML |
| CSRF | `@csrf` trong mọi form, verify token trong AJAX |
| File upload | Kiểm tra MIME thực + extension, lưu ngoài public/ |
| Authentication | Dùng `auth` middleware, không hardcode session |
| Authorization | `authorize()` trong Form Request hoặc Policy |
| Sensitive data | Trong `.env`, không hardcode |
| Error exposure | Không return stack trace cho user ở production |
| Mass assignment | Dùng `$fillable`, không dùng `$request->all()` |

### Xử lý file upload an toàn

```php
public function store(Request $request): void
{
    $request->validate([
        'image' => ['required', 'file', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
    ]);

    $file = $request->file('image');

    // Kiểm tra MIME type thực sự (không chỉ extension)
    $mime = $file->getMimeType();
    if (! in_array($mime, ['image/jpeg', 'image/png', 'image/webp'])) {
        abort(422, 'File không hợp lệ.');
    }

    // Tạo tên file ngẫu nhiên (không dùng tên gốc)
    $path = $file->store('products', 'private');
}
```

---

## 14. Git Workflow

### Branch naming

```
main              → Production code
develop           → Development branch
feature/{name}    → New features        vd: feature/visual-search
fix/{name}        → Bug fixes           vd: fix/cart-quantity-update
hotfix/{name}     → Urgent production fixes
```

### Commit message format

```
<type>: <mô tả ngắn gọn>

[body nếu cần]
```

**Types:**
- `feat`: tính năng mới
- `fix`: sửa lỗi
- `refactor`: cải tiến code không thêm feature
- `style`: format, whitespace (không ảnh hưởng logic)
- `test`: thêm/sửa test
- `docs`: cập nhật tài liệu
- `chore`: build, config, dependencies

**Ví dụ:**
```
feat: thêm tính năng tìm kiếm sản phẩm bằng hình ảnh

- Tích hợp API visual search với AI Service
- Thêm drag-and-drop upload ảnh
- Fallback về sản phẩm nổi bật khi AI Service lỗi
```

### Quy trình

```
1. Pull latest develop
2. Tạo feature branch
3. Viết code + test
4. Chạy linter và tests
5. Tạo Pull Request → develop
6. Code review (nếu có)
7. Merge
```

---

*Cập nhật lần cuối: 2026-03-07*
