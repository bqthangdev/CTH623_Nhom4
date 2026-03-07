<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Wishlist;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WishlistController extends Controller
{
    public function index(): View
    {
        $wishlists = Wishlist::with('product.primaryImage')
            ->where('user_id', auth()->id())
            ->latest()
            ->get();

        return view('shop.wishlist.index', compact('wishlists'));
    }

    public function toggle(Request $request): RedirectResponse
    {
        $request->validate(['product_id' => ['required', 'integer', 'exists:products,id']]);

        $product = Product::findOrFail($request->product_id);
        $exists  = Wishlist::where('user_id', auth()->id())
            ->where('product_id', $product->id)
            ->exists();

        if ($exists) {
            Wishlist::where('user_id', auth()->id())->where('product_id', $product->id)->delete();
            $message = 'Đã xóa khỏi danh sách yêu thích.';
        } else {
            Wishlist::create(['user_id' => auth()->id(), 'product_id' => $product->id]);
            $message = 'Đã thêm vào danh sách yêu thích!';
        }

        return back()->with('success', $message);
    }
}
