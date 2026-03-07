@extends('layouts.app')

@section('title', 'Sản phẩm')

@section('content')

<div class="flex gap-6">

    {{-- Sidebar filters --}}
    <aside class="hidden md:block w-56 flex-shrink-0">
        <form method="GET" action="{{ route('shop.products.index') }}">
            <div class="bg-white rounded-lg shadow p-4 space-y-5">

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Danh mục</label>
                    <select name="category" class="w-full border border-gray-300 rounded px-2 py-1 text-sm">
                        <option value="">Tất cả</option>
                        @foreach($categories as $cat)
                        <option value="{{ $cat->slug }}" {{ request('category') === $cat->slug ? 'selected' : '' }}>
                            {{ $cat->name }}
                        </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Sắp xếp</label>
                    <select name="sort" class="w-full border border-gray-300 rounded px-2 py-1 text-sm">
                        <option value="">Mặc định</option>
                        <option value="price_asc"   {{ request('sort') === 'price_asc'   ? 'selected' : '' }}>Giá tăng dần</option>
                        <option value="price_desc"  {{ request('sort') === 'price_desc'  ? 'selected' : '' }}>Giá giảm dần</option>
                        <option value="newest"      {{ request('sort') === 'newest'      ? 'selected' : '' }}>Mới nhất</option>
                        <option value="bestseller"  {{ request('sort') === 'bestseller'  ? 'selected' : '' }}>Bán chạy</option>
                    </select>
                </div>

                <button type="submit" class="w-full bg-indigo-600 text-white py-1.5 rounded-lg text-sm hover:bg-indigo-700 transition">
                    Lọc
                </button>

                <a href="{{ route('shop.products.index') }}" class="block text-center text-xs text-gray-400 hover:text-gray-600">
                    Xóa bộ lọc
                </a>
            </div>
        </form>
    </aside>

    {{-- Products grid --}}
    <div class="flex-1">
        @if($products->isEmpty())
        <div class="text-center py-16 text-gray-500">Không tìm thấy sản phẩm nào.</div>
        @else
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
            @foreach($products as $product)
            <x-product-card :product="$product" />
            @endforeach
        </div>

        <div class="mt-6">
            {{ $products->withQueryString()->links() }}
        </div>
        @endif
    </div>
</div>

@endsection
