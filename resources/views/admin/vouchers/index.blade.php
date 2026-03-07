@extends('layouts.admin')

@section('title', 'Voucher')

@section('content')

<div class="flex justify-between items-center mb-4">
    <h2 class="text-lg font-semibold">Quản lý Voucher</h2>
    <a href="{{ route('admin.vouchers.create') }}"
        class="bg-indigo-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-indigo-700 transition">
        + Thêm voucher
    </a>
</div>

<div class="bg-white rounded-lg shadow overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b">
            <tr>
                <th class="text-left px-4 py-3 font-medium text-gray-600">Mã</th>
                <th class="text-left px-4 py-3 font-medium text-gray-600">Loại</th>
                <th class="text-right px-4 py-3 font-medium text-gray-600">Giá trị</th>
                <th class="text-right px-4 py-3 font-medium text-gray-600">Đã dùng</th>
                <th class="text-left px-4 py-3 font-medium text-gray-600">Hết hạn</th>
                <th class="text-center px-4 py-3 font-medium text-gray-600">Trạng thái</th>
                <th class="px-4 py-3"></th>
            </tr>
        </thead>
        <tbody class="divide-y">
            @forelse($vouchers as $voucher)
            <tr class="hover:bg-gray-50">
                <td class="px-4 py-3 font-mono font-bold text-indigo-600">{{ $voucher->code }}</td>
                <td class="px-4 py-3">{{ $voucher->type === 'fixed' ? 'Số tiền cố định' : 'Phần trăm' }}</td>
                <td class="px-4 py-3 text-right">
                    {{ $voucher->type === 'fixed' ? number_format($voucher->value) . 'đ' : $voucher->value . '%' }}
                </td>
                <td class="px-4 py-3 text-right">
                    {{ $voucher->used_count }}{{ $voucher->max_uses ? ' / ' . $voucher->max_uses : '' }}
                </td>
                <td class="px-4 py-3 text-gray-500">
                    {{ $voucher->expires_at ? $voucher->expires_at->format('d/m/Y') : '—' }}
                </td>
                <td class="px-4 py-3 text-center">
                    <span class="text-xs px-2 py-0.5 rounded-full {{ $voucher->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                        {{ $voucher->is_active ? 'Kích hoạt' : 'Tắt' }}
                    </span>
                </td>
                <td class="px-4 py-3 text-right">
                    <a href="{{ route('admin.vouchers.edit', $voucher->id) }}" class="text-indigo-600 hover:underline text-xs mr-2">Sửa</a>
                    <form method="POST" action="{{ route('admin.vouchers.destroy', $voucher->id) }}" class="inline">
                        @csrf @method('DELETE')
                        <button type="submit" onclick="return confirm('Xóa voucher này?')"
                            class="text-red-500 hover:underline text-xs">Xóa</button>
                    </form>
                </td>
            </tr>
            @empty
            <tr><td colspan="7" class="px-4 py-8 text-center text-gray-500">Chưa có voucher nào.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">{{ $vouchers->links() }}</div>

@endsection
