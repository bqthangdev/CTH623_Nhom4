@extends('layouts.app')

@section('title', 'Thanh toán')

@section('content')

<h1 class="text-2xl font-bold mb-6">Thanh toán</h1>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    {{-- Checkout form --}}
    <div class="lg:col-span-2">
        <form method="POST" action="{{ route('shop.checkout.store') }}" class="bg-white rounded-lg shadow p-6 space-y-4">
            @csrf

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Họ tên người nhận <span class="text-red-500">*</span></label>
                    <input type="text" name="recipient_name" value="{{ old('recipient_name', auth()->user()->name) }}"
                        class="w-full border @error('recipient_name') border-red-400 @else border-gray-300 @enderror rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-300 focus:outline-none">
                    @error('recipient_name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Số điện thoại <span class="text-red-500">*</span></label>
                    <input type="text" name="phone" value="{{ old('phone', auth()->user()->phone) }}"
                        class="w-full border @error('phone') border-red-400 @else border-gray-300 @enderror rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-300 focus:outline-none">
                    @error('phone')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Địa chỉ giao hàng <span class="text-red-500">*</span></label>
                <input type="text" name="shipping_address" value="{{ old('shipping_address', auth()->user()->address) }}"
                    class="w-full border @error('shipping_address') border-red-400 @else border-gray-300 @enderror rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-300 focus:outline-none">
                @error('shipping_address')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Mã voucher</label>
                <input type="text" name="voucher_code" value="{{ old('voucher_code') }}"
                    placeholder="Nhập mã giảm giá (nếu có)"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-300 focus:outline-none">
                @error('voucher_code')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Phương thức thanh toán <span class="text-red-500">*</span></label>
                <div class="space-y-2">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="payment_method" value="cod" {{ old('payment_method', 'cod') === 'cod' ? 'checked' : '' }}>
                        <span class="text-sm">Thanh toán khi nhận hàng (COD)</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="payment_method" value="vnpay" {{ old('payment_method') === 'vnpay' ? 'checked' : '' }}>
                        <span class="text-sm">VNPay</span>
                    </label>
                </div>
                @error('payment_method')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Ghi chú</label>
                <textarea name="note" rows="2" placeholder="Ghi chú cho đơn hàng (tùy chọn)"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-300 focus:outline-none">{{ old('note') }}</textarea>
            </div>

            <button type="submit"
                class="w-full bg-indigo-600 text-white py-3 rounded-lg hover:bg-indigo-700 transition font-medium text-lg">
                Đặt hàng
            </button>
        </form>
    </div>

    {{-- Order summary --}}
    <div class="bg-white rounded-lg shadow p-5 h-fit">
        <h2 class="font-semibold text-lg mb-4">Đơn hàng của bạn</h2>
        @foreach($cartItems as $item)
        <div class="flex justify-between text-sm mb-2">
            <span class="truncate mr-2">{{ $item->product->name }} × {{ $item->quantity }}</span>
            <span class="flex-shrink-0">{{ number_format($item->subtotal) }}đ</span>
        </div>
        @endforeach
        <div class="border-t pt-3 mt-3 flex justify-between font-bold">
            <span>Tổng cộng</span>
            <span class="text-indigo-600">{{ number_format($total) }}đ</span>
        </div>
    </div>

</div>

@endsection
