@extends('layouts.admin')

@section('title', 'Sản phẩm')

@section('content')

<div class="flex items-center justify-between mb-4">
    <form method="GET" action="{{ route('admin.products.index') }}" class="flex gap-2">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Tìm tên sản phẩm..."
            class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:ring-2 focus:ring-indigo-300 focus:outline-none">
        <button type="submit" class="bg-gray-100 px-3 py-1.5 rounded-lg text-sm hover:bg-gray-200">Tìm</button>
    </form>
    <a href="{{ route('admin.products.create') }}"
        class="bg-indigo-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-indigo-700 transition">
        + Thêm sản phẩm
    </a>
</div>

<div class="bg-white rounded-lg shadow overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b">
            <tr>
                <th class="text-left px-4 py-3 text-gray-600 font-medium">Sản phẩm</th>
                <th class="text-left px-4 py-3 text-gray-600 font-medium">Danh mục</th>
                <th class="text-right px-4 py-3 text-gray-600 font-medium">Giá</th>
                <th class="text-right px-4 py-3 text-gray-600 font-medium">Tồn kho</th>
                <th class="text-center px-4 py-3 text-gray-600 font-medium">Trạng thái</th>
                <th class="px-4 py-3"></th>
            </tr>
        </thead>
        <tbody class="divide-y">
            @forelse($products as $product)
            <tr class="hover:bg-gray-50">
                <td class="px-4 py-3">
                    <div class="flex items-center gap-3">
                        <img src="{{ $product->image_url }}" alt="{{ $product->name }}"
                            class="w-10 h-10 object-cover rounded bg-gray-100">
                        <span class="font-medium">{{ $product->name }}</span>
                    </div>
                </td>
                <td class="px-4 py-3 text-gray-500">{{ $product->category->name }}</td>
                <td class="px-4 py-3 text-right">{{ number_format($product->effective_price) }}đ</td>
                <td class="px-4 py-3 text-right {{ $product->stock <= 5 ? 'text-red-600 font-medium' : '' }}">
                    {{ $product->stock }}
                </td>
                <td class="px-4 py-3 text-center">
                    <span class="px-2 py-0.5 rounded-full text-xs {{ $product->status ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                        {{ $product->status ? 'Hiển thị' : 'Ẩn' }}
                    </span>
                </td>
                <td class="px-4 py-3 text-right">
                    <div class="flex items-center justify-end gap-2">
                        <a href="{{ route('admin.products.edit', $product->id) }}" class="text-sm px-3 py-1.5 rounded-lg border border-indigo-600 text-indigo-600 hover:bg-indigo-50 transition">Sửa</a>
                        <form method="POST" action="{{ route('admin.products.destroy', $product->id) }}" class="inline">
                            @csrf @method('DELETE')
                            <button type="submit" onclick="return confirm('Xóa sản phẩm này?')"
                                class="text-sm px-3 py-1.5 rounded-lg border border-red-500 text-red-500 hover:bg-red-50 transition">Xóa</button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr><td colspan="6" class="px-4 py-8 text-center text-gray-500">Không có sản phẩm nào.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">{{ $products->withQueryString()->links() }}</div>

@endsection
