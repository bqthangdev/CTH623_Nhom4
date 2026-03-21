@extends('layouts.app')

@section('title', 'Sửa địa chỉ')

@section('content')

<div class="max-w-lg mx-auto">
    <div class="flex items-center gap-4 mb-6">
        <a href="{{ route('shop.addresses.index') }}" class="text-gray-500 hover:text-gray-700">← Địa chỉ của tôi</a>
        <h1 class="text-2xl font-bold">Sửa địa chỉ</h1>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <form method="POST" action="{{ route('shop.addresses.update', $address) }}" class="space-y-4">
            @csrf @method('PUT')

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Họ tên người nhận <span class="text-red-500">*</span>
                </label>
                <input type="text" name="recipient_name"
                    value="{{ old('recipient_name', $address->recipient_name) }}" required
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-300 focus:outline-none">
                @error('recipient_name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Số điện thoại <span class="text-red-500">*</span>
                </label>
                <input type="text" name="phone"
                    value="{{ old('phone', $address->phone) }}" required
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-300 focus:outline-none">
                @error('phone')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Địa chỉ giao hàng <span class="text-red-500">*</span>
                </label>
                <input type="text" name="address"
                    value="{{ old('address', $address->address) }}" required
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-300 focus:outline-none">
                @error('address')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div class="flex gap-3 pt-2">
                <button type="submit"
                    class="bg-indigo-600 text-white px-5 py-2 rounded-lg text-sm hover:bg-indigo-700 transition">
                    Lưu thay đổi
                </button>
                <a href="{{ route('shop.addresses.index') }}"
                    class="border border-gray-300 text-gray-600 px-5 py-2 rounded-lg text-sm hover:bg-gray-50 transition">
                    Hủy
                </a>
            </div>
        </form>
    </div>
</div>

@endsection
