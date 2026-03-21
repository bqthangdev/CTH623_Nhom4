<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Review;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class ReviewService
{
    public function store(int $userId, Product $product, int $rating, ?string $comment, ?int $orderId = null): Review
    {
        if (! $this->hasPurchased($userId, $product->id, $orderId)) {
            throw ValidationException::withMessages([
                'review' => 'Bạn chỉ có thể đánh giá sản phẩm sau khi đã mua và nhận hàng.',
            ]);
        }

        if ($this->hasReviewed($userId, $product->id, $orderId)) {
            throw ValidationException::withMessages([
                'review' => 'Bạn đã đánh giá sản phẩm này rồi.',
            ]);
        }

        return Review::create([
            'user_id'    => $userId,
            'product_id' => $product->id,
            'order_id'   => $orderId,
            'rating'     => $rating,
            'comment'    => $comment,
        ]);
    }

    public function hasReviewed(int $userId, int $productId, ?int $orderId = null): bool
    {
        return Review::where('user_id', $userId)
            ->where('product_id', $productId)
            ->where('order_id', $orderId)
            ->exists();
    }

    public function hasPurchased(int $userId, int $productId, ?int $orderId = null): bool
    {
        return OrderItem::whereHas('order', function ($q) use ($userId, $orderId) {
            $q->where('user_id', $userId)
              ->where('status', 'delivered')
              ->where('delivered_at', '>=', now()->subDays(5));
            if ($orderId !== null) {
                $q->where('id', $orderId);
            }
        })->where('product_id', $productId)->exists();
    }

    /**
     * Returns review display data for the order show page.
     *
     * @return array{reviewedProductIds: int[], canReview: bool, reviewDeadline: \Carbon\Carbon|null}
     */
    public function getOrderReviewInfo(Order $order, int $userId): array
    {
        if ($order->status !== 'delivered') {
            return ['reviewedProductIds' => [], 'canReview' => false, 'reviewDeadline' => null];
        }

        $productIds         = $order->items->pluck('product_id')->filter()->all();
        $reviewedProductIds = Review::where('user_id', $userId)
            ->where('order_id', $order->id)
            ->whereIn('product_id', $productIds)
            ->pluck('product_id')
            ->all();

        $reviewDeadline = $order->delivered_at?->addDays(5);
        $canReview      = $reviewDeadline?->isFuture() ?? false;

        return compact('reviewedProductIds', 'canReview', 'reviewDeadline');
    }
}
