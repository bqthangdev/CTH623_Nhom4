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
        $imageFile          = $request->file('image');
        $searchImageDataUri = 'data:' . $imageFile->getMimeType() . ';base64,' . base64_encode($imageFile->getContent());

        $searchResult   = $this->visualSearchService->search($imageFile);
        $results        = $searchResult->products;
        $detectedObject = $searchResult->detectedObject;

        return view('shop.visual-search', compact('results', 'detectedObject', 'searchImageDataUri'));
    }
}
