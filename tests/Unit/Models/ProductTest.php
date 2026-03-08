<?php

namespace Tests\Unit\Models;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase;

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

    public function test_effective_price_returns_regular_price_when_sale_price_is_zero(): void
    {
        $product = new Product(['price' => 100000, 'sale_price' => 0]);

        $this->assertEquals(100000, $product->effective_price);
    }

    public function test_scope_active_filters_inactive_products(): void
    {
        Product::factory()->count(3)->create(['status' => true]);
        Product::factory()->count(2)->create(['status' => false]);

        $this->assertEquals(3, Product::active()->count());
    }

    public function test_scope_featured_filters_non_featured_products(): void
    {
        Product::factory()->count(2)->featured()->create();
        Product::factory()->count(4)->create(['is_featured' => false]);

        $this->assertEquals(2, Product::featured()->count());
    }

    public function test_scope_in_stock_excludes_out_of_stock_products(): void
    {
        Product::factory()->count(3)->inStock(5)->create();
        Product::factory()->count(2)->outOfStock()->create();

        $this->assertEquals(3, Product::inStock()->count());
    }

    public function test_product_has_fillable_fields(): void
    {
        $product = Product::factory()->make();

        $this->assertNotNull($product->name);
        $this->assertNotNull($product->price);
        $this->assertNotNull($product->stock);
    }

    public function test_price_is_cast_to_decimal(): void
    {
        $product = new Product(['price' => 100000]);

        $this->assertIsFloat($product->effective_price);
    }

    public function test_status_is_cast_to_boolean(): void
    {
        $product = Product::factory()->create(['status' => 1]);

        $this->assertTrue($product->status);
    }

    public function test_is_featured_is_cast_to_boolean(): void
    {
        $product = Product::factory()->create(['is_featured' => 0]);

        $this->assertFalse($product->is_featured);
    }
}
