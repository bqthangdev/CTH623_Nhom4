<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
    }

    public function test_admin_can_view_product_list(): void
    {
        Product::factory()->count(5)->create();

        $this->actingAs($this->admin)
            ->get(route('admin.products.index'))
            ->assertOk()
            ->assertViewIs('admin.products.index')
            ->assertViewHas('products');
    }

    public function test_admin_can_view_create_product_form(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.products.create'))
            ->assertOk()
            ->assertViewIs('admin.products.create');
    }

    public function test_admin_can_create_product(): void
    {
        Storage::fake('public');

        $category = Category::factory()->create();

        $this->actingAs($this->admin)
            ->post(route('admin.products.store'), [
                'name'        => 'Áo thun cotton',
                'price'       => 250000,
                'category_id' => $category->id,
                'stock'       => 50,
                'status'      => true,
                'images'      => [UploadedFile::fake()->image('product.jpg')],
            ])
            ->assertRedirect(route('admin.products.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('products', [
            'name'  => 'Áo thun cotton',
            'price' => 250000,
        ]);
    }

    public function test_admin_cannot_create_product_without_name(): void
    {
        $category = Category::factory()->create();

        $this->actingAs($this->admin)
            ->post(route('admin.products.store'), [
                'price'       => 250000,
                'category_id' => $category->id,
                'stock'       => 10,
            ])
            ->assertSessionHasErrors('name');
    }

    public function test_admin_can_view_edit_product_form(): void
    {
        $product = Product::factory()->create();

        $this->actingAs($this->admin)
            ->get(route('admin.products.edit', $product))
            ->assertOk()
            ->assertViewIs('admin.products.edit');
    }

    public function test_admin_can_update_product(): void
    {
        Storage::fake('public');

        $product  = Product::factory()->create();
        $category = Category::factory()->create();

        $this->actingAs($this->admin)
            ->put(route('admin.products.update', $product), [
                'name'        => 'Tên sản phẩm mới',
                'price'       => 300000,
                'category_id' => $category->id,
                'stock'       => 25,
                'status'      => true,
            ])
            ->assertRedirect(route('admin.products.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('products', [
            'id'    => $product->id,
            'name'  => 'Tên sản phẩm mới',
            'price' => 300000,
        ]);
    }

    public function test_admin_can_delete_product(): void
    {
        $product = Product::factory()->create();

        $this->actingAs($this->admin)
            ->delete(route('admin.products.destroy', $product))
            ->assertRedirect(route('admin.products.index'))
            ->assertSessionHas('success');

        $this->assertSoftDeleted('products', ['id' => $product->id]);
    }

    public function test_admin_can_filter_products_by_category(): void
    {
        $cat1 = Category::factory()->create();
        $cat2 = Category::factory()->create();

        Product::factory()->count(3)->create(['category_id' => $cat1->id]);
        Product::factory()->count(2)->create(['category_id' => $cat2->id]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.products.index', ['category_id' => $cat1->id]));

        $response->assertOk();
        $products = $response->viewData('products');
        $this->assertEquals(3, $products->total());
    }
}
