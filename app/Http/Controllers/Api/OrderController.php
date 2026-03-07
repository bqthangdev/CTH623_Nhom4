<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Repositories\OrderRepository;
use Illuminate\Http\JsonResponse;

class OrderController extends Controller
{
    public function __construct(private readonly OrderRepository $orderRepository) {}

    public function index(): JsonResponse
    {
        $orders = $this->orderRepository->getForUser(auth()->user(), perPage: 10);

        return response()->json([
            'success' => true,
            'data'    => $orders,
        ]);
    }

    public function show(int $orderId): JsonResponse
    {
        $order = $this->orderRepository->findForUser($orderId, auth()->id());

        if (! $order) {
            return response()->json(['success' => false, 'message' => 'Đơn hàng không tồn tại.'], 404);
        }

        $order->load('items.product');

        return response()->json([
            'success' => true,
            'data'    => $order,
        ]);
    }
}
