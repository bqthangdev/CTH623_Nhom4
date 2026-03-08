<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\RecommendationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RecommendationController extends Controller
{
    public function __construct(private readonly RecommendationService $recommendationService) {}

    public function index(Request $request, Product $product): JsonResponse
    {
        $limit    = (int) $request->get('limit', 8);
        $products = $this->recommendationService->getForProduct($product, min($limit, 20));

        return response()->json([
            'success' => true,
            'data'    => $products,
        ]);
    }
}
