@extends('layouts.app')

@section('title', 'Đơn hàng #' . $order->id)

@section('content')

<div class="max-w-2xl mx-auto">
    <div class="flex items-center gap-4 mb-6">
        <a href="{{ route('shop.orders.index') }}" class="text-gray-500 hover:text-gray-700">← Quay lại</a>
        <h1 class="text-2xl font-bold">Đơn hàng #{{ $order->id }}</h1>
    </div>

    <div class="bg-white rounded-lg shadow p-6 space-y-6">

        {{-- Status --}}
        <div class="flex items-center justify-between">
            <span class="text-gray-600">Trạng thái</span>
            <span class="px-3 py-1 rounded-full text-sm font-medium
                {{ $order->status === 'delivered' ? 'bg-green-100 text-green-700' :
                   ($order->status === 'cancelled' ? 'bg-red-100 text-red-700' :
                   'bg-blue-100 text-blue-700') }}">
                {{ $order->status_label }}
            </span>
        </div>

        {{-- Shipping info --}}
        <div class="border-t pt-4">
            <h2 class="font-semibold mb-2">Thông tin giao hàng</h2>
            <p class="text-sm text-gray-700">{{ $order->recipient_name }}</p>
            <p class="text-sm text-gray-700">{{ $order->phone }}</p>
            <p class="text-sm text-gray-700">{{ $order->shipping_address }}</p>
            @if($order->shippingCarrier)
            <div class="mt-3 p-3 bg-blue-50 rounded-lg text-sm space-y-1">
                <p class="text-gray-600">Đơn vị vận chuyển: <span class="font-medium text-gray-800">{{ $order->shippingCarrier->name }}</span></p>
                <p class="text-gray-600">Mã vận đơn: <span class="font-mono font-medium text-gray-800">{{ $order->tracking_code }}</span></p>
            </div>
            @endif
        </div>

        {{-- Items --}}
        <div class="border-t pt-4">
            <h2 class="font-semibold mb-3">Sản phẩm đặt hàng</h2>
            <div class="space-y-3">
                @foreach($order->items as $item)
                <div class="flex gap-3">
                    <img src="{{ $item->product->image_url ?? '' }}" alt="{{ $item->product_name }}"
                        class="w-14 h-14 object-cover rounded-lg bg-gray-100">
                    <div class="flex-1">
                        <p class="text-sm font-medium">{{ $item->product_name }}</p>
                        <p class="text-sm text-gray-500">{{ number_format($item->price) }}đ × {{ $item->quantity }}</p>
                    </div>
                    <span class="font-medium text-sm">{{ number_format($item->subtotal) }}đ</span>
                </div>
                @endforeach
            </div>
        </div>

        {{-- Totals --}}
        <div class="border-t pt-4 space-y-2 text-sm">
            <div class="flex justify-between">
                <span>Tạm tính</span>
                <span>{{ number_format($order->total_amount) }}đ</span>
            </div>
            @if($order->discount_amount > 0)
            <div class="flex justify-between text-green-600">
                <span>
                    Giảm giá
                    @if($order->voucher)
                    <span class="ml-1 font-mono bg-green-100 text-green-700 text-xs px-1.5 py-0.5 rounded">
                        {{ $order->voucher->code }}
                    </span>
                    <span class="text-xs text-green-500">
                        ({{ $order->voucher->type === 'percent'
                            ? '-' . number_format($order->voucher->value) . '%'
                            : '-' . number_format($order->voucher->value) . 'đ cố định' }})
                    </span>
                    @endif
                </span>
                <span>-{{ number_format($order->discount_amount) }}đ</span>
            </div>
            @endif
            <div class="flex justify-between text-gray-600">
                <span>Phí vận chuyển</span>
                <span>{{ number_format($order->shipping_fee) }}đ</span>
            </div>
            <div class="flex justify-between font-bold text-base border-t pt-2">
                <span>Tổng cộng</span>
                <span class="text-indigo-600">{{ number_format($order->final_amount) }}đ</span>
            </div>
        </div>

        {{-- Payment --}}
        <div class="border-t pt-4 text-sm text-gray-600">
            Thanh toán: {{ $order->payment_method === 'cod' ? 'Tiền mặt khi nhận hàng (COD)' : 'VNPay' }}
        </div>

        @if($order->status === 'shipping')
        <div class="border-t pt-4">
            <form method="POST" action="{{ route('shop.orders.confirm-delivery', $order->id) }}">
                @csrf
                <button type="submit"
                    onclick="return confirm('Bạn xác nhận đã nhận được hàng?')"
                    class="w-full bg-green-600 text-white py-2 rounded-lg hover:bg-green-700 transition text-sm font-medium">
                    Đã nhận hàng
                </button>
            </form>
        </div>
        @endif

        @if(in_array($order->status, ['pending', 'confirmed']))
        <div class="border-t pt-4">
            <form method="POST" action="{{ route('shop.orders.cancel', $order->id) }}">
                @csrf
                <button type="submit"
                    onclick="return confirm('Bạn có chắc muốn hủy đơn hàng này không?')"
                    class="w-full border border-red-400 text-red-600 py-2 rounded-lg hover:bg-red-50 transition text-sm font-medium">
                    Hủy đơn hàng
                </button>
            </form>
        </div>
        @endif
    </div>
</div>

@endsection

@if(session('order_just_placed') == $order->id)
@push('scripts')
<script>
(function() {
    if (typeof gtag === 'undefined') return;
    gtag('event', 'purchase', {
        transaction_id: '{{ $order->id }}',
        currency: 'VND',
        value: {{ $order->final_amount }},
        shipping: {{ $order->shipping_fee }},
        tax: 0,
        items: [
            @foreach($order->items as $item)
            {
                item_id: '{{ $item->product_id ?? '' }}',
                item_name: {{ Illuminate\Support\Js::from($item->product_name) }},
                price: {{ $item->price }},
                quantity: {{ $item->quantity }}
            },
            @endforeach
        ]
    });
})();
</script>
@endpush
@endif