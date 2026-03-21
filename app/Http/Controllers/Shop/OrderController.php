<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Repositories\OrderRepository;
use App\Services\OrderService;
use App\Services\ReviewService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function __construct(
        private readonly OrderRepository $orderRepository,
        private readonly OrderService $orderService,
        private readonly ReviewService $reviewService,
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

        $reviewInfo = $this->reviewService->getOrderReviewInfo($order, auth()->id());

        return view('shop.orders.show', array_merge(compact('order'), $reviewInfo));
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
        $prepared = $this->orderService->prepareReorder($order);

        if (empty($prepared['items'])) {
            return back()->with('error', 'Tất cả sản phẩm trong đơn hàng này hiện đã hết hàng.');
        }

        session(['reorder_items' => $prepared['items']]);

        $message = empty($prepared['skipped'])
            ? 'Vui lòng kiểm tra và xác nhận đơn hàng.'
            : 'Một số sản phẩm không còn hàng đã được bỏ qua: ' . implode(', ', $prepared['skipped']) . '.';

        return redirect()->route('shop.checkout.reorder')->with('info', $message);
    }
}
