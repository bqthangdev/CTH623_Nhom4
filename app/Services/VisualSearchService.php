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

    /**
     * Search for products visually similar to the uploaded image.
     *
     * @return object{products: Collection, detectedObject: string|null, embeddingMethod: string|null}
     */
    public function search(UploadedFile $image): object
    {
        Log::info('[VisualSearch] Request received.', [
            'file'    => $image->getClientOriginalName(),
            'size'    => $image->getSize(),
            'mime'    => $image->getMimeType(),
        ]);

        try {
            $result = $this->searchViaAiService($image);

            Log::info('[VisualSearch] Search completed.', [
                'embedding_method' => $result->embeddingMethod,
                'result_count'     => $result->products->count(),
            ]);

            return $result;
        } catch (\Exception $e) {
            Log::warning('[VisualSearch] Failed, falling back to featured products.', [
                'error' => $e->getMessage(),
            ]);

            return (object) [
                'products' => $this->productRepository->getFeatured(10),
            ];
        }
    }

    private function searchViaAiService(UploadedFile $image): object
    {
        Log::info('[VisualSearch] Calling AI service.', [
            'url' => config('services.ai.url') . '/api/visual-search',
        ]);

        $response = Http::timeout(config('services.ai.timeout', 30))
            ->attach('image', $image->getContent(), $image->getClientOriginalName())
            ->post(config('services.ai.url') . '/api/visual-search');

        if ($response->failed()) {
            throw new \RuntimeException('AI service returned error: ' . $response->status());
        }

        $productData = collect($response->json('products', []));
        $ids         = $productData->pluck('id')->filter()->all();
        $scoreMap    = $productData->keyBy('id')->map(fn ($item) => $item['similarity_score'] ?? null);

        if (empty($ids)) {
            throw new \RuntimeException('AI service returned no matching products.');
        }

        $idOrder  = array_flip($ids);
        $products = $this->productRepository->getByIds($ids)
            ->sortBy(fn ($p) => $idOrder[$p->id] ?? PHP_INT_MAX)
            ->each(fn ($p) => $p->similarity_score = $scoreMap->get($p->id))
            ->values();

        return (object) [
            'products'        => $products,
            'embeddingMethod' => $response->json('embedding_method'),
        ];
    }
}
