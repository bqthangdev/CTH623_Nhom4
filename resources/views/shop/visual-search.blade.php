@extends('layouts.app')

@section('title', 'Tìm kiếm bằng hình ảnh')

@section('content')

<div class="max-w-xl mx-auto">
    <h1 class="text-2xl font-bold mb-2">Tìm kiếm bằng hình ảnh</h1>
    <p class="text-gray-500 text-sm mb-6">Tải lên ảnh sản phẩm để tìm các sản phẩm tương tự.</p>

    <div x-data="{ preview: null, loading: false }" class="bg-white rounded-lg shadow p-6">
        <form method="POST" action="{{ route('shop.visual-search.search') }}"
            enctype="multipart/form-data"
            @submit="loading = true"
            class="space-y-4">
            @csrf

            <div
                class="border-2 border-dashed border-gray-300 rounded-xl p-8 text-center cursor-pointer hover:border-indigo-400 transition"
                @click="$refs.fileInput.click()">
                <template x-if="!preview">
                    <div>
                        <svg class="w-12 h-12 text-gray-400 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        <p class="text-gray-500 text-sm">Nhấp để chọn ảnh hoặc kéo thả vào đây</p>
                        <p class="text-xs text-gray-400 mt-1">JPG, PNG, WEBP — tối đa 5MB</p>
                    </div>
                </template>
                <template x-if="preview">
                    <img :src="preview" alt="Preview" class="max-h-48 mx-auto rounded-lg object-contain">
                </template>
                <input x-ref="fileInput" type="file" name="image" accept="image/jpeg,image/png,image/webp"
                    class="hidden"
                    @change="preview = URL.createObjectURL($event.target.files[0])">
            </div>

            @error('image')<p class="text-red-500 text-sm">{{ $message }}</p>@enderror

            <button type="submit"
                :disabled="!preview || loading"
                class="w-full bg-indigo-600 text-white py-3 rounded-lg hover:bg-indigo-700 transition font-medium disabled:opacity-50 disabled:cursor-not-allowed">
                <span x-show="!loading">Tìm kiếm</span>
                <span x-show="loading">Đang tìm kiếm...</span>
            </button>
        </form>
    </div>
</div>

@if(isset($detectedObject) && $detectedObject)
<div class="mt-6 flex items-center gap-2 rounded-xl border border-indigo-200 bg-indigo-50 px-4 py-3 text-sm text-indigo-700">
    <svg class="h-5 w-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7
                 -1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
    </svg>
    <span>Nhận diện: <strong>{{ $detectedObject }}</strong></span>
</div>
@endif

@if(isset($results) && $results->isNotEmpty())
<div class="mt-8">
    <h2 class="text-xl font-bold mb-4">
        Sản phẩm tương tự
        @if(isset($detectedObject) && $detectedObject)
            <span class="text-indigo-600">{{ $detectedObject }}</span>
        @endif
    </h2>
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
        @foreach($results as $product)
        <x-product-card :product="$product" />
        @endforeach
    </div>
</div>
@elseif(isset($results) && $results->isEmpty())
<div class="mt-8 text-center text-gray-500">
    @if(isset($detectedObject) && $detectedObject)
        Không tìm thấy sản phẩm <strong>{{ $detectedObject }}</strong> nào phù hợp.
    @else
        Không tìm thấy sản phẩm tương tự.
    @endif
</div>
@endif

@endsection
