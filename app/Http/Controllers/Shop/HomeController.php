<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Repositories\ProductRepository;
use App\Services\RecommendationService;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __construct(
        private readonly ProductRepository $productRepository,
        private readonly RecommendationService $recommendationService,
    ) {}

    public function index(): View
    {
        $banners          = Banner::active()->get();
        $featuredProducts = $this->productRepository->getFeatured(8);

        $personalizedProducts = auth()->check()
            ? $this->recommendationService->getPersonalized(auth()->id(), 8)
            : collect();

        return view('shop.home', compact('banners', 'featuredProducts', 'personalizedProducts'));
    }
}
