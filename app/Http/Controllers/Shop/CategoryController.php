<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Repositories\ProductRepository;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function __construct(
        private readonly ProductRepository $productRepository,
    ) {}

    public function show(Category $category, Request $request): View
    {
        abort_if(! $category->is_active, 404);

        $request->merge(['category' => $category->slug]);
        $products   = $this->productRepository->getForShop($request);
        $categories = Category::active()->root()->with('children')->orderBy('sort_order')->get();

        return view('shop.products.index', compact('products', 'categories', 'category'));
    }
}
