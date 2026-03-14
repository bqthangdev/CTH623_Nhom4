<?php

namespace App\Services;

use App\Models\Product;
use App\Repositories\ProductRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RecommendationService
{
    public function __construct(
        private readonly ProductRepository $productRepository,
    ) {}

    /**
     * Products visually similar to a given product (for product detail page).
     */
    public function getForProduct(Product $product, int $limit = 8): Collection
    {
        try {
            return $this->fetchOrdered('/api/recommendations/similar', [
                'product_id' => $product->id,
                'limit'      => $limit,
            ]);
        } catch (\Exception $e) {
            Log::warning('AI similar-products failed, using fallback.', [
                'product_id' => $product->id,
                'error'      => $e->getMessage(),
            ]);

            return $this->productRepository->getSameCategoryExcept($product->category_id, $product->id, $limit);
        }
    }

    /**
     * Products personalised to a user's purchase history (for home page).
     */
    public function getPersonalized(int $userId, int $limit = 8): Collection
    {
        try {
            return $this->fetchOrdered('/api/recommendations/personal', [
                'user_id' => $userId,
                'limit'   => $limit,
            ]);
        } catch (\Exception $e) {
            Log::warning('AI personalized recommendations failed, using fallback.', [
                'user_id' => $userId,
                'error'   => $e->getMessage(),
            ]);

            return $this->productRepository->getFeatured($limit);
        }
    }

    /**
     * Call an AI recommendation endpoint and return products in AI-ranked order.
     */
    private function fetchOrdered(string $path, array $params): Collection
    {
        $response = Http::timeout(config('services.ai.timeout', 10))
            ->get(config('services.ai.url') . $path, $params);

        if ($response->failed()) {
            throw new \RuntimeException('AI service returned error: ' . $response->status());
        }

        $ids = collect($response->json('recommended_products', []))->pluck('id')->filter()->all();

        if (empty($ids)) {
            throw new \RuntimeException('AI service returned empty list.');
        }

        Log::info('AI recommendations success.', [
            'path'             => $path,
            'params'           => $params,
            'count'            => count($ids),
            'embedding_method' => $response->json('embedding_method', 'unknown'),
        ]);

        // Preserve the ranking order returned by the AI service
        $idOrder = array_flip($ids);

        return $this->productRepository->getByIds($ids)
            ->sortBy(fn (Product $product) => $idOrder[$product->id] ?? PHP_INT_MAX)
            ->values();
    }
}
