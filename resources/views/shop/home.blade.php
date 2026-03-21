@extends('layouts.app')

@section('title', 'Trang chủ')

@section('content')

{{-- Banners --}}
@if($banners->isNotEmpty())
<div x-data="bannerSlider({{ $banners->count() }})"
     x-init="init()"
     @pointerdown="dragStart($event)"
     @pointerup="dragEnd($event)"
     @pointerleave="dragging = false"
     class="relative rounded-xl overflow-hidden mb-8 h-64 md:h-96 cursor-grab active:cursor-grabbing select-none">
    @foreach($banners as $i => $banner)
    <div x-show="current === {{ $i }}"
         x-transition:enter="transition-opacity duration-500"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         class="absolute inset-0">
        <img src="{{ $banner->image_url }}" alt="{{ $banner->title }}"
            class="w-full h-full object-cover pointer-events-none" draggable="false">
        @if($banner->link)
        <a href="{{ $banner->link }}" class="absolute inset-0"></a>
        @endif
    </div>
    @endforeach

    {{-- Prev / Next arrows --}}
    @if($banners->count() > 1)
    <button @click="prev()" class="absolute left-3 top-1/2 -translate-y-1/2 bg-black/30 hover:bg-black/50 text-white rounded-full w-9 h-9 flex items-center justify-center transition">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
    </button>
    <button @click="next()" class="absolute right-3 top-1/2 -translate-y-1/2 bg-black/30 hover:bg-black/50 text-white rounded-full w-9 h-9 flex items-center justify-center transition">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    </button>
    @endif

    {{-- Dot indicators --}}
    <div class="absolute bottom-4 left-1/2 -translate-x-1/2 flex gap-2">
        @foreach($banners as $i => $banner)
        <button @click="goTo({{ $i }})"
            :class="current === {{ $i }} ? 'bg-white' : 'bg-white/50'"
            class="w-2.5 h-2.5 rounded-full transition-colors"></button>
        @endforeach
    </div>
</div>
@endif

{{-- Featured products --}}
<section>
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-bold text-gray-800">Sản phẩm nổi bật</h2>
        <a href="{{ route('shop.products.index') }}" class="text-sm text-indigo-600 hover:underline">Xem tất cả →</a>
    </div>

    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
        @foreach($featuredProducts as $product)
        <x-product-card :product="$product" />
        @endforeach
    </div>
</section>

{{-- Personalized recommendations (logged-in users with purchase history) --}}
@auth
@if($personalizedProducts->isNotEmpty())
<section class="mt-10">
    <h2 class="text-2xl font-bold text-gray-800 mb-6">Gợi ý cho bạn</h2>

    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
        @foreach($personalizedProducts as $product)
        <x-product-card :product="$product" />
        @endforeach
    </div>
</section>
@endif
@endauth

@endsection

@push('scripts')
<script>
function bannerSlider(total) {
    return {
        current: 0,
        total: total,
        timer: null,
        dragging: false,
        dragStartX: 0,

        init() {
            if (this.total > 1) this.startTimer();
        },

        startTimer() {
            this.timer = setInterval(() => this.next(), 5000);
        },

        resetTimer() {
            clearInterval(this.timer);
            this.startTimer();
        },

        next() {
            this.current = (this.current + 1) % this.total;
        },

        prev() {
            this.current = (this.current - 1 + this.total) % this.total;
        },

        goTo(index) {
            this.current = index;
            this.resetTimer();
        },

        dragStart(e) {
            this.dragging = true;
            this.dragStartX = e.clientX;
            e.currentTarget.setPointerCapture(e.pointerId);
        },

        dragEnd(e) {
            if (!this.dragging) return;
            this.dragging = false;
            const diff = e.clientX - this.dragStartX;
            if (Math.abs(diff) < 30) return; // too small — treat as click
            if (diff < 0) this.next();
            else this.prev();
            this.resetTimer();
        },
    };
}
</script>
@endpush
