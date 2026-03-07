@extends('layouts.admin')

@section('title', 'Khách hàng: ' . $customer->name)

@section('content')

<div class="max-w-2xl">
    <a href="{{ route('admin.customers.index') }}" class="text-sm text-gray-500 hover:text-gray-700 mb-4 inline-block">← Quay lại</a>

    <div class="bg-white rounded-lg shadow p-6 space-y-4 mb-6">
        <div class="grid grid-cols-2 gap-3 text-sm">
            <div><span class="text-gray-500">Họ tên</span><p class="font-medium">{{ $customer->name }}</p></div>
            <div><span class="text-gray-500">Email</span><p>{{ $customer->email }}</p></div>
            <div><span class="text-gray-500">SĐT</span><p>{{ $customer->phone ?? '—' }}</p></div>
            <div><span class="text-gray-500">Địa chỉ</span><p>{{ $customer->address ?? '—' }}</p></div>
            <div><span class="text-gray-500">Ngày đăng ký</span><p>{{ $customer->created_at->format('d/m/Y') }}</p></div>
            <div>
                <span class="text-gray-500">Trạng thái</span>
                <p>
                    <span class="text-xs px-2 py-0.5 rounded-full {{ $customer->is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                        {{ $customer->is_active ? 'Hoạt động' : 'Bị khóa' }}
                    </span>
                </p>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.customers.toggle-active', $customer->id) }}" class="pt-2 border-t">
            @csrf
            <button type="submit"
                class="text-sm px-4 py-1.5 rounded-lg border {{ $customer->is_active ? 'border-red-400 text-red-600 hover:bg-red-50' : 'border-green-400 text-green-600 hover:bg-green-50' }} transition">
                {{ $customer->is_active ? 'Khóa tài khoản' : 'Mở khóa tài khoản' }}
            </button>
        </form>
    </div>

    <h3 class="font-semibold mb-3">Đơn hàng gần đây</h3>
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b">
                <tr>
                    <th class="text-left px-4 py-2 font-medium text-gray-600">#</th>
                    <th class="text-left px-4 py-2 font-medium text-gray-600">Ngày</th>
                    <th class="text-right px-4 py-2 font-medium text-gray-600">Tổng tiền</th>
                    <th class="text-center px-4 py-2 font-medium text-gray-600">Trạng thái</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse($customer->orders as $order)
                <tr>
                    <td class="px-4 py-2">
                        <a href="{{ route('admin.orders.show', $order->id) }}" class="text-indigo-600 hover:underline">#{{ $order->id }}</a>
                    </td>
                    <td class="px-4 py-2 text-gray-500">{{ $order->created_at->format('d/m/Y') }}</td>
                    <td class="px-4 py-2 text-right">{{ number_format($order->final_amount) }}đ</td>
                    <td class="px-4 py-2 text-center">
                        <span class="text-xs px-2 py-0.5 rounded-full
                            {{ $order->status === 'delivered' ? 'bg-green-100 text-green-700' :
                               ($order->status === 'cancelled' ? 'bg-red-100 text-red-700' : 'bg-blue-100 text-blue-700') }}">
                            {{ $order->status_label }}
                        </span>
                    </td>
                </tr>
                @empty
                <tr><td colspan="4" class="px-4 py-4 text-center text-gray-500">Chưa có đơn hàng.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection
