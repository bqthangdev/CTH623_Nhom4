<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
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

        return view('shop.orders.show', compact('order'));
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
}
