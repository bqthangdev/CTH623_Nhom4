<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\Shop;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

// ───────────────────────────────────────────────
// Shop (Frontend)
// ───────────────────────────────────────────────
Route::get('/', [Shop\HomeController::class, 'index'])->name('home');

Route::get('/products', [Shop\ProductController::class, 'index'])->name('shop.products.index');
Route::get('/products/{product:slug}', [Shop\ProductController::class, 'show'])->name('shop.products.show');

Route::get('/categories/{category:slug}', [Shop\CategoryController::class, 'show'])->name('shop.categories.show');

// Tìm kiếm bằng hình ảnh
Route::get('/visual-search', [Shop\VisualSearchController::class, 'index'])->name('shop.visual-search');
Route::post('/visual-search', [Shop\VisualSearchController::class, 'search'])->name('shop.visual-search.search');

Route::middleware('auth')->group(function () {
    // Giỏ hàng
    Route::get('/cart', [Shop\CartController::class, 'index'])->name('shop.cart.index');
    Route::post('/cart', [Shop\CartController::class, 'store'])->name('shop.cart.store');
    Route::patch('/cart/{cartItem}', [Shop\CartController::class, 'update'])->name('shop.cart.update');
    Route::delete('/cart/{cartItem}', [Shop\CartController::class, 'destroy'])->name('shop.cart.destroy');

    // Thanh toán & Đơn hàng
    Route::get('/checkout', [Shop\CheckoutController::class, 'index'])->name('shop.checkout.index');
    Route::post('/checkout', [Shop\CheckoutController::class, 'store'])->name('shop.checkout.store');
    Route::get('/orders', [Shop\OrderController::class, 'index'])->name('shop.orders.index');
    Route::get('/orders/{order}', [Shop\OrderController::class, 'show'])->name('shop.orders.show');
    Route::post('/orders/{order}/cancel', [Shop\OrderController::class, 'cancel'])->name('shop.orders.cancel');

    // Đánh giá
    Route::post('/products/{product}/reviews', [Shop\ReviewController::class, 'store'])->name('shop.reviews.store');

    // Wishlist
    Route::post('/wishlist/toggle', [Shop\WishlistController::class, 'toggle'])->name('shop.wishlist.toggle');
    Route::get('/wishlist', [Shop\WishlistController::class, 'index'])->name('shop.wishlist.index');

    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// ───────────────────────────────────────────────
// Admin Panel
// ───────────────────────────────────────────────
Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth', 'role:admin'])
    ->group(function () {
        Route::get('/', [Admin\DashboardController::class, 'index'])->name('dashboard');

        Route::resource('products', Admin\ProductController::class);
        Route::delete('products/{product}/images/{image}', [Admin\ProductController::class, 'destroyImage'])
            ->name('products.destroy-image');

        Route::resource('categories', Admin\CategoryController::class);
        Route::resource('orders', Admin\OrderController::class)->only(['index', 'show', 'update']);
        Route::resource('customers', Admin\CustomerController::class)->only(['index', 'show']);
        Route::post('customers/{customer}/toggle-active', [Admin\CustomerController::class, 'toggleActive'])->name('customers.toggle-active');
        Route::resource('banners', Admin\BannerController::class);
        Route::resource('vouchers', Admin\VoucherController::class);
    });

require __DIR__.'/auth.php';

