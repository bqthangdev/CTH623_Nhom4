@extends('layouts.admin')

@section('title', 'Thêm Banner')

@section('content')

<div class="max-w-lg">
    <a href="{{ route('admin.banners.index') }}" class="text-sm text-gray-500 hover:text-gray-700 mb-4 inline-block">← Quay lại</a>

    <form method="POST" action="{{ route('admin.banners.store') }}" enctype="multipart/form-data"
        class="bg-white rounded-lg shadow p-6 space-y-4">
        @csrf

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Tiêu đề <span class="text-red-500">*</span></label>
            <input type="text" name="title" value="{{ old('title') }}"
                class="w-full border @error('title') border-red-400 @else border-gray-300 @enderror rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-300 focus:outline-none">
            @error('title')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Ảnh banner <span class="text-red-500">*</span></label>
            <x-upload-zone name="image" />
            @error('image')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Liên kết khi nhấp</label>
            <input type="url" name="link" value="{{ old('link') }}" placeholder="https://..."
                class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-300 focus:outline-none">
            @error('link')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Thứ tự hiển thị</label>
                <input type="number" name="sort_order" value="{{ old('sort_order', 0) }}" min="0"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2">
            </div>
            <div class="flex items-end pb-2">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="is_active" value="1" class="rounded" checked>
                    <span class="text-sm">Hiển thị</span>
                </label>
            </div>
        </div>

        <button type="submit" class="bg-indigo-600 text-white px-6 py-2 rounded-lg hover:bg-indigo-700 transition">
            Tạo banner
        </button>
    </form>
</div>

@endsection
