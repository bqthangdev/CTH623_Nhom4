<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Http\Requests\Shop\StoreVisualSearchRequest;
use App\Services\VisualSearchService;
use Illuminate\View\View;

class VisualSearchController extends Controller
{
    public function __construct(
        private readonly VisualSearchService $visualSearchService,
    ) {}

    public function index(): View
    {
        return view('shop.visual-search');
    }

    public function search(StoreVisualSearchRequest $request): View
    {
        $results = $this->visualSearchService->search($request->file('image'));

        return view('shop.visual-search', compact('results'));
    }
}
