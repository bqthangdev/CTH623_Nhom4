<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Http\Requests\Shop\StoreReviewRequest;
use App\Models\Category;
use App\Models\Product;
use App\Repositories\ProductRepository;
use App\Services\ProductService;
use App\Services\RecommendationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function __construct(
        private readonly ProductRepository $productRepository,
        private readonly ProductService $productService,
        private readonly RecommendationService $recommendationService,
    ) {}

    public function index(Request $request): View
    {
        $products   = $this->productRepository->getForShop($request);
        $categories = Category::active()->root()->with('children')->orderBy('sort_order')->get();

        return view('shop.products.index', compact('products', 'categories'));
    }

    public function show(Product $product): View
    {
        abort_if(! $product->status, 404);

        $product->load(['images', 'category', 'attributes', 'reviews.user']);

        $this->productService->recordView($product, auth()->id());

        $recommendations = $this->recommendationService->getForProduct($product);

        return view('shop.products.show', compact('product', 'recommendations'));
    }
}
