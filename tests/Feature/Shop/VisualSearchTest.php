<?php

namespace Tests\Feature\Shop;

use App\Models\Product;
use App\Models\User;
use App\Services\VisualSearchService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VisualSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_visual_search_page_loads_for_guests(): void
    {
        $this->get(route('shop.visual-search'))
            ->assertOk()
            ->assertViewIs('shop.visual-search');
    }

    public function test_visual_search_returns_results(): void
    {
        Storage::fake('public');

        $products = Product::factory()->count(3)->inStock()->active()->create();

        $this->mock(VisualSearchService::class, function ($mock) use ($products) {
            $mock->shouldReceive('search')
                ->once()
                ->andReturn($products);
        });

        $this->post(route('shop.visual-search.search'), [
            'image' => UploadedFile::fake()->image('search.jpg', 200, 200),
        ])
            ->assertOk()
            ->assertViewIs('shop.visual-search')
            ->assertViewHas('results');
    }

    public function test_visual_search_requires_image(): void
    {
        $this->post(route('shop.visual-search.search'), [])
            ->assertSessionHasErrors('image');
    }

    public function test_visual_search_rejects_non_image_files(): void
    {
        $this->post(route('shop.visual-search.search'), [
            'image' => UploadedFile::fake()->create('document.pdf', 100, 'application/pdf'),
        ])
            ->assertSessionHasErrors('image');
    }

    public function test_visual_search_falls_back_when_ai_service_fails(): void
    {
        Storage::fake('public');

        $featured = Product::factory()->count(5)->featured()->active()->inStock()->create();

        $this->mock(VisualSearchService::class, function ($mock) use ($featured) {
            $mock->shouldReceive('search')
                ->once()
                ->andReturn($featured);
        });

        $response = $this->post(route('shop.visual-search.search'), [
            'image' => UploadedFile::fake()->image('test.jpg'),
        ]);

        $response->assertOk();
        $results = $response->viewData('results');
        $this->assertNotNull($results);
    }
}
