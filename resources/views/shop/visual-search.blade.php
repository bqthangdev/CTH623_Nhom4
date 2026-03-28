@extends('layouts.app')

@section('title', 'Tìm kiếm bằng hình ảnh')

@section('content')

<div class="max-w-xl mx-auto">
    <h1 class="text-2xl font-bold mb-2">Tìm kiếm bằng hình ảnh</h1>
    <p class="text-gray-500 text-sm mb-6">Tải lên ảnh sản phẩm để tìm các sản phẩm tương tự.</p>

    <div x-data="visualSearchUpload()" class="bg-white rounded-lg shadow p-6">
        <form method="POST" action="{{ route('shop.visual-search.search') }}"
            enctype="multipart/form-data"
            @submit.prevent="if (hasFile) { loading = true; $el.submit(); }"
            class="space-y-4">
            @csrf

            {{-- Preview of selected image --}}
            <template x-if="previewSrc">
                <div class="mb-3">
                    <img :src="previewSrc" class="w-48 h-36 object-cover rounded-lg border border-gray-200 mx-auto">
                </div>
            </template>

            {{-- Drop zone --}}
            <div
                class="border-2 border-dashed rounded-xl p-8 text-center cursor-pointer transition select-none"
                :class="dragging ? 'border-indigo-500 bg-indigo-50'
                       : (hasFile  ? 'border-green-500 bg-green-50'
                                   : 'border-gray-300 hover:border-indigo-400')"
                @click="$refs.fileInput.click()"
                @dragover.prevent="dragging = true"
                @dragleave.prevent="dragging = false"
                @drop.prevent="handleDrop($event)">

                <template x-if="!hasFile">
                    <div class="pointer-events-none space-y-1">
                        <svg class="w-10 h-10 text-gray-400 mx-auto" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z"/>
                        </svg>
                        <p class="text-sm text-gray-600">Kéo ảnh vào đây hoặc <span class="text-indigo-600 font-medium">nhấn để chọn</span></p>
                        <p class="text-xs text-gray-400">JPG, PNG, WebP — tối đa 5MB, tự động nén nếu lớn hơn</p>
                    </div>
                </template>

                <template x-if="hasFile">
                    <div class="pointer-events-none space-y-1">
                        <svg class="w-10 h-10 text-green-500 mx-auto" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                        </svg>
                        <p class="text-sm text-green-700 font-medium" x-text="fileName"></p>
                        <p class="text-xs text-gray-400">Nhấn hoặc kéo để đổi ảnh</p>
                    </div>
                </template>

                <input x-ref="fileInput" type="file" name="image" accept="image/jpeg,image/png,image/webp"
                    class="hidden"
                    @change="handleChange($event)">
            </div>

            @error('image')<p class="text-red-500 text-sm">{{ $message }}</p>@enderror

            <button type="submit"
                :disabled="!hasFile || loading"
                class="w-full bg-indigo-600 text-white py-3 rounded-lg hover:bg-indigo-700 transition font-medium disabled:opacity-50 disabled:cursor-not-allowed">
                <span x-show="!loading">Tìm kiếm</span>
                <span x-show="loading">Đang tìm kiếm...</span>
            </button>
        </form>
    </div>
</div>

{{-- Uploaded image preview (shown after search) --}}
@if(isset($searchImageDataUri) && $searchImageDataUri)
<div class="mt-6 flex flex-col items-center gap-2">
    <p class="text-xs text-gray-500 font-medium uppercase tracking-wide">Ảnh đã tải lên</p>
    <img src="{{ $searchImageDataUri }}" alt="Ảnh tìm kiếm"
        class="w-48 h-36 object-cover rounded-lg border border-gray-200 shadow-sm">
</div>
@endif

@if(isset($results) && $results->isNotEmpty())
<div class="mt-8">
    <h2 class="text-xl font-bold mb-4">Sản phẩm tương tự</h2>
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
        @foreach($results as $product)
        <div class="relative">
            <x-product-card :product="$product" />
            @if($product->similarity_score)
            <span class="absolute top-2.5 left-2.5 z-10 pointer-events-none inline-flex items-center gap-1
                         bg-indigo-600 text-white font-semibold
                         text-xs px-2.5 py-1 rounded-lg shadow-md">
                <svg class="w-3 h-3 text-yellow-300" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                </svg>
                {{ round($product->similarity_score * 100) }}%
            </span>
            @endif
        </div>
        @endforeach
    </div>
</div>
@elseif(isset($results) && $results->isEmpty())
<div class="mt-8 text-center text-gray-500">
    Không tìm thấy sản phẩm tương tự.
</div>
@endif

@endsection

@push('scripts')
<script>
function visualSearchUpload() {
    return {
        hasFile: false,
        fileName: '',
        previewSrc: '',
        loading: false,
        dragging: false,
        async handleFiles(fileList) {
            const allowed = ['image/jpeg', 'image/png', 'image/webp'];
            const file = Array.from(fileList).find(f => allowed.includes(f.type));
            if (!file) return;
            const compressed = await window._compressImage(file);
            const dt = new DataTransfer();
            dt.items.add(compressed);
            this.$refs.fileInput.files = dt.files;
            this.hasFile = true;
            this.fileName = compressed.name;
            this.previewSrc = URL.createObjectURL(compressed);
        },
        handleDrop(event) {
            this.dragging = false;
            this.handleFiles(event.dataTransfer.files);
        },
        handleChange(event) {
            this.handleFiles(event.target.files);
        },
    };
}
</script>
@endpush
