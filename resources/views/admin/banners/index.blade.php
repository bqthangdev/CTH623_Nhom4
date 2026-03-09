@extends('layouts.admin')

@section('title', 'Banner')

@section('content')

<div class="flex justify-between items-center mb-4">
    <h2 class="text-lg font-semibold">Quản lý Banner</h2>
    <a href="{{ route('admin.banners.create') }}"
        class="bg-indigo-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-indigo-700 transition">
        + Thêm banner
    </a>
</div>

<div class="bg-white rounded-lg shadow overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b">
            <tr>
                <th class="text-left px-4 py-3 font-medium text-gray-600">Ảnh</th>
                <th class="text-left px-4 py-3 font-medium text-gray-600">Tiêu đề</th>
                <th class="text-right px-4 py-3 font-medium text-gray-600">Thứ tự</th>
                <th class="text-center px-4 py-3 font-medium text-gray-600">Trạng thái</th>
                <th class="px-4 py-3"></th>
            </tr>
        </thead>
        <tbody class="divide-y">
            @forelse($banners as $banner)
            <tr class="hover:bg-gray-50">
                <td class="px-4 py-3">
                    <img src="{{ asset('storage/' . $banner->image) }}" alt="{{ $banner->title }}"
                        class="w-20 h-10 object-cover rounded bg-gray-100">
                </td>
                <td class="px-4 py-3 font-medium">{{ $banner->title }}</td>
                <td class="px-4 py-3 text-right">{{ $banner->sort_order }}</td>
                <td class="px-4 py-3 text-center">
                    <span class="text-xs px-2 py-0.5 rounded-full {{ $banner->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                        {{ $banner->is_active ? 'Hiển thị' : 'Ẩn' }}
                    </span>
                </td>
                <td class="px-4 py-3 text-right">
                    <div class="flex items-center justify-end gap-2">
                        <a href="{{ route('admin.banners.edit', $banner->id) }}" class="text-sm px-3 py-1.5 rounded-lg border border-indigo-600 text-indigo-600 hover:bg-indigo-50 transition">Sửa</a>
                        <form method="POST" action="{{ route('admin.banners.destroy', $banner->id) }}" class="inline">
                            @csrf @method('DELETE')
                            <button type="submit" onclick="return confirm('Xóa banner này?')"
                                class="text-sm px-3 py-1.5 rounded-lg border border-red-500 text-red-500 hover:bg-red-50 transition">Xóa</button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr><td colspan="5" class="px-4 py-8 text-center text-gray-500">Chưa có banner nào.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">{{ $banners->links() }}</div>

@endsection
