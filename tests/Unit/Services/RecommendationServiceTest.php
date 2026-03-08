<?php

namespace Tests\Unit\Services;

use App\Models\Product;
use App\Repositories\ProductRepository;
use App\Services\RecommendationService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class RecommendationServiceTest extends TestCase
{
    use RefreshDatabase;

    private RecommendationService $service;

    /** @var MockInterface&ProductRepository */
    private MockInterface $productRepository;

    protected function setUp(): void
    {
        parent::setUp();

        /** @var MockInterface&ProductRepository $mock */
        $mock                    = Mockery::mock(ProductRepository::class);
        $this->productRepository = $mock;
        $this->service           = new RecommendationService($this->productRepository);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_get_for_product_returns_ai_recommendations(): void
    {
        $product = Product::factory()->create();

        $aiProducts = new Collection([
            Product::factory()->make(['id' => 10]),
            Product::factory()->make(['id' => 11]),
        ]);

        Http::fake([
            '*recommendations/similar*' => Http::response([
                'recommended_products' => [
                    ['id' => 10],
                    ['id' => 11],
                ],
            ], 200),
        ]);

        $this->productRepository
            ->shouldReceive('getByIds')
            ->once()
            ->andReturn($aiProducts);

        $result = $this->service->getForProduct($product, 8);

        $this->assertInstanceOf(Collection::class, $result);
    }

    public function test_get_for_product_falls_back_on_ai_failure(): void
    {
        $product          = Product::factory()->create();
        $fallbackProducts = new Collection([Product::factory()->make()]);

        Http::fake([
            '*recommendations/similar*' => Http::response([], 500),
        ]);

        $this->productRepository
            ->shouldReceive('getSameCategoryExcept')
            ->once()
            ->andReturn($fallbackProducts);

        $result = $this->service->getForProduct($product, 8);

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(1, $result);
    }

    public function test_get_personalized_returns_ai_recommendations(): void
    {
        $aiProducts = new Collection([Product::factory()->make()]);

        Http::fake([
            '*recommendations/personal*' => Http::response([
                'recommended_products' => [['id' => 20]],
            ], 200),
        ]);

        $this->productRepository
            ->shouldReceive('getByIds')
            ->once()
            ->andReturn($aiProducts);

        $result = $this->service->getPersonalized(42, 8);

        $this->assertInstanceOf(Collection::class, $result);
    }

    public function test_get_personalized_falls_back_on_ai_failure(): void
    {
        $featured = new Collection([Product::factory()->make()]);

        Http::fake([
            '*recommendations/personal*' => Http::response([], 503),
        ]);

        $this->productRepository
            ->shouldReceive('getFeatured')
            ->once()
            ->andReturn($featured);

        $result = $this->service->getPersonalized(42, 8);

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(1, $result);
    }

    public function test_get_for_product_falls_back_when_ai_returns_empty_list(): void
    {
        $product          = Product::factory()->create();
        $fallbackProducts = new Collection([Product::factory()->make()]);

        Http::fake([
            '*recommendations/similar*' => Http::response(['recommended_products' => []], 200),
        ]);

        $this->productRepository
            ->shouldReceive('getSameCategoryExcept')
            ->once()
            ->andReturn($fallbackProducts);

        $result = $this->service->getForProduct($product, 8);

        $this->assertInstanceOf(Collection::class, $result);
    }
}
