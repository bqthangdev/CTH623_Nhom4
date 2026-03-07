<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Services\VisualSearchService;
use Illuminate\Http\Request;
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

    public function search(Request $request): View
    {
        $request->validate([
            'image' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ], [
            'image.required' => 'Vui lòng chọn một hình ảnh.',
            'image.image'    => 'File phải là hình ảnh.',
            'image.max'      => 'Ảnh không được vượt quá 4MB.',
        ]);

        $results = $this->visualSearchService->search($request->file('image'));

        return view('shop.visual-search', compact('results'));
    }
}
