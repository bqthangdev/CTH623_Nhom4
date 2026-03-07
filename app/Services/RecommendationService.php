<?php

namespace App\Services;

use App\Models\Product;
use App\Models\UserActivity;
use App\Repositories\ProductRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RecommendationService
{
    public function __construct(
        private readonly ProductRepository $productRepository,
    ) {}

    public function getForProduct(Product $product, int $userId = 0, int $limit = 8): Collection
    {
        try {
            return $this->fetchFromAiService($product->id, $userId, $limit);
        } catch (\Exception $e) {
            Log::warning('AI recommendation failed, using fallback.', [
                'product_id' => $product->id,
                'error'      => $e->getMessage(),
            ]);

            return $this->productRepository->getSameCategoryExcept($product->category_id, $product->id, $limit);
        }
    }

    private function fetchFromAiService(int $productId, int $userId, int $limit): Collection
    {
        $response = Http::timeout(config('services.ai.timeout', 10))
            ->get(config('services.ai.url') . '/api/recommendations', [
                'product_id' => $productId,
                'user_id'    => $userId ?: null,
                'limit'      => $limit,
            ]);

        if ($response->failed()) {
            throw new \RuntimeException('AI service returned error: ' . $response->status());
        }

        $ids = collect($response->json('recommended_products', []))->pluck('id')->filter()->all();

        if (empty($ids)) {
            throw new \RuntimeException('AI service returned empty recommendations.');
        }

        // Giữ nguyên thứ tự mà AI trả về
        $products = $this->productRepository->getByIds($ids)->keyBy('id');
        return collect($ids)->map(fn ($id) => $products[$id] ?? null)->filter()->values();
    }
}
