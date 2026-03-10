@extends('layouts.admin')

@section('title', 'Phương thức thanh toán')

@section('content')

<div class="flex justify-between items-center mb-4">
    <h2 class="text-lg font-semibold">Quản lý phương thức thanh toán</h2>
    <a href="{{ route('admin.payment-methods.create') }}"
        class="bg-indigo-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-indigo-700 transition">
        + Thêm phương thức
    </a>
</div>

@if(session('success'))
<div class="mb-4 px-4 py-3 bg-green-50 border border-green-200 text-green-800 rounded-lg text-sm">{{ session('success') }}</div>
@endif
@if(session('error'))
<div class="mb-4 px-4 py-3 bg-red-50 border border-red-200 text-red-800 rounded-lg text-sm">{{ session('error') }}</div>
@endif

<div class="bg-white rounded-lg shadow overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b">
            <tr>
                <th class="text-left px-4 py-3 font-medium text-gray-600">Tên</th>
                <th class="text-left px-4 py-3 font-medium text-gray-600">Mã</th>
                <th class="text-center px-4 py-3 font-medium text-gray-600">Loại</th>
                <th class="text-right px-4 py-3 font-medium text-gray-600">Thứ tự</th>
                <th class="text-center px-4 py-3 font-medium text-gray-600">Trạng thái</th>
                <th class="px-4 py-3"></th>
            </tr>
        </thead>
        <tbody class="divide-y">
            @forelse($paymentMethods as $method)
            <tr class="hover:bg-gray-50">
                <td class="px-4 py-3 font-medium">
                    {{ $method->name }}
                    @if($method->description)
                    <p class="text-xs text-gray-400 font-normal mt-0.5">{{ $method->description }}</p>
                    @endif
                </td>
                <td class="px-4 py-3 font-mono text-indigo-600">{{ $method->code }}</td>
                <td class="px-4 py-3 text-center">
                    @if($method->is_external)
                    <span class="text-xs px-2 py-0.5 rounded-full bg-blue-100 text-blue-700">Cổng ngoài</span>
                    @else
                    <span class="text-xs px-2 py-0.5 rounded-full bg-gray-100 text-gray-600">Nội bộ</span>
                    @endif
                </td>
                <td class="px-4 py-3 text-right">{{ $method->sort_order }}</td>
                <td class="px-4 py-3 text-center">
                    <span class="text-xs px-2 py-0.5 rounded-full {{ $method->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                        {{ $method->is_active ? 'Kích hoạt' : 'Tắt' }}
                    </span>
                </td>
                <td class="px-4 py-3 text-right">
                    <div class="flex items-center justify-end gap-2">
                        <a href="{{ route('admin.payment-methods.edit', $method->id) }}"
                            class="text-sm px-3 py-1.5 rounded-lg border border-indigo-600 text-indigo-600 hover:bg-indigo-50 transition">Sửa</a>
                        <form method="POST" action="{{ route('admin.payment-methods.destroy', $method->id) }}" class="inline">
                            @csrf @method('DELETE')
                            <button type="submit" onclick="return confirm('Xóa phương thức thanh toán này?')"
                                class="text-sm px-3 py-1.5 rounded-lg border border-red-500 text-red-500 hover:bg-red-50 transition">Xóa</button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr><td colspan="6" class="px-4 py-8 text-center text-gray-500">Chưa có phương thức thanh toán nào.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

@endsection
