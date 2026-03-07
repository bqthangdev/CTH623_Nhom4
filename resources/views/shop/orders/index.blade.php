@extends('layouts.app')

@section('title', 'Đơn hàng của tôi')

@section('content')

<h1 class="text-2xl font-bold mb-6">Đơn hàng của tôi</h1>

@if($orders->isEmpty())
<div class="text-center py-16 text-gray-500">
    <p class="mb-4">Bạn chưa có đơn hàng nào.</p>
    <a href="{{ route('shop.products.index') }}" class="text-indigo-600 hover:underline">Bắt đầu mua sắm →</a>
</div>
@else
<div class="space-y-4">
    @foreach($orders as $order)
    <div class="bg-white rounded-lg shadow p-4">
        <div class="flex items-start justify-between mb-3">
            <div>
                <span class="font-semibold">#{{ $order->id }}</span>
                <span class="text-sm text-gray-500 ml-2">{{ $order->created_at->format('d/m/Y H:i') }}</span>
            </div>
            <span class="text-sm px-3 py-1 rounded-full font-medium
                {{ $order->status === 'delivered' ? 'bg-green-100 text-green-700' :
                   ($order->status === 'cancelled' ? 'bg-red-100 text-red-700' :
                   'bg-blue-100 text-blue-700') }}">
                {{ $order->status_label }}
            </span>
        </div>

        <div class="text-sm text-gray-600 mb-3">
            Giao đến: {{ $order->shipping_address }}
        </div>

        <div class="flex items-center justify-between">
            <span class="font-bold text-indigo-600">{{ number_format($order->final_amount) }}đ</span>
            <div class="flex gap-2">
                <a href="{{ route('shop.orders.show', $order->id) }}"
                    class="text-sm text-indigo-600 hover:underline">Chi tiết</a>
                @if(in_array($order->status, ['pending', 'confirmed']))
                <form method="POST" action="{{ route('shop.orders.cancel', $order->id) }}">
                    @csrf
                    <button type="submit"
                        onclick="return confirm('Bạn có chắc muốn hủy đơn hàng này không?')"
                        class="text-sm text-red-500 hover:underline">Hủy</button>
                </form>
                @endif
            </div>
        </div>
    </div>
    @endforeach
</div>

<div class="mt-6">{{ $orders->links() }}</div>
@endif

@endsection
