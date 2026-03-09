@extends('layouts.admin')

@section('title', 'Khách hàng')

@section('content')

<form method="GET" action="{{ route('admin.customers.index') }}" class="mb-4">
    <input type="text" name="search" value="{{ request('search') }}" placeholder="Tìm tên, email..."
        class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:ring-2 focus:ring-indigo-300 focus:outline-none w-64">
    <button type="submit" class="ml-2 bg-gray-100 px-3 py-1.5 rounded-lg text-sm hover:bg-gray-200">Tìm</button>
</form>

<div class="bg-white rounded-lg shadow overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b">
            <tr>
                <th class="text-left px-4 py-3 font-medium text-gray-600">Họ tên</th>
                <th class="text-left px-4 py-3 font-medium text-gray-600">Email</th>
                <th class="text-left px-4 py-3 font-medium text-gray-600">SĐT</th>
                <th class="text-right px-4 py-3 font-medium text-gray-600">Đơn hàng</th>
                <th class="text-center px-4 py-3 font-medium text-gray-600">Trạng thái</th>
                <th class="px-4 py-3"></th>
            </tr>
        </thead>
        <tbody class="divide-y">
            @forelse($customers as $customer)
            <tr class="hover:bg-gray-50">
                <td class="px-4 py-3 font-medium">{{ $customer->name }}</td>
                <td class="px-4 py-3 text-gray-600">{{ $customer->email }}</td>
                <td class="px-4 py-3 text-gray-600">{{ $customer->phone ?? '—' }}</td>
                <td class="px-4 py-3 text-right">{{ $customer->orders_count }}</td>
                <td class="px-4 py-3 text-center">
                    <span class="text-xs px-2 py-0.5 rounded-full {{ $customer->is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                        {{ $customer->is_active ? 'Hoạt động' : 'Bị khóa' }}
                    </span>
                </td>
                <td class="px-4 py-3 text-right">
                    <div class="flex items-center justify-end gap-2">
                        <a href="{{ route('admin.customers.show', $customer->id) }}" class="text-sm px-3 py-1.5 rounded-lg border border-indigo-600 text-indigo-600 hover:bg-indigo-50 transition">Xem</a>
                        <form method="POST" action="{{ route('admin.customers.toggle-active', $customer->id) }}" class="inline">
                            @csrf
                            <button type="submit"
                                class="text-sm px-3 py-1.5 rounded-lg border transition {{ $customer->is_active ? 'border-red-500 text-red-500 hover:bg-red-50' : 'border-green-600 text-green-600 hover:bg-green-50' }}">
                                {{ $customer->is_active ? 'Khóa' : 'Mở khóa' }}
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr><td colspan="6" class="px-4 py-8 text-center text-gray-500">Không có khách hàng nào.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">{{ $customers->withQueryString()->links() }}</div>

@endsection
