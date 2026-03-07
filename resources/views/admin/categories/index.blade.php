@extends('layouts.admin')

@section('title', 'Danh mục')

@section('content')

<div class="flex justify-between items-center mb-4">
    <h2 class="text-lg font-semibold">Quản lý danh mục</h2>
    <a href="{{ route('admin.categories.create') }}"
        class="bg-indigo-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-indigo-700 transition">
        + Thêm danh mục
    </a>
</div>

<div class="bg-white rounded-lg shadow overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b">
            <tr>
                <th class="text-left px-4 py-3 font-medium text-gray-600">Tên danh mục</th>
                <th class="text-left px-4 py-3 font-medium text-gray-600">Danh mục cha</th>
                <th class="text-right px-4 py-3 font-medium text-gray-600">Sản phẩm</th>
                <th class="text-center px-4 py-3 font-medium text-gray-600">Trạng thái</th>
                <th class="px-4 py-3"></th>
            </tr>
        </thead>
        <tbody class="divide-y">
            @forelse($categories as $category)
            <tr class="hover:bg-gray-50">
                <td class="px-4 py-3 font-medium">{{ $category->name }}</td>
                <td class="px-4 py-3 text-gray-500">{{ $category->parent?->name ?? '—' }}</td>
                <td class="px-4 py-3 text-right">{{ $category->products_count }}</td>
                <td class="px-4 py-3 text-center">
                    <span class="px-2 py-0.5 rounded-full text-xs {{ $category->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                        {{ $category->is_active ? 'Hiển thị' : 'Ẩn' }}
                    </span>
                </td>
                <td class="px-4 py-3 text-right">
                    <a href="{{ route('admin.categories.edit', $category->id) }}" class="text-indigo-600 hover:underline text-xs mr-2">Sửa</a>
                    <form method="POST" action="{{ route('admin.categories.destroy', $category->id) }}" class="inline">
                        @csrf @method('DELETE')
                        <button type="submit" onclick="return confirm('Xóa danh mục này?')"
                            class="text-red-500 hover:underline text-xs">Xóa</button>
                    </form>
                </td>
            </tr>
            @empty
            <tr><td colspan="5" class="px-4 py-8 text-center text-gray-500">Chưa có danh mục nào.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">{{ $categories->links() }}</div>

@endsection
