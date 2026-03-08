<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Http\Requests\Shop\AddToCartRequest;
use App\Http\Requests\Shop\UpdateCartItemRequest;
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

    public function index(Request $request): View
    {
        /** @var \App\Models\User $user */
        $user      = $request->user();
        $cartItems = $this->cartService->getItems($user);
        $total     = $this->cartService->getTotal($user);

        return view('shop.cart.index', compact('cartItems', 'total'));
    }

    public function store(AddToCartRequest $request): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user    = $request->user();
        $product = Product::findOrFail($request->validated('product_id'));

        if ($product->stock < $request->validated('quantity')) {
            return back()->with('error', 'Sản phẩm không đủ số lượng trong kho.');
        }

        $this->cartService->addItem($user, $product->id, $request->validated('quantity'));

        return back()->with('success', 'Đã thêm vào giỏ hàng!');
    }

    public function update(UpdateCartItemRequest $request, CartItem $cartItem): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();
        abort_unless($cartItem->user_id === $user->id, 403);

        $this->cartService->updateQuantity($user, $cartItem->id, $request->validated('quantity'));

        return back()->with('success', 'Đã cập nhật giỏ hàng.');
    }

    public function destroy(Request $request, CartItem $cartItem): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();
        abort_unless($cartItem->user_id === $user->id, 403);

        $this->cartService->removeItem($user, $cartItem->id);

        return back()->with('success', 'Đã xóa sản phẩm khỏi giỏ hàng.');
    }
}
