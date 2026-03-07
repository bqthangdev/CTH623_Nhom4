<?php

namespace App\Services;

use App\Models\Order;
use App\Models\User;
use App\Models\Voucher;
use App\Repositories\OrderRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderService
{
    public function __construct(
        private readonly OrderRepository $orderRepository,
        private readonly CartService $cartService,
    ) {}

    public function placeOrder(User $user, array $data): Order
    {
        $cartItems = $this->cartService->getItems($user);

        if ($cartItems->isEmpty()) {
            throw ValidationException::withMessages([
                'cart' => 'Giỏ hàng trống.',
            ]);
        }

        // Kiểm tra tồn kho
        foreach ($cartItems as $item) {
            if ($item->product->stock < $item->quantity) {
                throw ValidationException::withMessages([
                    'stock' => "Sản phẩm \"{$item->product->name}\" không đủ số lượng trong kho.",
                ]);
            }
        }

        return DB::transaction(function () use ($user, $data, $cartItems) {
            $subtotal = $this->cartService->getTotal($user);
            $discount = 0;
            $voucherId = null;

            if (! empty($data['voucher_code'])) {
                $voucher = Voucher::active()->where('code', $data['voucher_code'])->first();
                if ($voucher) {
                    $discount = $voucher->calculateDiscount($subtotal);
                    $voucherId = $voucher->id;
                    $voucher->increment('used_count');
                }
            }

            $shippingFee = 30000; // Phí vận chuyển cố định
            $total = max(0, $subtotal - $discount + $shippingFee);

            $order = $this->orderRepository->createWithItems(
                [
                    'user_id'          => $user->id,
                    'voucher_id'       => $voucherId,
                    'subtotal'         => $subtotal,
                    'discount'         => $discount,
                    'shipping_fee'     => $shippingFee,
                    'total'            => $total,
                    'payment_method'   => $data['payment_method'],
                    'shipping_address' => $data['shipping_address'],
                    'phone'            => $data['phone'],
                    'recipient_name'   => $data['recipient_name'],
                    'note'             => $data['note'] ?? null,
                ],
                $cartItems->map(fn ($item) => [
                    'product_id'   => $item->product_id,
                    'product_name' => $item->product->name,
                    'price'        => $item->product->effective_price,
                    'quantity'     => $item->quantity,
                ])->all()
            );

            // Giảm tồn kho
            foreach ($cartItems as $item) {
                $item->product->decrement('stock', $item->quantity);
            }

            $this->cartService->clear($user);

            return $order;
        });
    }

    public function cancelOrder(User $user, int $orderId): Order
    {
        $order = $this->orderRepository->findForUser($orderId, $user->id);

        abort_if(! $order, 404);
        abort_if($order->status !== 'pending', 422, 'Chỉ có thể hủy đơn hàng đang chờ xác nhận.');

        $order->update(['status' => 'cancelled']);

        // Hoàn lại tồn kho
        foreach ($order->items as $item) {
            $item->product->increment('stock', $item->quantity);
        }

        return $order->fresh();
    }
}
