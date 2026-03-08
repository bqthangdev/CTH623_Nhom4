<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreCartItemRequest;
use App\Http\Requests\Api\UpdateCartItemRequest;
use App\Services\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function __construct(private readonly CartService $cartService) {}

    public function index(Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user  = $request->user();
        $items = $this->cartService->getItems($user);
        $total = $this->cartService->getTotal($user);
        $count = $this->cartService->getCount($user);

        return response()->json([
            'success' => true,
            'data'    => [
                'items' => $items,
                'total' => $total,
                'count' => $count,
            ],
        ]);
    }

    public function store(StoreCartItemRequest $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();
        $this->cartService->addItem(
            $user,
            $request->validated('product_id'),
            $request->validated('quantity'),
        );

        return response()->json([
            'success'    => true,
            'cart_count' => $this->cartService->getCount($user),
            'message'    => 'Đã thêm vào giỏ hàng.',
        ]);
    }

    public function update(UpdateCartItemRequest $request, int $cartItem): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();
        $this->cartService->updateQuantity($user, $cartItem, $request->validated('quantity'));

        return response()->json([
            'success'    => true,
            'cart_count' => $this->cartService->getCount($user),
            'cart_total' => $this->cartService->getTotal($user),
        ]);
    }

    public function destroy(Request $request, int $cartItem): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();
        $this->cartService->removeItem($user, $cartItem);

        return response()->json([
            'success'    => true,
            'cart_count' => $this->cartService->getCount($user),
            'cart_total' => $this->cartService->getTotal($user),
        ]);
    }

    public function count(Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        return response()->json([
            'success' => true,
            'count'   => $this->cartService->getCount($user),
        ]);
    }
}
