<?php

namespace App\Services;

use App\Models\Product;
use App\Repositories\ProductRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class VisualSearchService
{
    public function __construct(
        private readonly ProductRepository $productRepository,
    ) {}

    public function search(UploadedFile $image): Collection
    {
        try {
            return $this->searchViaAiService($image);
        } catch (\Exception $e) {
            Log::warning('Visual search failed, using fallback.', [
                'error' => $e->getMessage(),
            ]);

            // Fallback: trả về sản phẩm nổi bật
            return $this->productRepository->getFeatured(10);
        }
    }

    private function searchViaAiService(UploadedFile $image): Collection
    {
        $response = Http::timeout(config('services.ai.timeout', 30))
            ->attach('image', $image->getContent(), $image->getClientOriginalName())
            ->post(config('services.ai.url') . '/api/visual-search');

        if ($response->failed()) {
            throw new \RuntimeException('AI service returned error: ' . $response->status());
        }

        $ids = collect($response->json('products', []))->pluck('id')->filter()->all();

        if (empty($ids)) {
            throw new \RuntimeException('AI service returned no matching products.');
        }

        return $this->productRepository->getByIds($ids);
    }
}
