<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Http\Requests\Shop\CheckoutRequest;
use App\Models\CartItem;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Voucher;
use App\Services\CartService;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
        $addresses      = auth()->user()->addresses()->orderByDesc('is_default')->orderByDesc('id')->get();

        return view('shop.checkout.index', compact('cartItems', 'subtotal', 'paymentMethods', 'addresses'));
    }

    public function store(CheckoutRequest $request): RedirectResponse
    {
        $order = $this->orderService->placeOrder(auth()->user(), $request->validated());

        return redirect()->route('shop.orders.show', $order)
            ->with('success', 'Đặt hàng thành công! Mã đơn hàng #' . $order->id)
            ->with('order_just_placed', $order->id);
    }

    public function reorderIndex(): View|RedirectResponse
    {
        $rawItems = session('reorder_items', []);

        if (empty($rawItems)) {
            return redirect()->route('shop.orders.index')->with('error', 'Phiên đặt lại đã hết hạn. Vui lòng thử lại.');
        }

        $productIds = collect($rawItems)->pluck('product_id')->all();
        $products   = Product::with('primaryImage')->whereIn('id', $productIds)->get()->keyBy('id');

        $cartItems = collect($rawItems)->map(function (array $row) use ($products) {
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

        if ($cartItems->isEmpty()) {
            return redirect()->route('shop.orders.index')->with('error', 'Các sản phẩm trong đơn hàng không còn khả dụng.');
        }

        $subtotal       = $cartItems->sum('subtotal');
        $paymentMethods = PaymentMethod::active()->get();
        $addresses      = auth()->user()->addresses()->orderByDesc('is_default')->orderByDesc('id')->get();
        $formAction     = route('shop.checkout.reorder.store');

        return view('shop.checkout.index', compact('cartItems', 'subtotal', 'paymentMethods', 'addresses', 'formAction'));
    }

    public function reorderStore(CheckoutRequest $request): RedirectResponse
    {
        $rawItems = session('reorder_items', []);

        if (empty($rawItems)) {
            return redirect()->route('shop.orders.index')->with('error', 'Phiên đặt lại đã hết hạn. Vui lòng thử lại.');
        }

        $order = $this->orderService->placeOrderFromItems(auth()->user(), $request->validated(), $rawItems);

        session()->forget('reorder_items');

        return redirect()->route('shop.orders.show', $order)
            ->with('success', 'Đặt hàng thành công! Mã đơn hàng #' . $order->id)
            ->with('order_just_placed', $order->id);
    }

    public function validateVoucher(Request $request): JsonResponse
    {
        $code     = trim((string) $request->input('voucher_code', ''));
        $subtotal = (float) $request->input('subtotal', 0);

        if ($code === '') {
            return response()->json(['success' => false, 'message' => 'Vui lòng nhập mã voucher.']);
        }

        $voucher = Voucher::active()->where('code', $code)->first();

        if (! $voucher) {
            return response()->json(['success' => false, 'message' => 'Mã voucher không hợp lệ hoặc đã hết hạn.']);
        }

        if ($subtotal < $voucher->min_order) {
            return response()->json([
                'success' => false,
                'message' => 'Đơn hàng tối thiểu ' . number_format($voucher->min_order) . 'đ để áp dụng voucher này.',
            ]);
        }

        $discount   = $voucher->calculateDiscount($subtotal);
        $shippingFee = 30000;
        $total      = max(0, $subtotal - $discount + $shippingFee);

        return response()->json([
            'success'      => true,
            'message'      => 'Áp dụng voucher thành công!',
            'discount'     => $discount,
            'total'        => $total,
            'discount_fmt' => number_format($discount),
            'total_fmt'    => number_format($total),
        ]);
    }
}
