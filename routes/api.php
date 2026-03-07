<?php

use App\Http\Controllers\Api;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', fn (Request $request) => $request->user());

    // Giỏ hàng
    Route::get('/cart', [Api\CartController::class, 'index']);
    Route::post('/cart/items', [Api\CartController::class, 'store']);
    Route::patch('/cart/items/{cartItem}', [Api\CartController::class, 'update']);
    Route::delete('/cart/items/{cartItem}', [Api\CartController::class, 'destroy']);
    Route::get('/cart/count', [Api\CartController::class, 'count']);

    // Đơn hàng
    Route::get('/orders', [Api\OrderController::class, 'index']);
    Route::get('/orders/{orderId}', [Api\OrderController::class, 'show']);
});

// Visual Search (yêu cầu auth nhưng dùng web session)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/visual-search', [Api\VisualSearchController::class, 'search'])
        ->middleware('throttle:20,1');
});

// Recommendations (public với rate limit)
Route::get('/products/{product}/recommendations', [Api\RecommendationController::class, 'index'])
    ->middleware('throttle:60,1');
