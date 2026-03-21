@extends('layouts.app')

@section('title', 'Địa chỉ của tôi')

@section('content')

<div class="max-w-2xl mx-auto">
    <div class="flex items-center gap-4 mb-6">
        <a href="{{ route('profile.edit') }}" class="text-gray-500 hover:text-gray-700">← Hồ sơ</a>
        <h1 class="text-2xl font-bold">Địa chỉ của tôi</h1>
    </div>

    {{-- Existing addresses --}}
    @if($addresses->isNotEmpty())
    <div class="space-y-4 mb-6">
        @foreach($addresses as $addr)
        <div class="bg-white rounded-lg shadow p-4">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <span class="font-medium">{{ $addr->recipient_name }}</span>
                        <span class="text-gray-500 text-sm">{{ $addr->phone }}</span>
                        @if($addr->is_default)
                        <span class="bg-indigo-100 text-indigo-700 text-xs px-2 py-0.5 rounded-full">Mặc định</span>
                        @endif
                    </div>
                    <p class="text-sm text-gray-600">{{ $addr->address }}</p>
                </div>
            </div>
            <div class="flex items-center gap-2 mt-3 flex-wrap">
                <a href="{{ route('shop.addresses.edit', $addr) }}"
                   class="border border-indigo-500 text-indigo-600 px-3 py-1.5 rounded-lg text-xs hover:bg-indigo-50 transition">
                    Sửa
                </a>
                @if(!$addr->is_default)
                <form method="POST" action="{{ route('shop.addresses.set-default', $addr) }}" class="inline">
                    @csrf @method('PATCH')
                    <button type="submit"
                        class="border border-gray-400 text-gray-600 px-3 py-1.5 rounded-lg text-xs hover:bg-gray-50 transition">
                        Đặt mặc định
                    </button>
                </form>
                <form method="POST" action="{{ route('shop.addresses.destroy', $addr) }}" class="inline"
                    onsubmit="return confirm('Xóa địa chỉ này?')">
                    @csrf @method('DELETE')
                    <button type="submit"
                        class="border border-red-400 text-red-600 px-3 py-1.5 rounded-lg text-xs hover:bg-red-50 transition">
                        Xóa
                    </button>
                </form>
                @endif
            </div>
        </div>
        @endforeach
    </div>
    @else
    <p class="text-gray-500 text-sm mb-6">Bạn chưa có địa chỉ nào. Thêm địa chỉ để thanh toán nhanh hơn.</p>
    @endif

    {{-- Add new address --}}
    @if($addresses->count() < 5)
    <div x-data="{ open: {{ $addresses->isEmpty() ? 'true' : 'false' }} }" class="bg-white rounded-lg shadow p-4">
        <button @click="open = !open"
            class="flex items-center gap-2 text-sm font-medium text-indigo-600 hover:text-indigo-700">
            <span x-text="open ? '− Ẩn form' : '+ Thêm địa chỉ mới'"></span>
        </button>

        <div x-show="open" x-cloak class="mt-4">
            <form method="POST" action="{{ route('shop.addresses.store') }}" class="space-y-3">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Họ tên người nhận <span class="text-red-500">*</span></label>
                    <input type="text" name="recipient_name" value="{{ old('recipient_name') }}" required
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-300 focus:outline-none">
                    @error('recipient_name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Số điện thoại <span class="text-red-500">*</span></label>
                    <input type="text" name="phone" value="{{ old('phone') }}" required
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-300 focus:outline-none">
                    @error('phone')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Địa chỉ giao hàng <span class="text-red-500">*</span></label>
                    <input type="text" name="address" value="{{ old('address') }}" required
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-300 focus:outline-none">
                    @error('address')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div class="flex items-center gap-2">
                    <input type="checkbox" name="is_default" id="is_default" value="1"
                        {{ $addresses->isEmpty() ? 'checked' : '' }}
                        class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                    <label for="is_default" class="text-sm text-gray-700">Đặt làm địa chỉ mặc định</label>
                </div>
                <button type="submit"
                    class="bg-indigo-600 text-white px-5 py-2 rounded-lg text-sm hover:bg-indigo-700 transition">
                    Lưu địa chỉ
                </button>
            </form>
        </div>
    </div>
    @else
    <p class="text-sm text-gray-500 bg-yellow-50 border border-yellow-200 rounded-lg px-4 py-3">
        Bạn đã đạt tối đa 5 địa chỉ. Vui lòng xóa bớt để thêm địa chỉ mới.
    </p>
    @endif

</div>

@endsection
