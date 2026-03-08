<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Http\Requests\Shop\ToggleWishlistRequest;
use App\Services\WishlistService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WishlistController extends Controller
{
    public function __construct(
        private readonly WishlistService $wishlistService,
    ) {}

    public function index(Request $request): View
    {
        /** @var \App\Models\User $user */
        $user      = $request->user();
        $wishlists = $this->wishlistService->getForUser($user->id);

        return view('shop.wishlist.index', compact('wishlists'));
    }

    public function toggle(ToggleWishlistRequest $request): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user   = $request->user();
        $result = $this->wishlistService->toggle($user->id, $request->validated('product_id'));

        $message = $result === 'added'
            ? 'Đã thêm vào danh sách yêu thích!'
            : 'Đã xóa khỏi danh sách yêu thích.';

        return back()->with('success', $message);
    }
}
