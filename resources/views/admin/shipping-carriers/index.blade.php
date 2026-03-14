@extends('layouts.admin')

@section('title', 'Đơn vị vận chuyển')

@section('content')

<div class="flex justify-between items-center mb-4">
    <h2 class="text-lg font-semibold">Quản lý đơn vị vận chuyển</h2>
    <a href="{{ route('admin.shipping-carriers.create') }}"
        class="bg-indigo-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-indigo-700 transition">
        + Thêm đơn vị vận chuyển
    </a>
</div>

@if(session('success'))
<div class="mb-4 px-4 py-3 bg-green-50 border border-green-200 text-green-700 rounded-lg text-sm">
    {{ session('success') }}
</div>
@endif

<div class="bg-white rounded-lg shadow overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b">
            <tr>
                <th class="text-left px-4 py-3 font-medium text-gray-600">#</th>
                <th class="text-left px-4 py-3 font-medium text-gray-600">Tên đơn vị vận chuyển</th>
                <th class="text-center px-4 py-3 font-medium text-gray-600">Trạng thái</th>
                <th class="px-4 py-3"></th>
            </tr>
        </thead>
        <tbody class="divide-y">
            @forelse($carriers as $carrier)
            <tr class="hover:bg-gray-50">
                <td class="px-4 py-3 text-gray-400">{{ $carrier->id }}</td>
                <td class="px-4 py-3 font-medium">{{ $carrier->name }}</td>
                <td class="px-4 py-3 text-center">
                    <span class="px-2 py-0.5 rounded-full text-xs {{ $carrier->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                        {{ $carrier->is_active ? 'Hoạt động' : 'Tạm ngưng' }}
                    </span>
                </td>
                <td class="px-4 py-3 text-right">
                    <div class="flex items-center justify-end gap-2">
                        <a href="{{ route('admin.shipping-carriers.edit', $carrier->id) }}"
                            class="text-sm px-3 py-1.5 rounded-lg border border-indigo-600 text-indigo-600 hover:bg-indigo-50 transition">Sửa</a>
                        <form method="POST" action="{{ route('admin.shipping-carriers.destroy', $carrier->id) }}" class="inline">
                            @csrf @method('DELETE')
                            <button type="submit" onclick="return confirm('Xóa đơn vị vận chuyển này?')"
                                class="text-sm px-3 py-1.5 rounded-lg border border-red-500 text-red-500 hover:bg-red-50 transition">Xóa</button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr><td colspan="4" class="px-4 py-8 text-center text-gray-500">Chưa có đơn vị vận chuyển nào.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">{{ $carriers->links() }}</div>

@endsection
