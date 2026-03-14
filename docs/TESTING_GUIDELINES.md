# SmartShop — Testing Guidelines

> Hướng dẫn viết test cho dự án SmartShop.
> Mục tiêu: đảm bảo các tính năng cốt lõi hoạt động đúng, giúp phát hiện lỗi sớm khi thay đổi code.

---

## Mục lục

1. [Triết lý kiểm thử](#1-triết-lý-kiểm-thử)
2. [Cấu trúc thư mục test](#2-cấu-trúc-thư-mục-test)
3. [Unit Tests](#3-unit-tests)
4. [Feature Tests](#4-feature-tests)
5. [Test Coverage mục tiêu](#5-test-coverage-mục-tiêu)
6. [Test Data & Factories](#6-test-data--factories)
7. [Quy tắc đặt tên test](#7-quy-tắc-đặt-tên-test)
8. [Chạy tests](#8-chạy-tests)
9. [Mocking & Faking](#9-mocking--faking)
10. [Test cho AI Features](#10-test-cho-ai-features)

---

## 1. Triết lý kiểm thử

### Mức độ ưu tiên
Đây là dự án học tập, do đó tập trung vào:

1. **Feature Tests** (ưu tiên cao nhất) — test luồng nghiệp vụ end-to-end qua HTTP
2. **Unit Tests** — test logic phức tạp trong Service và helper functions
3. **Integration Tests** — test kết hợp nhiều layer (DB, queue, cache)

### Nguyên tắc
- Test phải **độc lập** — mỗi test không phụ thuộc vào test khác.
- Test phải **có tên mô tả rõ** — đọc tên là hiểu test làm gì.
- Test phải **nhanh** — tổng thời gian chạy < 2 phút.
- Test **không test framework** — test business logic, không test Laravel đã làm đúng chưa.
- **Viết test trước khi fix bug** — tái hiện bug bằng test, rồi fix.

### AAA Pattern (Arrange - Act - Assert)
```php
public function test_customer_can_add_product_to_cart(): void
{
    // Arrange — chuẩn bị dữ liệu
    $user    = User::factory()->create();
    $product = Product::factory()->inStock()->create(['price' => 100000]);

    // Act — thực hiện hành động
    $response = $this->actingAs($user)
        ->postJson('/api/cart/items', [
            'product_id' => $product->id,
            'quantity'   => 2,
        ]);

    // Assert — kiểm tra kết quả
    $response->assertCreated()
        ->assertJsonPath('data.cart_count', 1);

    $this->assertDatabaseHas('cart_items', [
        'user_id'    => $user->id,
        'product_id' => $product->id,
        'quantity'   => 2,
    ]);
}
```

---

## 2. Cấu trúc thư mục test

> Ký hiệu: ✅ đã triển khai — 📝 chưa triển khai (mục tiêu)

```
tests/
├── Unit/
│   ├── Services/
│   │   ├── CartServiceTest.php               ✅
│   │   ├── RecommendationServiceTest.php      ✅
│   │   ├── OrderServiceTest.php               📝
│   │   ├── PricingServiceTest.php             📝
│   │   └── VisualSearchServiceTest.php        📝
│   ├── Models/
│   │   └── ProductTest.php                   ✅
│   └── Helpers/
│       └── PriceFormatterTest.php             📝
│
├── Feature/
│   ├── Auth/                                  ✅ (6 files — Breeze)
│   ├── Shop/
│   │   ├── CartTest.php                       ✅
│   │   ├── CheckoutTest.php                   ✅
│   │   ├── VisualSearchTest.php               ✅
│   │   ├── ProductBrowsingTest.php            📝
│   │   ├── OrderTest.php                      📝
│   │   ├── AuthTest.php                       📝
│   │   ├── ReviewTest.php                     📝
│   │   └── WishlistTest.php                   📝
│   │
│   └── Admin/
│       ├── AuthorizationTest.php              ✅
│       ├── ProductManagementTest.php          ✅
│       ├── CategoryManagementTest.php         📝
│       ├── OrderManagementTest.php            📝
│       └── DashboardTest.php                  📝
│
└── TestCase.php
```

---

## 3. Unit Tests

Unit test kiểm tra **một hàm hoặc class đơn lẻ**, không phụ thuộc vào DB hay external services.

### Service Unit Test

```php
<?php

namespace Tests\Unit\Services;

use App\Models\Order;
use App\Models\Product;
use App\Repositories\OrderRepository;
use App\Services\CartService;
use App\Services\OrderService;
use App\Services\StockService;
use Tests\TestCase;
use Mockery;
use Mockery\MockInterface;

class OrderServiceTest extends TestCase
{
    private OrderService $orderService;
    private MockInterface $orderRepository;
    private MockInterface $cartService;
    private MockInterface $stockService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->orderRepository = Mockery::mock(OrderRepository::class);
        $this->cartService     = Mockery::mock(CartService::class);
        $this->stockService    = Mockery::mock(StockService::class);

        $this->orderService = new OrderService(
            $this->orderRepository,
            $this->cartService,
            $this->stockService,
        );
    }

    public function test_place_order_clears_cart_after_success(): void
    {
        // Arrange
        $user = User::factory()->make(['id' => 1]);
        // ... setup mocks

        // Act
        $order = $this->orderService->placeOrder($user, $request);

        // Assert
        $this->cartService->shouldHaveReceived('clear')->with($user)->once();
    }

    public function test_place_order_throws_exception_when_out_of_stock(): void
    {
        $this->expectException(\App\Exceptions\OutOfStockException::class);

        // ... setup out-of-stock scenario & call placeOrder
    }
}
```

### Model Unit Test

```php
<?php

namespace Tests\Unit\Models;

use App\Models\Product;
use Tests\TestCase;

class ProductTest extends TestCase
{
    public function test_effective_price_returns_sale_price_when_available(): void
    {
        $product = new Product(['price' => 100000, 'sale_price' => 80000]);

        $this->assertEquals(80000, $product->effective_price);
    }

    public function test_effective_price_returns_regular_price_when_no_sale(): void
    {
        $product = new Product(['price' => 100000, 'sale_price' => null]);

        $this->assertEquals(100000, $product->effective_price);
    }

    public function test_scope_active_filters_by_status(): void
    {
        Product::factory()->count(3)->create(['status' => true]);
        Product::factory()->count(2)->create(['status' => false]);

        $this->assertEquals(3, Product::active()->count());
    }
}
```

---

## 4. Feature Tests

Feature test mô phỏng **HTTP request thực** và kiểm tra response + DB state.

### Shop — Product Browsing

```php
<?php

namespace Tests\Feature\Shop;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductBrowsingTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_list_page_loads_successfully(): void
    {
        Product::factory()->count(5)->active()->create();

        $this->get(route('shop.products.index'))
            ->assertOk()
            ->assertViewIs('shop.products.index')
            ->assertViewHas('products');
    }

    public function test_product_detail_page_shows_correct_product(): void
    {
        $product = Product::factory()->active()->create();

        $this->get(route('shop.products.show', $product))
            ->assertOk()
            ->assertSee($product->name);
    }

    public function test_inactive_product_returns_404(): void
    {
        $product = Product::factory()->create(['status' => false]);

        $this->get(route('shop.products.show', $product))
            ->assertNotFound();
    }

    public function test_product_list_can_be_filtered_by_category(): void
    {
        $category     = Category::factory()->create();
        $inCategory   = Product::factory()->count(3)->active()->create(['category_id' => $category->id]);
        $otherProduct = Product::factory()->active()->create();

        $response = $this->get(route('shop.products.index', ['category' => $category->slug]));

        $response->assertOk();
        $products = $response->viewData('products');
        $this->assertCount(3, $products);
    }

    public function test_search_returns_matching_products(): void
    {
        Product::factory()->active()->create(['name' => 'Áo thun trắng']);
        Product::factory()->active()->create(['name' => 'Quần jeans xanh']);

        $this->get(route('shop.products.index', ['q' => 'áo thun']))
            ->assertOk()
            ->assertSee('Áo thun trắng')
            ->assertDontSee('Quần jeans xanh');
    }
}
```

### Shop — Cart

```php
<?php

namespace Tests\Feature\Shop;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_add_to_cart(): void
    {
        $product = Product::factory()->inStock()->create();

        $this->postJson('/api/cart/items', ['product_id' => $product->id, 'quantity' => 1])
            ->assertUnauthorized();
    }

    public function test_authenticated_user_can_add_product_to_cart(): void
    {
        $user    = User::factory()->create();
        $product = Product::factory()->inStock()->create();

        $this->actingAs($user)
            ->postJson('/api/cart/items', ['product_id' => $product->id, 'quantity' => 2])
            ->assertCreated()
            ->assertJsonStructure(['success', 'data' => ['cart_count']]);

        $this->assertDatabaseHas('cart_items', [
            'user_id'    => $user->id,
            'product_id' => $product->id,
            'quantity'   => 2,
        ]);
    }

    public function test_adding_same_product_updates_quantity(): void
    {
        $user    = User::factory()->create();
        $product = Product::factory()->inStock()->create();

        $this->actingAs($user)->postJson('/api/cart/items', ['product_id' => $product->id, 'quantity' => 1]);
        $this->actingAs($user)->postJson('/api/cart/items', ['product_id' => $product->id, 'quantity' => 2]);

        $this->assertDatabaseHas('cart_items', ['user_id' => $user->id, 'product_id' => $product->id, 'quantity' => 3]);
        $this->assertDatabaseCount('cart_items', 1);
    }

    public function test_cannot_add_out_of_stock_product(): void
    {
        $user    = User::factory()->create();
        $product = Product::factory()->create(['stock' => 0]);

        $this->actingAs($user)
            ->postJson('/api/cart/items', ['product_id' => $product->id, 'quantity' => 1])
            ->assertUnprocessable()
            ->assertJsonPath('success', false);
    }
}
```

### Shop — Checkout

```php
<?php

namespace Tests\Feature\Shop;

use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;
use App\Events\OrderPlaced;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user    = User::factory()->create();
        $this->product = Product::factory()->inStock(10)->create(['price' => 100000]);

        CartItem::factory()->create([
            'user_id'    => $this->user->id,
            'product_id' => $this->product->id,
            'quantity'   => 2,
        ]);
    }

    public function test_user_can_place_order_with_cod(): void
    {
        Event::fake();

        $response = $this->actingAs($this->user)
            ->postJson('/api/orders', [
                'shipping_address' => '123 Nguyễn Văn A, Q.1, TP.HCM',
                'payment_method'   => 'cod',
                'phone'            => '0901234567',
            ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['id', 'total', 'status']]);

        $this->assertDatabaseHas('orders', [
            'user_id'        => $this->user->id,
            'payment_method' => 'cod',
        ]);

        // Giỏ hàng đã được xóa sau đặt hàng
        $this->assertDatabaseEmpty('cart_items');

        // Tồn kho đã giảm
        $this->assertEquals(8, $this->product->fresh()->stock);

        Event::assertDispatched(OrderPlaced::class);
    }

    public function test_checkout_fails_with_empty_cart(): void
    {
        CartItem::where('user_id', $this->user->id)->delete();

        $this->actingAs($this->user)
            ->postJson('/api/orders', [
                'shipping_address' => '123 Test',
                'payment_method'   => 'cod',
            ])
            ->assertUnprocessable();
    }
}
```

### Admin — Authorization

```php
<?php

namespace Tests\Feature\Admin;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_admin(): void
    {
        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
    }

    public function test_customer_cannot_access_admin(): void
    {
        $customer = User::factory()->role('customer')->create();

        $this->actingAs($customer)
            ->get(route('admin.dashboard'))
            ->assertForbidden();
    }

    public function test_admin_can_access_admin_panel(): void
    {
        $admin = User::factory()->role('admin')->create();

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk();
    }

    public function test_admin_can_create_product(): void
    {
        $admin    = User::factory()->admin()->create();
        $category = Category::factory()->create();

        $this->actingAs($admin)
            ->postJson(route('admin.products.store'), [
                'name'        => 'Sản phẩm test',
                'price'       => 150000,
                'category_id' => $category->id,
                'stock'       => 10,
                'status'      => true,
            ])
            ->assertCreated();

        $this->assertDatabaseHas('products', ['name' => 'Sản phẩm test']);
    }

    public function test_customer_cannot_create_product(): void
    {
        $customer = User::factory()->role('customer')->create();

        $this->actingAs($customer)
            ->postJson(route('admin.products.store'), ['name' => 'hack'])
            ->assertForbidden();
    }
}
```

---

## 5. Test Coverage mục tiêu

| Module | Unit | Feature | Mức độ ưu tiên |
|---|---|---|---|
| Authentication (đăng ký, đăng nhập) | — | ✅ Bắt buộc | Cao |
| Phân quyền Admin/Customer | — | ✅ Bắt buộc | Cao |
| Đặt hàng (checkout flow) | ✅ | ✅ Bắt buộc | Cao |
| Giỏ hàng (thêm/sửa/xóa) | ✅ | ✅ Bắt buộc | Cao |
| Product CRUD (admin) | — | ✅ Bắt buộc | Cao |
| Tính giá (discount, voucher) | ✅ Bắt buộc | ✅ | Cao |
| Tìm kiếm sản phẩm | — | ✅ | Trung bình |
| Visual Search | ✅ (mock) | ✅ (mock) | Trung bình |
| Recommendations | ✅ (mock) | — | Trung bình |
| Wishlist | — | ✅ | Thấp |
| Đánh giá sản phẩm | — | ✅ | Thấp |
| Dashboard / Reports | — | ✅ | Thấp |

---

## 6. Test Data & Factories

### Factory chuẩn

```php
<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ProductFactory extends Factory
{
    public function definition(): array
    {
        $name = $this->faker->words(3, true);

        return [
            'category_id' => Category::factory(),
            'name'        => ucfirst($name),
            'slug'        => Str::slug($name) . '-' . $this->faker->unique()->numberBetween(1, 9999),
            'description' => $this->faker->paragraph(),
            'price'       => $this->faker->numberBetween(50000, 5000000),
            'sale_price'  => null,
            'stock'       => $this->faker->numberBetween(0, 100),
            'status'      => true,
            'is_featured' => false,
        ];
    }

    // States — dùng factory states thay vì magic numbers
    public function active(): static
    {
        return $this->state(['status' => true]);
    }

    public function inactive(): static
    {
        return $this->state(['status' => false]);
    }

    public function featured(): static
    {
        return $this->state(['is_featured' => true]);
    }

    public function inStock(int $stock = 10): static
    {
        return $this->state(['stock' => $stock]);
    }

    public function outOfStock(): static
    {
        return $this->state(['stock' => 0]);
    }

    public function withSalePrice(): static
    {
        return $this->state(function (array $attributes) {
            return ['sale_price' => $attributes['price'] * 0.8];
        });
    }
}
```

### User Factory với roles

```php
class UserFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name'     => $this->faker->name(),
            'email'    => $this->faker->unique()->safeEmail(),
            'password' => bcrypt('password'), // Test password luôn là 'password'
            'role'     => 'customer',
        ];
    }

    public function admin(): static
    {
        return $this->state(['role' => 'admin']);
    }

    public function role(string $role): static
    {
        return $this->state(['role' => $role]);
    }
}
```

### Quy tắc Factory

- Mỗi Model phải có Factory.
- `$this->faker->unique()` cho các cột unique (email, slug).
- Dùng **Factory States** thay vì tạo nhiều factory riêng.
- Test password mặc định luôn là `'password'` để dễ test.
- Factory không nên gọi API ngoài hay tạo file thực.

---

## 7. Quy tắc đặt tên test

### Tên test method phải mô tả đầy đủ

```
test_{subject}_{action}_{expected_result}
test_{subject}_{condition}_{expected_result}
```

**Ví dụ đúng:**
```php
test_guest_cannot_add_to_cart()
test_authenticated_user_can_place_order()
test_inactive_product_returns_404()
test_checkout_fails_when_cart_is_empty()
test_admin_can_create_product_with_valid_data()
test_effective_price_returns_sale_price_when_discount_available()
```

**Ví dụ sai:**
```php
test_cart()                 // Quá ngắn, không rõ
test_product1()             // Không có ý nghĩa
testCheckout()              // Thiếu prefix test_ (hoặc dùng attribute)
test_it_works()             // Không mô tả gì
```

### Đặt tên Test class

```
{FeatureName}Test.php    → ProductBrowsingTest, CartTest, CheckoutTest
{ServiceName}Test.php    → CartServiceTest, OrderServiceTest
{ModelName}Test.php      → ProductTest
```

---

## 8. Chạy tests

### Lệnh cơ bản

```bash
# Chạy toàn bộ test suite
php artisan test

# Chạy với hiển thị chi tiết
php artisan test --verbose

# Chạy test song song (nhanh hơn)
php artisan test --parallel

# Chạy một file test cụ thể
php artisan test tests/Feature/Shop/CartTest.php

# Chạy một method test cụ thể
php artisan test --filter=test_user_can_add_product_to_cart

# Chạy nhóm tests (dùng @group annotation)
php artisan test --group=shop
php artisan test --group=admin
php artisan test --group=ai

# Hiển thị code coverage (cần Xdebug hoặc PCOV)
php artisan test --coverage
php artisan test --coverage-html=storage/coverage
```

### Cấu hình phpunit.xml

```xml
<!-- phpunit.xml -->
<php>
    <env name="APP_ENV" value="testing"/>
    <env name="DB_CONNECTION" value="sqlite"/>
    <env name="DB_DATABASE" value=":memory:"/>
    <env name="QUEUE_CONNECTION" value="sync"/>
    <env name="CACHE_DRIVER" value="array"/>
    <env name="SESSION_DRIVER" value="array"/>
    <env name="AI_SERVICE_URL" value="http://localhost:8001"/>
</php>
```

### RefreshDatabase vs DatabaseTransactions

```php
// RefreshDatabase: migrate lại sau mỗi test class → chậm hơn, sạch hơn
use RefreshDatabase;

// DatabaseTransactions: rollback transaction sau mỗi test → nhanh hơn
// Dùng khi test không cần reset hẳn schema
use DatabaseTransactions;
```

**Khuyến nghị:** Dùng `RefreshDatabase` mặc định. Dùng `DatabaseTransactions` chỉ khi test suite quá chậm.

---

## 9. Mocking & Faking

### Mock External Services

```php
// Trong test, luôn mock AI Service để test không phụ thuộc external
public function test_visual_search_returns_products(): void
{
    // Mock HTTP call đến AI Service
    Http::fake([
        config('services.ai.url') . '/api/visual-search' => Http::response([
            'products' => [
                ['id' => 1, 'similarity_score' => 0.95],
                ['id' => 2, 'similarity_score' => 0.87],
            ]
        ], 200),
    ]);

    $product1 = Product::factory()->create(['id' => 1]);
    $product2 = Product::factory()->create(['id' => 2]);
    $image    = UploadedFile::fake()->image('search.jpg');

    $response = $this->actingAs(User::factory()->create())
        ->postJson('/api/visual-search', ['image' => $image]);

    $response->assertOk()
        ->assertJsonCount(2, 'data');
}

// Test fallback khi AI Service lỗi
public function test_visual_search_falls_back_when_ai_service_fails(): void
{
    Http::fake([
        config('services.ai.url') . '/*' => Http::response([], 500),
    ]);

    Product::factory()->count(5)->featured()->active()->create();
    $image = UploadedFile::fake()->image('search.jpg');

    $response = $this->actingAs(User::factory()->create())
        ->postJson('/api/visual-search', ['image' => $image]);

    // Fallback trả về sản phẩm nổi bật
    $response->assertOk()
        ->assertJsonPath('fallback', true);
}
```

### Fake Events, Jobs, Notifications

```php
public function test_order_placed_event_is_dispatched(): void
{
    Event::fake([OrderPlaced::class]);

    // ... place order

    Event::assertDispatched(OrderPlaced::class, function ($event) use ($order) {
        return $event->order->id === $order->id;
    });
}

public function test_order_confirmation_email_is_queued(): void
{
    Mail::fake();

    // ... place order

    Mail::assertQueued(OrderConfirmationMail::class, function ($mail) use ($user) {
        return $mail->hasTo($user->email);
    });
}

public function test_queue_job_is_dispatched(): void
{
    Queue::fake();

    // ... trigger action

    Queue::assertPushed(ProcessVisualSearch::class);
}
```

### Fake Storage

```php
public function test_admin_can_upload_product_image(): void
{
    Storage::fake('public');

    $admin   = User::factory()->admin()->create();
    $image   = UploadedFile::fake()->image('product.jpg', 800, 600)->size(500);
    $product = Product::factory()->create();

    $this->actingAs($admin)
        ->postJson(route('admin.products.images.store', $product), ['image' => $image])
        ->assertCreated();

    Storage::disk('public')->assertExists("products/{$image->hashName()}");

    // Đảm bảo file thực sự không được lưu trong test
    Storage::disk('public')->assertMissing('products/real_file.jpg');
}
```

---

## 10. Test cho AI Features

### Visual Search Test

```php
<?php

namespace Tests\Feature\Shop;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class VisualSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_visual_search_page_is_accessible(): void
    {
        $this->get(route('shop.visual-search'))
            ->assertOk()
            ->assertViewIs('shop.visual-search');
    }

    public function test_visual_search_requires_image_file(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/visual-search', ['image' => 'not-a-file'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['image']);
    }

    public function test_visual_search_rejects_non_image_files(): void
    {
        $user = User::factory()->create();
        $file = UploadedFile::fake()->create('malware.exe', 100);

        $this->actingAs($user)
            ->postJson('/api/visual-search', ['image' => $file])
            ->assertUnprocessable();
    }

    public function test_visual_search_returns_similar_products_from_ai(): void
    {
        Http::fake([
            '*visual-search*' => Http::response([
                'products' => [['id' => 1, 'similarity_score' => 0.92]],
            ]),
        ]);

        $user    = User::factory()->create();
        $product = Product::factory()->create();
        $image   = UploadedFile::fake()->image('shirt.jpg');

        $this->actingAs($user)
            ->postJson('/api/visual-search', ['image' => $image])
            ->assertOk()
            ->assertJsonStructure(['success', 'data' => [['id', 'name']]]);
    }

    public function test_visual_search_fallback_on_ai_failure(): void
    {
        Http::fake(['*' => Http::response([], 503)]);

        $featuredProducts = Product::factory()->count(5)->featured()->active()->create();
        $user             = User::factory()->create();
        $image            = UploadedFile::fake()->image('shirt.jpg');

        $this->actingAs($user)
            ->postJson('/api/visual-search', ['image' => $image])
            ->assertOk()
            ->assertJsonCount(5, 'data');
    }
}
```

### Recommendation Unit Test

```php
<?php

namespace Tests\Unit\Services;

use App\Models\Product;
use App\Services\RecommendationService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RecommendationServiceTest extends TestCase
{
    public function test_returns_ai_recommendations_when_service_available(): void
    {
        $product  = Product::factory()->make(['id' => 1]);
        $products = Product::factory()->count(4)->make();

        Http::fake([
            '*recommendations*' => Http::response([
                'recommended_products' => $products->pluck('id')->toArray(),
            ]),
        ]);

        // ... test service returns correct products
    }

    public function test_falls_back_to_same_category_on_ai_failure(): void
    {
        Http::fake(['*' => Http::serverError()]);

        $this->refreshDatabase();
        $category = Category::factory()->create();
        $product  = Product::factory()->create(['category_id' => $category->id]);
        Product::factory()->count(5)->create(['category_id' => $category->id]);
        Product::factory()->count(3)->create(); // Other categories

        $service = app(RecommendationService::class);
        $result  = $service->getForProduct($product, limit: 4);

        $this->assertCount(4, $result);
        $result->each(fn ($p) => $this->assertEquals($category->id, $p->category_id));
    }
}
```

---

## 11. Đánh giá độ chính xác AI (Offline Accuracy Evaluation)

Ngoài các PHP test dùng mock, AI Service đi kèm một script Python độc lập để
**đo chất lượng thực tế** của hai tính năng AI ngay trên dữ liệu sản phẩm và
lịch sử mua trong DB — không cần FastAPI server đang chạy.

### File

```
ai-service/evaluate_accuracy.py
```

### Chạy script (trong venv)

```bash
cd ai-service

# Windows (venv tên .venv)
.venv\Scripts\python evaluate_accuracy.py

# Mặc định: K = [1, 3, 5, 8], threshold từ .env
.venv\Scripts\python evaluate_accuracy.py

# Tuỳ chỉnh K và threshold
.venv\Scripts\python evaluate_accuracy.py --top-k 1 3 5 10 --vs-threshold 0.55 --rec-threshold 0.35
```

> **Yêu cầu trước khi chạy:** Đã chạy `php artisan embeddings:generate` để có dữ liệu
> trong bảng `product_embeddings`. Script đọc `.env` từ thư mục gốc tự động.

### Metrics được tính

| Metric | Tính năng | Ý nghĩa |
|---|---|---|
| **Category-Precision@K** | Visual Search | % kết quả top-K cùng danh mục với query |
| **Coverage** | Visual Search | % sản phẩm có ≥1 kết quả trên threshold |
| **Category-Precision@K** | Recommendations (Similar) | Sau khi áp threshold + diversity (giống production) |
| **Hit Rate@K** | Recommendations (Personal) | % user tìm thấy sản phẩm mua gần nhất trong top-K |
| **MRR** | Recommendations (Personal) | Mean Reciprocal Rank — đo vị trí trung bình |

### Đọc kết quả

**Visual Search — Category-Precision@K**
- Dùng embedding đã lưu làm proxy query (offline protocol chuẩn).
- `Precision@1 = 80%` → 80% sản phẩm tìm thấy được, kết quả giống nhất cũng cùng danh mục.
- Baseline ngẫu nhiên ≈ `1 / số_danh_mục` (ví dụ 10 danh mục → 10%).

**Recommendations — Hit Rate@K (Leave-One-Out)**
- Với mỗi user có ≥2 đơn hàng: giữ sản phẩm mua gần nhất làm "test item",
  xây taste profile từ lịch sử còn lại, kiểm tra test item có trong top-K không.
- `Hit Rate@5 = 40%` → 40% user tìm thấy sản phẩm cần gợi ý trong top 5 kết quả.
- `MRR = 20%` → trung bình test item nằm ở vị trí thứ 5 (`1/0.20 ≈ 5`).

### Cải thiện khi kết quả thấp

| Triệu chứng | Nguyên nhân có thể | Gợi ý |
|---|---|---|
| Coverage thấp (<50%) | Threshold quá cao | Giảm `VISUAL_SEARCH_THRESHOLD` trong `.env` |
| Precision thấp nhưng coverage cao | Embedding chất lượng thấp | Thử `CLIP_MODEL=ViT-L-14` |
| Hit Rate Personal thấp | Ít dữ liệu lịch sử | Cần thêm đơn hàng thực tế; threshold OK |
| Precision@1 cao nhưng @5 thấp | Diversity quá mạnh | Tăng `--max-per-cat` lên 3 |

---

## Checklist trước khi commit

Trước khi tạo Pull Request, đảm bảo:

- [ ] Toàn bộ test suite pass: `php artisan test`
- [ ] Không có N+1 queries mới (kiểm tra với Debugbar/Telescope)
- [ ] Code đã được format: `./vendor/bin/pint`
- [ ] Tính năng mới đã có test tương ứng
- [ ] Test đặt tên rõ ràng theo convention
- [ ] Mock external services (AI, payment gateway) trong test
- [ ] Không hardcode credentials trong test

---

*Cập nhật lần cuối: 2026-03-14*
