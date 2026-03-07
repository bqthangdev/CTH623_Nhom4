<?php

namespace App\Repositories;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class OrderRepository
{
    public function createWithItems(array $orderData, array $items): Order
    {
        $order = Order::create($orderData);

        foreach ($items as $item) {
            OrderItem::create([
                'order_id'     => $order->id,
                'product_id'   => $item['product_id'],
                'product_name' => $item['product_name'],
                'price'        => $item['price'],
                'quantity'     => $item['quantity'],
            ]);
        }

        return $order->load('items');
    }

    public function getForUser(User $user, int $perPage = 10): LengthAwarePaginator
    {
        return Order::with(['items.product'])
            ->where('user_id', $user->id)
            ->latest()
            ->paginate($perPage);
    }

    public function findForUser(int $orderId, int $userId): ?Order
    {
        return Order::with(['items.product', 'voucher'])
            ->where('id', $orderId)
            ->where('user_id', $userId)
            ->first();
    }

    public function getForAdmin(?string $status = null, ?string $search = null, int $perPage = 20): LengthAwarePaginator
    {
        return Order::with(['user', 'items'])
            ->when($status, fn ($q, $s) => $q->where('status', $s))
            ->when($search, function ($q, $keyword) {
                $q->whereHas('user', fn ($q) => $q->where('name', 'like', "%{$keyword}%")
                    ->orWhere('email', 'like', "%{$keyword}%"));
            })
            ->latest()
            ->paginate($perPage);
    }
}
