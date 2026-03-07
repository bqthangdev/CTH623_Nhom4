<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\VisualSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VisualSearchController extends Controller
{
    public function __construct(private readonly VisualSearchService $visualSearchService) {}

    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $products = $this->visualSearchService->search($request->file('image'));

        return response()->json([
            'success' => true,
            'data'    => $products,
        ]);
    }
}
