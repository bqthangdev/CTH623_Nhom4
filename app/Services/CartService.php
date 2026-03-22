<?php

namespace App\Services;

use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

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

        $newTotal      = ($item->exists ? $item->quantity : 0) + $quantity;
        $product       = Product::findOrFail($productId);

        if ($product->stock < $newTotal) {
            $currentInCart = $item->exists ? $item->quantity : 0;
            $available     = max(0, $product->stock - $currentInCart);
            $message = $available > 0
                ? "Chỉ có thể thêm {$available} sản phẩm nữa (kho còn {$product->stock}, giỏ đã có {$currentInCart})."
                : "Sản phẩm \"{$product->name}\" không đủ số lượng trong kho.";

            throw ValidationException::withMessages(['quantity' => $message]);
        }

        $item->quantity = $newTotal;
        $item->save();

        return $item;
    }

    public function updateQuantity(User $user, int $cartItemId, int $quantity): CartItem
    {
        $item = CartItem::with('product')
            ->where('id', $cartItemId)
            ->where('user_id', $user->id)
            ->firstOrFail();

        if ($item->product->stock < $quantity) {
            throw ValidationException::withMessages([
                'quantity' => "Sản phẩm \"{$item->product->name}\" chỉ còn {$item->product->stock} sản phẩm trong kho.",
            ]);
        }

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

    public function removeMultiple(User $user, array $cartItemIds): void
    {
        CartItem::where('user_id', $user->id)
            ->whereIn('id', $cartItemIds)
            ->delete();
    }

    public function getTotal(User $user): float
    {
        return $this->getItems($user)->sum('subtotal');
    }

    public function getCount(User $user): int
    {
        return CartItem::where('user_id', $user->id)->sum('quantity');
    }

    /**
     * Auto-adjust cart quantities that exceed current stock.
     * Returns warning messages for items that were adjusted or are out of stock.
     */
    public function sanitizeStockQuantities(User $user): array
    {
        $messages  = [];
        $cartItems = $this->getItems($user);

        foreach ($cartItems as $item) {
            $stock = $item->product->stock;

            if ($stock === 0) {
                $messages[] = "Sản phẩm \"{$item->product->name}\" hiện đã hết hàng.";
            } elseif ($item->quantity > $stock) {
                $item->update(['quantity' => $stock]);
                $messages[] = "Số lượng \"{$item->product->name}\" đã được điều chỉnh xuống {$stock} do tồn kho không đủ.";
            }
        }

        return $messages;
    }
}
