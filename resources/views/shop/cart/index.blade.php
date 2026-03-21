@extends('layouts.app')

@section('title', 'Giỏ hàng')

@section('content')

<h1 class="text-2xl font-bold mb-6">Giỏ hàng</h1>

@if($cartItems->isEmpty())
<div class="text-center py-24 text-gray-500">
    <p class="text-lg mb-4">Giỏ hàng trống.</p>
    <a href="{{ route('shop.products.index') }}" class="text-indigo-600 hover:underline">Tiếp tục mua sắm →</a>
</div>
@else

{{-- Stock adjustment warnings --}}
@if(!empty($messages))
<div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3 mb-4">
    @foreach($messages as $msg)
    <p class="text-sm text-yellow-800">&#9888; {{ $msg }}</p>
    @endforeach
</div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    {{-- Items list --}}
    <div class="lg:col-span-2 space-y-4">
        @foreach($cartItems as $item)
        <div class="bg-white rounded-lg shadow p-4 flex gap-4 {{ $item->product->stock === 0 ? 'opacity-60' : '' }}">
            <img src="{{ $item->product->image_url }}" alt="{{ $item->product->name }}"
                class="w-20 h-20 object-cover rounded-lg flex-shrink-0">
            <div class="flex-1">
                <a href="{{ route('shop.products.show', $item->product->slug) }}"
                    class="font-medium text-gray-800 hover:text-indigo-600">
                    {{ $item->product->name }}
                </a>
                <p class="text-indigo-600 font-semibold mt-1">
                    {{ number_format($item->product->effective_price) }}đ
                </p>
                @if($item->product->stock === 0)
                <span class="inline-block bg-red-100 text-red-600 text-xs px-2 py-0.5 rounded-full mt-1">Hết hàng</span>
                @elseif($item->product->stock < $item->quantity)
                <span class="inline-block bg-yellow-100 text-yellow-700 text-xs px-2 py-0.5 rounded-full mt-1">Chỉ còn {{ $item->product->stock }}</span>
                @endif
                <div class="flex flex-wrap items-center gap-3 mt-2">
                    <form method="POST" action="{{ route('shop.cart.update', $item->id) }}" class="flex items-center gap-1">
                        @csrf @method('PATCH')
                        <input type="number" name="quantity" value="{{ $item->quantity }}" min="1" max="{{ $item->product->stock ?: 99 }}"
                            class="w-16 border border-gray-300 rounded px-2 py-1 text-sm text-center {{ $item->product->stock === 0 ? 'bg-gray-100' : '' }}"
                            {{ $item->product->stock === 0 ? 'disabled' : '' }}>
                        <button type="submit" class="text-xs border border-indigo-400 text-indigo-600 px-2 py-1 rounded hover:bg-indigo-50 transition">Cập nhật</button>
                    </form>

                    <form method="POST" action="{{ route('shop.cart.destroy', $item->id) }}">
                        @csrf @method('DELETE')
                        <button type="submit" class="text-xs border border-red-300 text-red-500 px-2 py-1 rounded hover:bg-red-50 transition">Xóa</button>
                    </form>
                </div>
            </div>
            <div class="text-right font-semibold text-gray-800">
                {{ number_format($item->subtotal) }}đ
            </div>
        </div>
        @endforeach
    </div>

    {{-- Order summary --}}
    <div class="bg-white rounded-lg shadow p-5 h-fit">
        <h2 class="font-semibold text-lg mb-4">Tóm tắt đơn hàng</h2>
        <div class="flex justify-between text-sm mb-2">
            <span>Tạm tính</span>
            <span>{{ number_format($total) }}đ</span>
        </div>
        <div class="flex justify-between text-sm mb-4">
            <span>Phí vận chuyển</span>
            <span class="text-green-600">Miễn phí</span>
        </div>
        <div class="border-t pt-3 flex justify-between font-bold text-base">
            <span>Tổng cộng</span>
            <span class="text-indigo-600">{{ number_format($total) }}đ</span>
        </div>
        <a href="{{ route('shop.checkout.index') }}"
            class="block mt-4 bg-indigo-600 text-white text-center py-3 rounded-lg hover:bg-indigo-700 transition font-medium">
            Tiến hành thanh toán
        </a>
        <a href="{{ route('shop.products.index') }}" class="block mt-2 text-center text-sm text-gray-500 hover:underline">
            ← Tiếp tục mua sắm
        </a>
    </div>
</div>
@endif

@endsection
