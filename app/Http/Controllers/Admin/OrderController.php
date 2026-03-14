<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateOrderShippingRequest;
use App\Http\Requests\Admin\UpdateOrderStatusRequest;
use App\Models\Order;
use App\Repositories\OrderRepository;
use App\Services\OrderService;
use App\Services\ShippingCarrierService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function __construct(
        private readonly OrderRepository $orderRepository,
        private readonly OrderService $orderService,
        private readonly ShippingCarrierService $carrierService,
    ) {}

    public function index(Request $request): View
    {
        $orders = $this->orderRepository->getForAdmin(
            status: $request->status,
            search: $request->search,
            perPage: 20
        );

        return view('admin.orders.index', compact('orders'));
    }

    public function show(Order $order): View
    {
        $order->load(['user', 'items.product', 'voucher', 'shippingCarrier']);
        $carriers = $this->carrierService->getAllActive();

        return view('admin.orders.show', compact('order', 'carriers'));
    }

    public function update(UpdateOrderStatusRequest $request, Order $order): RedirectResponse
    {
        try {
            $this->orderService->updateStatus($order, $request->validated('status'));
        } catch (ValidationException $e) {
            return back()->with('error', collect($e->errors())->flatten()->first());
        }

        return back()->with('success', 'Cập nhật trạng thái đơn hàng thành công!');
    }

    public function updateShipping(UpdateOrderShippingRequest $request, Order $order): RedirectResponse
    {
        try {
            $data = $request->validated();
            $this->orderService->dispatchOrder($order, $data['shipping_carrier_id'], $data['tracking_code']);
        } catch (ValidationException $e) {
            return back()->with('error', collect($e->errors())->flatten()->first());
        }

        return back()->with('success', 'Đã chuyển đơn sang trạng thái đang giao hàng!');
    }
}
