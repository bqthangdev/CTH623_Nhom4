@extends('layouts.app')

@section('title', 'Danh sách yêu thích')

@section('content')

<h1 class="text-2xl font-bold mb-6">Danh sách yêu thích</h1>

@if($wishlists->isEmpty())
<div class="text-center py-16 text-gray-500">
    <p class="mb-4">Bạn chưa yêu thích sản phẩm nào.</p>
    <a href="{{ route('shop.products.index') }}" class="text-indigo-600 hover:underline">Khám phá sản phẩm →</a>
</div>
@else
<div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
    @foreach($wishlists as $wishlist)
    <div class="relative">
        <x-product-card :product="$wishlist->product" />
        <form method="POST" action="{{ route('shop.wishlist.toggle') }}" class="absolute top-2 right-2">
            @csrf
            <input type="hidden" name="product_id" value="{{ $wishlist->product_id }}">
            <button type="submit" class="bg-white rounded-full p-1.5 shadow hover:bg-red-50" title="Xóa khỏi yêu thích">
                <svg class="w-4 h-4 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                </svg>
            </button>
        </form>
    </div>
    @endforeach
</div>
@endif

@endsection
