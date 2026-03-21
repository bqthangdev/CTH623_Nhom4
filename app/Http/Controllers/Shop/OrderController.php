<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Repositories\OrderRepository;
use App\Services\OrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function __construct(
        private readonly OrderRepository $orderRepository,
        private readonly OrderService $orderService,
    ) {}

    public function index(): View
    {
        $orders = $this->orderRepository->getForUser(auth()->user());

        return view('shop.orders.index', compact('orders'));
    }

    public function show(int $order): View
    {
        $order = $this->orderRepository->findForUser($order, auth()->id());
        abort_if(! $order, 404);

        $order->load(['items.product', 'voucher', 'shippingCarrier']);

        $reviewedProductIds = [];
        $canReview          = false;
        $reviewDeadline     = null;

        if ($order->status === 'delivered') {
            $productIds         = $order->items->pluck('product_id')->filter()->all();
            $reviewedProductIds = Review::where('user_id', auth()->id())
                ->where('order_id', $order->id)
                ->whereIn('product_id', $productIds)
                ->pluck('product_id')
                ->all();

            if ($order->delivered_at) {
                $reviewDeadline = $order->delivered_at->addDays(5);
                $canReview      = $reviewDeadline->isFuture();
            }
        }

        return view('shop.orders.show', compact('order', 'reviewedProductIds', 'canReview', 'reviewDeadline'));
    }

    public function cancel(int $order): RedirectResponse
    {
        $this->orderService->cancelOrder(auth()->user(), $order);

        return back()->with('success', 'Đơn hàng đã được hủy.');
    }

    public function confirmDelivery(int $order): RedirectResponse
    {
        $this->orderService->confirmDelivery(auth()->user(), $order);

        return back()->with('success', 'Cảm ơn bạn đã xác nhận nhận hàng!');
    }

    public function reorder(int $order): RedirectResponse
    {
        $order = $this->orderRepository->findForUser($order, auth()->id());
        abort_if(! $order, 404);
        abort_if(! in_array($order->status, ['delivered', 'cancelled'], true), 422);

        $order->load('items.product');

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

        if (empty($reorderItems)) {
            return back()->with('error', 'Tất cả sản phẩm trong đơn hàng này hiện đã hết hàng.');
        }

        session(['reorder_items' => $reorderItems]);

        $message = 'Vui lòng kiểm tra và xác nhận đơn hàng.';
        if (! empty($skipped)) {
            $message = 'Một số sản phẩm không còn hàng đã được bỏ qua: ' . implode(', ', $skipped) . '.';
        }

        return redirect()->route('shop.checkout.reorder')->with('info', $message);
    }
}
