@extends('layouts.admin')

@section('title', 'Khách hàng: ' . $customer->name)

@section('content')

<div class="max-w-2xl">
    <a href="{{ route('admin.customers.index') }}" class="text-sm text-gray-500 hover:text-gray-700 mb-4 inline-block">← Quay lại</a>

    {{-- Thông tin khách hàng --}}
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

        <div class="flex flex-wrap gap-3 pt-2 border-t">
            {{-- Kích hoạt / Khóa --}}
            <form method="POST" action="{{ route('admin.customers.toggle-active', $customer->id) }}">
                @csrf
                <button type="submit"
                    class="text-sm px-4 py-1.5 rounded-lg border {{ $customer->is_active ? 'border-red-400 text-red-600 hover:bg-red-50' : 'border-green-400 text-green-600 hover:bg-green-50' }} transition">
                    {{ $customer->is_active ? 'Khóa tài khoản' : 'Mở khóa tài khoản' }}
                </button>
            </form>

            {{-- Đặt lại mật khẩu --}}
            <form method="POST" action="{{ route('admin.customers.reset-password', $customer->id) }}"
                  x-data="{}" @submit.prevent="if(confirm('Đặt lại mật khẩu cho {{ $customer->name }}?')) $el.submit()">
                @csrf
                <button type="submit"
                    class="text-sm px-4 py-1.5 rounded-lg border border-amber-400 text-amber-700 hover:bg-amber-50 transition">
                    Đặt lại mật khẩu
                </button>
            </form>
        </div>
    </div>

    {{-- Hiển thị mật khẩu tạm thời sau khi reset --}}
    @if(session('temp_password'))
    <div class="mb-6 bg-amber-50 border border-amber-300 rounded-xl px-4 py-4 text-sm">
        <p class="font-semibold text-amber-800 mb-1">⚠️ Mật khẩu tạm thời — chỉ hiển thị một lần</p>
        <p class="text-amber-700 mb-2">Sao chép và gửi cho người dùng. Họ sẽ được yêu cầu đổi mật khẩu khi đăng nhập lần sau.</p>
        <div class="flex items-center gap-2">
            <code class="bg-white border border-amber-300 rounded px-3 py-1.5 font-mono text-base tracking-wider"
                  id="temp-pw">{{ session('temp_password') }}</code>
            <button type="button" onclick="navigator.clipboard.writeText(document.getElementById('temp-pw').textContent)"
                class="text-xs text-amber-700 hover:text-amber-900 underline">
                Sao chép
            </button>
        </div>
    </div>
    @endif

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
