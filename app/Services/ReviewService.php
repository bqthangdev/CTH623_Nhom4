<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Review;
use Illuminate\Validation\ValidationException;

class ReviewService
{
    public function store(int $userId, Product $product, int $rating, ?string $comment): Review
    {
        if ($this->hasReviewed($userId, $product->id)) {
            throw ValidationException::withMessages([
                'review' => 'Bạn đã đánh giá sản phẩm này rồi.',
            ]);
        }

        return Review::create([
            'user_id'    => $userId,
            'product_id' => $product->id,
            'rating'     => $rating,
            'comment'    => $comment,
        ]);
    }

    public function hasReviewed(int $userId, int $productId): bool
    {
        return Review::where('user_id', $userId)
            ->where('product_id', $productId)
            ->exists();
    }
}
