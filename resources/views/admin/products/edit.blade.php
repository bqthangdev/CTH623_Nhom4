@extends('layouts.admin')

@section('title', 'Sửa: ' . $product->name)

@section('content')

<div class="max-w-2xl space-y-4">
    <a href="{{ route('admin.products.index') }}" class="text-sm text-gray-500 hover:text-gray-700 inline-block">← Quay lại</a>

    <form method="POST" action="{{ route('admin.products.update', $product->id) }}" enctype="multipart/form-data"
        class="bg-white rounded-lg shadow p-6 space-y-5">
        @csrf @method('PUT')

        @include('admin.products._form')

        <div class="pt-2">
            <button type="submit" class="bg-indigo-600 text-white px-6 py-2 rounded-lg hover:bg-indigo-700 transition">
                Lưu thay đổi
            </button>
        </div>
    </form>

    {{-- Ảnh hiện tại — đặt ngoài form chính để tránh nested form --}}
    @if($product->images->isNotEmpty())
    <div class="bg-white rounded-lg shadow p-6">
        <p class="text-sm font-medium text-gray-700 mb-3">Ảnh hiện tại</p>
        <div class="flex flex-wrap gap-4">
            @foreach($product->images as $img)
            <div class="flex flex-col items-center gap-1.5">
                <div class="relative">
                    <img src="{{ $img->url }}" alt="" class="w-20 h-20 object-cover rounded-lg bg-gray-100">
                    @if($img->is_primary)
                    <span class="absolute top-1 left-1 bg-indigo-500 text-white text-xs px-1 rounded leading-tight">Chính</span>
                    @endif
                </div>
                <form method="POST" action="{{ route('admin.products.destroy-image', [$product->id, $img->id]) }}">
                    @csrf @method('DELETE')
                    <button type="submit" onclick="return confirm('Xóa ảnh này?')"
                        class="text-xs text-red-600 hover:underline">Xóa</button>
                </form>
            </div>
            @endforeach
        </div>
    </div>
    @endif
</div>

@endsection
