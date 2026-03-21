<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Http\Requests\Shop\AddToCartRequest;
use App\Http\Requests\Shop\UpdateCartItemRequest;
use App\Models\CartItem;
use App\Models\Product;
use App\Services\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
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
        $messages  = $this->cartService->sanitizeStockQuantities($user);
        $cartItems = $this->cartService->getItems($user);
        $total     = $this->cartService->getTotal($user);

        return view('shop.cart.index', compact('cartItems', 'total', 'messages'));
    }

    public function store(AddToCartRequest $request): RedirectResponse|JsonResponse
    {
        /** @var \App\Models\User $user */
        $user    = $request->user();
        $product = Product::findOrFail($request->validated('product_id'));

        try {
            $this->cartService->addItem($user, $product->id, $request->validated('quantity'));
        } catch (ValidationException $e) {
            $message = collect($e->errors())->flatten()->first();
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $message], 422);
            }
            return back()->with('error', $message);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success'    => true,
                'cart_count' => $this->cartService->getCount($user),
                'message'    => 'Đã thêm vào giỏ hàng!',
            ]);
        }

        return back()->with('success', 'Đã thêm vào giỏ hàng!');
    }

    public function update(UpdateCartItemRequest $request, CartItem $cartItem): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();
        abort_unless($cartItem->user_id === $user->id, 403);

        try {
            $this->cartService->updateQuantity($user, $cartItem->id, $request->validated('quantity'));
        } catch (ValidationException $e) {
            return back()->with('error', collect($e->errors())->flatten()->first());
        }

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
