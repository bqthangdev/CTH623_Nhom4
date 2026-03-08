<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_admin_dashboard(): void
    {
        $this->get(route('admin.dashboard'))
            ->assertRedirect(route('login'));
    }

    public function test_customer_cannot_access_admin_dashboard(): void
    {
        $customer = User::factory()->role('customer')->create();

        $this->actingAs($customer)
            ->get(route('admin.dashboard'))
            ->assertForbidden();
    }

    public function test_admin_can_access_admin_dashboard(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk();
    }

    public function test_customer_cannot_access_admin_products(): void
    {
        $customer = User::factory()->role('customer')->create();

        $this->actingAs($customer)
            ->get(route('admin.products.index'))
            ->assertForbidden();
    }

    public function test_admin_can_view_products_list(): void
    {
        $admin = User::factory()->admin()->create();
        Product::factory()->count(3)->create();

        $this->actingAs($admin)
            ->get(route('admin.products.index'))
            ->assertOk()
            ->assertViewIs('admin.products.index');
    }

    public function test_customer_cannot_access_admin_orders(): void
    {
        $customer = User::factory()->role('customer')->create();

        $this->actingAs($customer)
            ->get(route('admin.orders.index'))
            ->assertForbidden();
    }

    public function test_customer_cannot_access_admin_categories(): void
    {
        $customer = User::factory()->role('customer')->create();

        $this->actingAs($customer)
            ->get(route('admin.categories.index'))
            ->assertForbidden();
    }

    public function test_admin_can_create_product(): void
    {
        Storage::fake('public');

        $admin    = User::factory()->admin()->create();
        $category = Category::factory()->create();

        $this->actingAs($admin)
            ->post(route('admin.products.store'), [
                'name'        => 'Sản phẩm test',
                'price'       => 150000,
                'category_id' => $category->id,
                'stock'       => 10,
                'status'      => true,
                'images'      => [UploadedFile::fake()->image('product.jpg')],
            ])
            ->assertRedirect(route('admin.products.index'));

        $this->assertDatabaseHas('products', ['name' => 'Sản phẩm test']);
    }

    public function test_customer_cannot_create_product(): void
    {
        $customer = User::factory()->role('customer')->create();
        $category = Category::factory()->create();

        $this->actingAs($customer)
            ->post(route('admin.products.store'), [
                'name'        => 'Hack attempt',
                'price'       => 100,
                'category_id' => $category->id,
                'stock'       => 1,
            ])
            ->assertForbidden();
    }

    public function test_admin_can_delete_product(): void
    {
        $admin   = User::factory()->admin()->create();
        $product = Product::factory()->create();

        $this->actingAs($admin)
            ->delete(route('admin.products.destroy', $product))
            ->assertRedirect(route('admin.products.index'));

        $this->assertSoftDeleted('products', ['id' => $product->id]);
    }
}
