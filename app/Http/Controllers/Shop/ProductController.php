<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Repositories\ProductRepository;
use App\Services\ProductService;
use App\Services\RecommendationService;
use App\Services\WishlistService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function __construct(
        private readonly ProductRepository $productRepository,
        private readonly ProductService $productService,
        private readonly RecommendationService $recommendationService,
        private readonly WishlistService $wishlistService,
    ) {}

    public function index(Request $request): View
    {
        $products   = $this->productRepository->getForShop($request);
        $categories = Category::active()->root()->with('children')->orderBy('sort_order')->get();

        return view('shop.products.index', compact('products', 'categories'));
    }

    public function show(Request $request, Product $product): View
    {
        abort_if(! $product->status, 404);

        $product->load(['images', 'category', 'attributes', 'reviews.user']);

        $this->productService->recordView($product, $request->user()?->getAuthIdentifier());

        $recommendations = $this->recommendationService->getForProduct($product);
        $inWishlist      = $request->user()
            ? $this->wishlistService->isWishlisted($request->user()->id, $product->id)
            : false;

        return view('shop.products.show', compact('product', 'recommendations', 'inWishlist'));
    }
}
