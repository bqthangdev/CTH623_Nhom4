@extends('layouts.app')

@section('title', 'Trang chủ')

@section('content')

{{-- Banners --}}
@if($banners->isNotEmpty())
<div x-data="{ current: 0 }" class="relative rounded-xl overflow-hidden mb-8 h-64 md:h-96">
    @foreach($banners as $i => $banner)
    <div x-show="current === {{ $i }}" class="absolute inset-0 transition-opacity duration-500">
        <img src="{{ asset('storage/' . $banner->image) }}" alt="{{ $banner->title }}"
            class="w-full h-full object-cover">
        @if($banner->link)
        <a href="{{ $banner->link }}" class="absolute inset-0"></a>
        @endif
    </div>
    @endforeach
    <div class="absolute bottom-4 left-1/2 -translate-x-1/2 flex gap-2">
        @foreach($banners as $i => $banner)
        <button @click="current = {{ $i }}"
            :class="current === {{ $i }} ? 'bg-white' : 'bg-white/50'"
            class="w-2.5 h-2.5 rounded-full"></button>
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

@endsection
