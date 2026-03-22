<?php

namespace App\Services;

use App\Models\CartItem;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\UserAddress;
use App\Models\Voucher;
use App\Repositories\OrderRepository;
use Illuminate\Support\Collection;
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
        $allCartItems = $this->cartService->getItems($user);

        $selectedIds = array_filter(array_map('intval', $data['selected_cart_item_ids'] ?? []));
        $cartItems   = ! empty($selectedIds)
            ? $allCartItems->filter(fn ($item) => in_array($item->id, $selectedIds))->values()
            : $allCartItems;

        if ($cartItems->isEmpty()) {
            throw ValidationException::withMessages([
                'cart' => 'Vui lòng chọn ít nhất một sản phẩm để thanh toán.',
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

        // Resolve shipping address from saved address or inline fields
        if (! empty($data['address_id'])) {
            $savedAddress = UserAddress::where('id', $data['address_id'])
                ->where('user_id', $user->id)
                ->firstOrFail();
            $recipientName   = $savedAddress->recipient_name;
            $phone           = $savedAddress->phone;
            $shippingAddress = $savedAddress->address;
        } else {
            $recipientName   = $data['recipient_name'];
            $phone           = $data['phone'];
            $shippingAddress = $data['shipping_address'];
        }

        return DB::transaction(function () use ($user, $data, $cartItems, $recipientName, $phone, $shippingAddress) {
            $subtotal  = $cartItems->sum('subtotal');
            $discount  = 0;
            $voucherId = null;

            if (! empty($data['voucher_code'])) {
                $voucher = Voucher::active()->where('code', $data['voucher_code'])->first();
                if ($voucher) {
                    $discount  = $voucher->calculateDiscount($subtotal);
                    $voucherId = $voucher->id;
                    $voucher->increment('used_count');
                }
            }

            $shippingFee = 30000; // Phí vận chuyển cố định
            $total       = max(0, $subtotal - $discount + $shippingFee);

            $order = $this->orderRepository->createWithItems(
                [
                    'user_id'          => $user->id,
                    'voucher_id'       => $voucherId,
                    'subtotal'         => $subtotal,
                    'discount'         => $discount,
                    'shipping_fee'     => $shippingFee,
                    'total'            => $total,
                    'payment_method'   => $data['payment_method'],
                    'shipping_address' => $shippingAddress,
                    'phone'            => $phone,
                    'recipient_name'   => $recipientName,
                    'note'             => $data['note'] ?? null,
                ],
                $cartItems->map(fn ($item) => [
                    'product_id'   => $item->product_id,
                    'product_name' => $item->product->name,
                    'price'        => $item->product->effective_price,
                    'quantity'     => $item->quantity,
                ])->all()
            );

            // Auto-save address on first order (no address_id provided and user has no addresses yet)
            if (empty($data['address_id']) && $user->addresses()->count() === 0) {
                $user->addresses()->create([
                    'recipient_name' => $recipientName,
                    'phone'          => $phone,
                    'address'        => $shippingAddress,
                    'is_default'     => true,
                ]);
            }

            // Giảm tồn kho
            foreach ($cartItems as $item) {
                $item->product->decrement('stock', $item->quantity);
            }

            $selectedIds = array_filter(array_map('intval', $data['selected_cart_item_ids'] ?? []));
            if (! empty($selectedIds)) {
                $this->cartService->removeMultiple($user, $cartItems->pluck('id')->all());
            } else {
                $this->cartService->clear($user);
            }

            return $order;
        });
    }

    /**
     * Place an order from raw item data (reorder flow).
     * Does not read or modify the user's cart.
     *
     * @param  array<int, array{product_id: int, quantity: int}>  $rawItems
     */
    public function placeOrderFromItems(User $user, array $data, array $rawItems): Order
    {
        $productIds = collect($rawItems)->pluck('product_id')->all();
        $products   = Product::whereIn('id', $productIds)->get()->keyBy('id');

        // Validate stock
        foreach ($rawItems as $row) {
            $product = $products->get($row['product_id']);
            if (! $product || $product->stock < $row['quantity']) {
                $name = $product ? $product->name : "ID #{$row['product_id']}";
                throw ValidationException::withMessages([
                    'stock' => "Sản phẩm \"{$name}\" không đủ số lượng trong kho.",
                ]);
            }
        }

        // Resolve shipping address
        if (! empty($data['address_id'])) {
            $savedAddress = UserAddress::where('id', $data['address_id'])
                ->where('user_id', $user->id)
                ->firstOrFail();
            $recipientName   = $savedAddress->recipient_name;
            $phone           = $savedAddress->phone;
            $shippingAddress = $savedAddress->address;
        } else {
            $recipientName   = $data['recipient_name'];
            $phone           = $data['phone'];
            $shippingAddress = $data['shipping_address'];
        }

        return DB::transaction(function () use ($user, $data, $rawItems, $products, $recipientName, $phone, $shippingAddress) {
            $subtotal = collect($rawItems)->sum(
                fn ($row) => $products->get($row['product_id'])->effective_price * $row['quantity']
            );
            $discount  = 0;
            $voucherId = null;

            if (! empty($data['voucher_code'])) {
                $voucher = Voucher::active()->where('code', $data['voucher_code'])->first();
                if ($voucher) {
                    $discount  = $voucher->calculateDiscount($subtotal);
                    $voucherId = $voucher->id;
                    $voucher->increment('used_count');
                }
            }

            $shippingFee = 30000;
            $total       = max(0, $subtotal - $discount + $shippingFee);

            $order = $this->orderRepository->createWithItems(
                [
                    'user_id'          => $user->id,
                    'voucher_id'       => $voucherId,
                    'subtotal'         => $subtotal,
                    'discount'         => $discount,
                    'shipping_fee'     => $shippingFee,
                    'total'            => $total,
                    'payment_method'   => $data['payment_method'],
                    'shipping_address' => $shippingAddress,
                    'phone'            => $phone,
                    'recipient_name'   => $recipientName,
                    'note'             => $data['note'] ?? null,
                ],
                collect($rawItems)->map(fn ($row) => [
                    'product_id'   => $row['product_id'],
                    'product_name' => $products->get($row['product_id'])->name,
                    'price'        => $products->get($row['product_id'])->effective_price,
                    'quantity'     => $row['quantity'],
                ])->all()
            );

            // Decrement stock
            foreach ($rawItems as $row) {
                $products->get($row['product_id'])->decrement('stock', $row['quantity']);
            }

            // Auto-save address on first order when no saved address used
            if (empty($data['address_id']) && $user->addresses()->count() === 0) {
                $user->addresses()->create([
                    'recipient_name' => $recipientName,
                    'phone'          => $phone,
                    'address'        => $shippingAddress,
                    'is_default'     => true,
                ]);
            }

            return $order;
        });
    }

    /**
     * Filters order items by stock availability for the reorder flow.
     *
     * @return array{items: array<int, array{product_id: int, quantity: int}>, skipped: string[]}
     */
    public function prepareReorder(Order $order): array
    {
        $reorderItems = [];
        $skipped      = [];

        foreach ($order->items as $item) {
            if (! $item->product || $item->product->stock <= 0) {
                $skipped[] = $item->product_name;
                continue;
            }

            $reorderItems[] = [
                'product_id' => $item->product_id,
                'quantity'   => min($item->quantity, $item->product->stock),
            ];
        }

        return ['items' => $reorderItems, 'skipped' => $skipped];
    }

    /**
     * Builds virtual CartItem objects from raw session data for the reorder checkout view.
     * Does not touch the user's actual cart.
     */
    public function buildReorderCheckoutItems(array $rawItems): Collection
    {
        $productIds = collect($rawItems)->pluck('product_id')->all();
        $products   = Product::with('primaryImage')->whereIn('id', $productIds)->get()->keyBy('id');

        return collect($rawItems)->map(function (array $row) use ($products): ?CartItem {
            $product = $products->get($row['product_id']);
            if (! $product) {
                return null;
            }

            $cartItem = new CartItem([
                'product_id' => $row['product_id'],
                'quantity'   => $row['quantity'],
            ]);
            $cartItem->setRelation('product', $product);

            return $cartItem;
        })->filter()->values();
    }

    public function updateStatus(Order $order, string $newStatus): Order
    {
        $allowed = $order->allowedAdminTransitions();

        if (! in_array($newStatus, $allowed, true)) {
            throw ValidationException::withMessages([
                'status' => 'Không thể chuyển sang trạng thái này từ trạng thái hiện tại.',
            ]);
        }

        if ($newStatus === 'shipping') {
            throw ValidationException::withMessages([
                'status' => 'Chuyển sang trạng thái đang giao hàng cần chọn đơn vị vận chuyển và nhập mã vận đơn.',
            ]);
        }

        $updateData = ['status' => $newStatus];
        if ($newStatus === 'delivered') {
            $updateData['delivered_at'] = now();
        }
        $order->update($updateData);

        return $order->fresh();
    }

    public function dispatchOrder(Order $order, int $carrierId, string $trackingCode): Order
    {
        if ($order->status !== 'confirmed') {
            throw ValidationException::withMessages([
                'status' => 'Chỉ có thể giao hàng với đơn đã xác nhận.',
            ]);
        }

        $order->update([
            'status'              => 'shipping',
            'shipping_carrier_id' => $carrierId,
            'tracking_code'       => $trackingCode,
        ]);

        return $order->fresh();
    }

    public function confirmDelivery(User $user, int $orderId): Order
    {
        $order = $this->orderRepository->findForUser($orderId, $user->id);

        abort_if(! $order, 404);
        abort_if($order->status !== 'shipping', 422, 'Chỉ có thể xác nhận đã nhận hàng với đơn đang được giao.');

        $order->update(['status' => 'delivered', 'delivered_at' => now()]);

        return $order->fresh();
    }

    public function cancelOrder(User $user, int $orderId): Order
    {
        $order = $this->orderRepository->findForUser($orderId, $user->id);

        abort_if(! $order, 404);
        abort_if(! in_array($order->status, ['pending', 'confirmed'], true), 422, 'Chỉ có thể hủy đơn hàng chưa được giao.');

        $order->update(['status' => 'cancelled']);

        // Hoàn lại tồn kho
        foreach ($order->items as $item) {
            $item->product->increment('stock', $item->quantity);
        }

        return $order->fresh();
    }
}
