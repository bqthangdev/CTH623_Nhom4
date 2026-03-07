@extends('layouts.admin')

@section('title', 'Tổng quan')

@section('content')

{{-- Stats cards --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    @php
        $stats = [
            ['label' => 'Tổng đơn hàng',  'value' => number_format($totalOrders),          'color' => 'indigo'],
            ['label' => 'Doanh thu',       'value' => number_format($totalRevenue) . 'đ',    'color' => 'green'],
            ['label' => 'Khách hàng',      'value' => number_format($totalCustomers),        'color' => 'blue'],
            ['label' => 'Sản phẩm',        'value' => number_format($totalProducts),         'color' => 'purple'],
        ];
    @endphp
    @foreach($stats as $stat)
    <div class="bg-white rounded-lg shadow p-4">
        <p class="text-sm text-gray-500">{{ $stat['label'] }}</p>
        <p class="text-2xl font-bold text-{{ $stat['color'] }}-600 mt-1">{{ $stat['value'] }}</p>
    </div>
    @endforeach
</div>

@if($lowStockProducts->isNotEmpty())
<div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
    <p class="font-semibold text-yellow-800 mb-2">⚠ Sản phẩm sắp hết hàng ({{ $lowStockProducts->count() }})</p>
    <div class="space-y-1">
        @foreach($lowStockProducts as $p)
        <div class="flex justify-between text-sm">
            <a href="{{ route('admin.products.edit', $p->id) }}" class="text-yellow-700 hover:underline">{{ $p->name }}</a>
            <span class="text-yellow-600 font-medium">Còn {{ $p->stock }} sản phẩm</span>
        </div>
        @endforeach
    </div>
</div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

    {{-- Recent orders --}}
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="flex items-center justify-between px-5 py-4 border-b">
            <h2 class="font-semibold">Đơn hàng gần đây</h2>
            <a href="{{ route('admin.orders.index') }}" class="text-sm text-indigo-600 hover:underline">Xem tất cả</a>
        </div>
        <div class="divide-y">
            @forelse($recentOrders as $order)
            <div class="px-5 py-3 flex items-center justify-between">
                <div>
                    <a href="{{ route('admin.orders.show', $order->id) }}" class="text-sm font-medium text-indigo-600 hover:underline">#{{ $order->id }}</a>
                    <p class="text-xs text-gray-500">{{ $order->user->name }}</p>
                </div>
                <div class="text-right">
                    <p class="text-sm font-medium">{{ number_format($order->final_amount) }}đ</p>
                    <span class="text-xs px-2 py-0.5 rounded-full
                        {{ $order->status === 'delivered' ? 'bg-green-100 text-green-700' :
                           ($order->status === 'cancelled' ? 'bg-red-100 text-red-700' : 'bg-blue-100 text-blue-700') }}">
                        {{ $order->status_label }}
                    </span>
                </div>
            </div>
            @empty
            <p class="px-5 py-4 text-sm text-gray-500">Chưa có đơn hàng.</p>
            @endforelse
        </div>
    </div>

    {{-- Top products --}}
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="flex items-center justify-between px-5 py-4 border-b">
            <h2 class="font-semibold">Sản phẩm bán chạy</h2>
            <a href="{{ route('admin.products.index') }}" class="text-sm text-indigo-600 hover:underline">Xem tất cả</a>
        </div>
        <div class="divide-y">
            @forelse($topProducts as $product)
            <div class="px-5 py-3 flex items-center justify-between">
                <span class="text-sm">{{ $product->name }}</span>
                <span class="text-sm font-medium text-gray-700">{{ $product->total_sold }} đã bán</span>
            </div>
            @empty
            <p class="px-5 py-4 text-sm text-gray-500">Chưa có dữ liệu.</p>
            @endforelse
        </div>
    </div>

</div>

@endsection
