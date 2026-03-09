@extends('layouts.admin')

@section('title', 'Đơn hàng')

@section('content')

<div class="flex gap-3 mb-4">
    <form method="GET" action="{{ route('admin.orders.index') }}" class="flex gap-2 flex-1">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Tìm tên khách, SĐT..."
            class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:ring-2 focus:ring-indigo-300 focus:outline-none">
        <select name="status" class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:ring-2 focus:ring-indigo-300 focus:outline-none">
            <option value="">Tất cả trạng thái</option>
            @foreach(['pending' => 'Chờ xác nhận', 'confirmed' => 'Đã xác nhận', 'shipping' => 'Đang giao', 'delivered' => 'Đã giao', 'cancelled' => 'Đã hủy'] as $val => $label)
            <option value="{{ $val }}" {{ request('status') === $val ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
        <button type="submit" class="bg-gray-100 px-3 py-1.5 rounded-lg text-sm hover:bg-gray-200">Lọc</button>
    </form>
</div>

<div class="bg-white rounded-lg shadow overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b">
            <tr>
                <th class="text-left px-4 py-3 font-medium text-gray-600">#</th>
                <th class="text-left px-4 py-3 font-medium text-gray-600">Khách hàng</th>
                <th class="text-left px-4 py-3 font-medium text-gray-600">Ngày đặt</th>
                <th class="text-right px-4 py-3 font-medium text-gray-600">Tổng tiền</th>
                <th class="text-center px-4 py-3 font-medium text-gray-600">Trạng thái</th>
                <th class="px-4 py-3"></th>
            </tr>
        </thead>
        <tbody class="divide-y">
            @forelse($orders as $order)
            <tr class="hover:bg-gray-50">
                <td class="px-4 py-3 font-medium">#{{ $order->id }}</td>
                <td class="px-4 py-3">
                    <p>{{ $order->recipient_name }}</p>
                    <p class="text-xs text-gray-500">{{ $order->user->email }}</p>
                </td>
                <td class="px-4 py-3 text-gray-500">{{ $order->created_at->format('d/m/Y') }}</td>
                <td class="px-4 py-3 text-right font-medium">{{ number_format($order->final_amount) }}đ</td>
                <td class="px-4 py-3 text-center">
                    <span class="text-xs px-2 py-0.5 rounded-full font-medium
                        {{ $order->status === 'delivered' ? 'bg-green-100 text-green-700' :
                           ($order->status === 'cancelled' ? 'bg-red-100 text-red-700' : 'bg-blue-100 text-blue-700') }}">
                        {{ $order->status_label }}
                    </span>
                </td>
                <td class="px-4 py-3 text-right">
                    <a href="{{ route('admin.orders.show', $order->id) }}" class="text-sm px-3 py-1.5 rounded-lg border border-indigo-600 text-indigo-600 hover:bg-indigo-50 transition">Chi tiết</a>
                </td>
            </tr>
            @empty
            <tr><td colspan="6" class="px-4 py-8 text-center text-gray-500">Không có đơn hàng nào.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">{{ $orders->withQueryString()->links() }}</div>

@endsection
