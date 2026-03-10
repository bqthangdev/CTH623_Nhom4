@extends('layouts.admin')

@section('title', 'Sửa Banner')

@section('content')

<div class="max-w-lg">
    <a href="{{ route('admin.banners.index') }}" class="text-sm text-gray-500 hover:text-gray-700 mb-4 inline-block">← Quay lại</a>

    <form method="POST" action="{{ route('admin.banners.update', $banner->id) }}" enctype="multipart/form-data"
        class="bg-white rounded-lg shadow p-6 space-y-4">
        @csrf @method('PUT')

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Tiêu đề <span class="text-red-500">*</span></label>
            <input type="text" name="title" value="{{ old('title', $banner->title) }}"
                class="w-full border @error('title') border-red-400 @else border-gray-300 @enderror rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-300 focus:outline-none">
            @error('title')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Ảnh banner mới</label>
            <img src="{{ $banner->image_url }}" alt="{{ $banner->title }}"
                class="w-48 h-24 object-cover rounded-lg bg-gray-100 mb-2">
            <input type="file" name="image" accept="image/jpeg,image/png,image/webp"
                class="block text-sm text-gray-500 file:mr-3 file:py-1.5 file:px-3 file:rounded file:border-0 file:text-sm file:bg-indigo-50 file:text-indigo-600">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Liên kết khi nhấp</label>
            <input type="url" name="link" value="{{ old('link', $banner->link) }}" placeholder="https://..."
                class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-300 focus:outline-none">
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Thứ tự hiển thị</label>
                <input type="number" name="sort_order" value="{{ old('sort_order', $banner->sort_order) }}" min="0"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2">
            </div>
            <div class="flex items-end pb-2">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="is_active" value="1" class="rounded"
                        {{ old('is_active', $banner->is_active) ? 'checked' : '' }}>
                    <span class="text-sm">Hiển thị</span>
                </label>
            </div>
        </div>

        <button type="submit" class="bg-indigo-600 text-white px-6 py-2 rounded-lg hover:bg-indigo-700 transition">
            Lưu thay đổi
        </button>
    </form>
</div>

@endsection
