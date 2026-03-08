<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Wishlist;
use Illuminate\Database\Eloquent\Collection;

class WishlistService
{
    public function getForUser(int $userId): Collection
    {
        return Wishlist::with('product.primaryImage')
            ->where('user_id', $userId)
            ->latest()
            ->get();
    }

    public function toggle(int $userId, int $productId): string
    {
        $exists = Wishlist::where('user_id', $userId)
            ->where('product_id', $productId)
            ->exists();

        if ($exists) {
            Wishlist::where('user_id', $userId)->where('product_id', $productId)->delete();

            return 'removed';
        }

        Wishlist::create(['user_id' => $userId, 'product_id' => $productId]);

        return 'added';
    }

    public function isWishlisted(int $userId, int $productId): bool
    {
        return Wishlist::where('user_id', $userId)
            ->where('product_id', $productId)
            ->exists();
    }
}
