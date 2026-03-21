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
            <div class="space-y-4">
                @foreach($order->items as $item)
                <div>
                    <div class="flex gap-3">
                        <img src="{{ $item->product->image_url ?? '' }}" alt="{{ $item->product_name }}"
                            class="w-14 h-14 object-cover rounded-lg bg-gray-100">
                        <div class="flex-1">
                            @if($item->product && $item->product->slug)
                            <a href="{{ route('shop.products.show', $item->product->slug) }}"
                               class="text-sm font-medium text-gray-900 hover:text-indigo-600 hover:underline">
                                {{ $item->product_name }}
                            </a>
                            @else
                            <p class="text-sm font-medium">{{ $item->product_name }}</p>
                            @endif
                            <p class="text-sm text-gray-500">{{ number_format($item->price) }}đ × {{ $item->quantity }}</p>
                        </div>
                        <span class="font-medium text-sm">{{ number_format($item->subtotal) }}đ</span>
                    </div>

                    {{-- Review form: only for delivered orders with a valid product, not yet reviewed --}}
                    @if($order->status === 'delivered' && $item->product_id && !in_array($item->product_id, $reviewedProductIds))
                    @if($canReview)
                    <form method="POST" action="{{ route('shop.reviews.store', $item->product_id) }}"
                        x-data="{ rating: 0, hover: 0 }"
                        @submit.prevent="if (rating === 0) return; $el.submit()"
                        class="mt-3 ml-17 bg-gray-50 rounded-lg p-3 border border-gray-200">
                        @csrf
                        <input type="hidden" name="order_id" value="{{ $order->id }}">
                        <input type="hidden" name="rating" :value="rating">
                        <p class="text-xs font-medium text-gray-600 mb-2">Đánh giá sản phẩm này
                            @if($reviewDeadline)
                            <span class="text-gray-400 font-normal ml-1">(còn {{ now()->diffInDays($reviewDeadline) }} ngày)</span>
                            @endif
                        </p>
                        <div class="flex items-center gap-2 mb-2">
                            <div class="flex gap-0.5">
                                @for($i = 1; $i <= 5; $i++)
                                <button type="button"
                                    class="focus:outline-none"
                                    @mouseenter="hover = {{ $i }}"
                                    @mouseleave="hover = 0"
                                    @click="rating = {{ $i }}">
                                    <svg class="w-7 h-7 transition-colors"
                                         :class="(hover || rating) >= {{ $i }} ? 'text-yellow-400' : 'text-gray-300'"
                                         fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                    </svg>
                                </button>
                                @endfor
                            </div>
                            <span x-show="rating === 0" class="text-xs text-gray-400">Chọn số sao</span>
                        </div>
                        <textarea name="comment" rows="2" placeholder="Nhận xét về sản phẩm (tùy chọn)"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs focus:ring-2 focus:ring-indigo-300 focus:outline-none mb-2"></textarea>
                        <button type="submit"
                            :disabled="rating === 0"
                            class="bg-indigo-600 text-white px-4 py-1.5 rounded-lg text-xs hover:bg-indigo-700 transition disabled:opacity-50 disabled:cursor-not-allowed">
                            Gửi đánh giá
                        </button>
                    </form>
                    @else
                    <p class="mt-2 text-xs text-orange-500 ml-1">⏰ Đã hết thời hạn đánh giá (5 ngày sau khi nhận hàng)</p>
                    @endif
                    @elseif($order->status === 'delivered' && $item->product_id && in_array($item->product_id, $reviewedProductIds))
                    <p class="mt-2 text-xs text-green-600 ml-1">✓ Đã đánh giá</p>
                    @endif
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

        @if(in_array($order->status, ['delivered', 'cancelled']))
        <div class="border-t pt-4">
            <form method="POST" action="{{ route('shop.orders.reorder', $order->id) }}">
                @csrf
                <button type="submit"
                    class="w-full bg-indigo-600 text-white py-2 rounded-lg hover:bg-indigo-700 transition text-sm font-medium">
                    Đặt lại đơn hàng này
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