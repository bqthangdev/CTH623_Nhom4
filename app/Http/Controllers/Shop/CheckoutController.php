<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Http\Requests\Shop\CheckoutRequest;
use App\Models\PaymentMethod;
use App\Services\CartService;
use App\Services\OrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    public function __construct(
        private readonly CartService $cartService,
        private readonly OrderService $orderService,
    ) {}

    public function index(): View|RedirectResponse
    {
        $cartItems = $this->cartService->getItems(auth()->user());

        if ($cartItems->isEmpty()) {
            return redirect()->route('shop.cart.index')->with('error', 'Giỏ hàng của bạn đang trống.');
        }

        $subtotal       = $this->cartService->getTotal(auth()->user());
        $paymentMethods = PaymentMethod::active()->get();

        return view('shop.checkout.index', compact('cartItems', 'subtotal', 'paymentMethods'));
    }

    public function store(CheckoutRequest $request): RedirectResponse
    {
        $order = $this->orderService->placeOrder(auth()->user(), $request->validated());

        return redirect()->route('shop.orders.show', $order)
            ->with('success', 'Đặt hàng thành công! Mã đơn hàng #' . $order->id);
    }
}
