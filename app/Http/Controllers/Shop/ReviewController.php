<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Http\Requests\Shop\StoreReviewRequest;
use App\Models\Product;
use App\Services\ReviewService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;

class ReviewController extends Controller
{
    public function __construct(
        private readonly ReviewService $reviewService,
    ) {}

    public function store(StoreReviewRequest $request, Product $product): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        try {
            $this->reviewService->store(
                $user->id,
                $product,
                $request->validated('rating'),
                $request->validated('comment'),
            );
        } catch (ValidationException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Cảm ơn bạn đã đánh giá sản phẩm!');
    }
}
