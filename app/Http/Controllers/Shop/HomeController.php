<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Repositories\ProductRepository;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __construct(
        private readonly ProductRepository $productRepository,
    ) {}

    public function index(): View
    {
        $banners          = Banner::active()->get();
        $featuredProducts = $this->productRepository->getFeatured(8);

        return view('shop.home', compact('banners', 'featuredProducts'));
    }
}
