<?php

namespace App\Services;

use App\Models\CartItem;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class CartService
{
    public function getItems(User $user): Collection
    {
        return CartItem::with(['product.primaryImage'])
            ->where('user_id', $user->id)
            ->get();
    }

    public function addItem(User $user, int $productId, int $quantity = 1): CartItem
    {
        $item = CartItem::firstOrNew([
            'user_id'    => $user->id,
            'product_id' => $productId,
        ]);

        $item->quantity = $item->exists ? $item->quantity + $quantity : $quantity;
        $item->save();

        return $item;
    }

    public function updateQuantity(User $user, int $cartItemId, int $quantity): CartItem
    {
        $item = CartItem::where('id', $cartItemId)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $item->update(['quantity' => $quantity]);

        return $item->fresh('product');
    }

    public function removeItem(User $user, int $cartItemId): void
    {
        CartItem::where('id', $cartItemId)
            ->where('user_id', $user->id)
            ->delete();
    }

    public function clear(User $user): void
    {
        CartItem::where('user_id', $user->id)->delete();
    }

    public function getTotal(User $user): float
    {
        return $this->getItems($user)->sum('subtotal');
    }

    public function getCount(User $user): int
    {
        return CartItem::where('user_id', $user->id)->sum('quantity');
    }
}
