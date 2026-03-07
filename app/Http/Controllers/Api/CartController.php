<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function __construct(private readonly CartService $cartService) {}

    public function index(): JsonResponse
    {
        $items = $this->cartService->getItems(auth()->user());
        $total = $this->cartService->getTotal(auth()->user());
        $count = $this->cartService->getCount(auth()->user());

        return response()->json([
            'success' => true,
            'data'    => [
                'items' => $items,
                'total' => $total,
                'count' => $count,
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'quantity'   => ['required', 'integer', 'min:1', 'max:100'],
        ]);

        $this->cartService->addItem(
            auth()->user(),
            $request->product_id,
            $request->quantity
        );

        return response()->json([
            'success'    => true,
            'cart_count' => $this->cartService->getCount(auth()->user()),
            'message'    => 'Đã thêm vào giỏ hàng.',
        ]);
    }

    public function update(Request $request, int $cartItem): JsonResponse
    {
        $request->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:100'],
        ]);

        $this->cartService->updateQuantity(auth()->user(), $cartItem, $request->quantity);

        return response()->json([
            'success'    => true,
            'cart_count' => $this->cartService->getCount(auth()->user()),
            'cart_total' => $this->cartService->getTotal(auth()->user()),
        ]);
    }

    public function destroy(int $cartItem): JsonResponse
    {
        $this->cartService->removeItem(auth()->user(), $cartItem);

        return response()->json([
            'success'    => true,
            'cart_count' => $this->cartService->getCount(auth()->user()),
            'cart_total' => $this->cartService->getTotal(auth()->user()),
        ]);
    }

    public function count(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'count'   => $this->cartService->getCount(auth()->user()),
        ]);
    }
}
