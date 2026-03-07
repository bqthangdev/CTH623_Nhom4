@extends('layouts.admin')

@section('title', 'Sửa: ' . $product->name)

@section('content')

<div class="max-w-2xl">
    <a href="{{ route('admin.products.index') }}" class="text-sm text-gray-500 hover:text-gray-700 mb-4 inline-block">← Quay lại</a>

    <form method="POST" action="{{ route('admin.products.update', $product->id) }}" enctype="multipart/form-data"
        class="bg-white rounded-lg shadow p-6 space-y-5">
        @csrf @method('PUT')

        @include('admin.products._form')

        {{-- Existing images --}}
        @if($product->images->isNotEmpty())
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Ảnh hiện tại</label>
            <div class="flex flex-wrap gap-3">
                @foreach($product->images as $img)
                <div class="relative group">
                    <img src="{{ $img->url }}" alt="" class="w-20 h-20 object-cover rounded-lg bg-gray-100">
                    <form method="POST" action="{{ route('admin.products.destroy-image', $img->id) }}"
                        class="absolute inset-0 flex items-center justify-center bg-black/50 opacity-0 group-hover:opacity-100 rounded-lg transition">
                        @csrf @method('DELETE')
                        <button type="submit" onclick="return confirm('Xóa ảnh này?')"
                            class="text-white text-xs bg-red-500 px-2 py-1 rounded">Xóa</button>
                    </form>
                    @if($img->is_primary)
                    <span class="absolute bottom-1 left-1 bg-indigo-500 text-white text-xs px-1 rounded">Chính</span>
                    @endif
                </div>
                @endforeach
            </div>
        </div>
        @endif

        <div class="pt-2">
            <button type="submit" class="bg-indigo-600 text-white px-6 py-2 rounded-lg hover:bg-indigo-700 transition">
                Lưu thay đổi
            </button>
        </div>
    </form>
</div>

@endsection
