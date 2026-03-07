<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\CartItem;
use App\Models\Product;
use App\Services\CartService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CartController extends Controller
{
    public function __construct(
        private readonly CartService $cartService,
    ) {}

    public function index(): View
    {
        $cartItems = $this->cartService->getItems(auth()->user());
        $total     = $this->cartService->getTotal(auth()->user());

        return view('shop.cart.index', compact('cartItems', 'total'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'quantity'   => ['required', 'integer', 'min:1', 'max:100'],
        ]);

        $product = Product::findOrFail($request->product_id);

        if ($product->stock < $request->quantity) {
            return back()->with('error', 'Sản phẩm không đủ số lượng trong kho.');
        }

        $this->cartService->addItem(auth()->user(), $request->product_id, $request->quantity);

        return back()->with('success', 'Đã thêm vào giỏ hàng!');
    }

    public function update(Request $request, CartItem $cartItem): RedirectResponse
    {
        abort_unless($cartItem->user_id === auth()->id(), 403);

        $request->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:100'],
        ]);

        $this->cartService->updateQuantity(auth()->user(), $cartItem->id, $request->quantity);

        return back()->with('success', 'Đã cập nhật giỏ hàng.');
    }

    public function destroy(CartItem $cartItem): RedirectResponse
    {
        abort_unless($cartItem->user_id === auth()->id(), 403);

        $this->cartService->removeItem(auth()->user(), $cartItem->id);

        return back()->with('success', 'Đã xóa sản phẩm khỏi giỏ hàng.');
    }
}
