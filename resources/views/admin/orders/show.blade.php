@extends('layouts.admin')

@section('title', 'Đơn hàng #' . $order->id)

@section('content')

<div>
    <a href="{{ route('admin.orders.index') }}" class="text-sm text-gray-500 hover:text-gray-700 mb-4 inline-block">← Quay lại</a>

    <div class="bg-white rounded-lg shadow p-6 space-y-5">

        {{-- Header --}}
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-lg font-bold">Đơn hàng #{{ $order->id }}</h2>
                <p class="text-sm text-gray-500">{{ $order->created_at->format('d/m/Y H:i') }}</p>
            </div>
            @php
                $statusLabels = ['pending' => 'Chờ xác nhận', 'confirmed' => 'Đã xác nhận', 'shipping' => 'Đang giao', 'delivered' => 'Đã giao', 'cancelled' => 'Đã hủy'];
                $transitions  = $order->allowedAdminTransitions();
            @endphp
            <div class="flex items-center gap-3">
                <span class="px-3 py-1.5 rounded-full text-sm font-medium
                    {{ $order->status === 'delivered' ? 'bg-green-100 text-green-700' :
                       ($order->status === 'cancelled' ? 'bg-red-100 text-red-700' : 'bg-blue-100 text-blue-700') }}">
                    {{ $order->status_label }}
                </span>
                @if($order->status === 'confirmed')
                    {{-- Dispatch to shipping: requires carrier + tracking code --}}
                    <form method="POST" action="{{ route('admin.orders.update-shipping', $order->id) }}" class="flex items-center gap-2 flex-wrap">
                        @csrf @method('PATCH')
                        <select name="shipping_carrier_id" required
                            class="border @error('shipping_carrier_id') border-red-400 @else border-gray-300 @enderror rounded-lg pl-3 pr-8 py-1.5 text-sm min-w-40 appearance-auto">
                            <option value="">-- Đơn vị vận chuyển --</option>
                            @foreach($carriers as $carrier)
                            <option value="{{ $carrier->id }}" {{ old('shipping_carrier_id') == $carrier->id ? 'selected' : '' }}>
                                {{ $carrier->name }}
                            </option>
                            @endforeach
                        </select>
                        <input type="text" name="tracking_code" value="{{ old('tracking_code') }}"
                            placeholder="Mã vận đơn" required maxlength="100"
                            class="border @error('tracking_code') border-red-400 @else border-gray-300 @enderror rounded-lg px-3 py-1.5 text-sm w-40">
                        <button type="submit" class="bg-indigo-600 text-white px-3 py-1.5 rounded-lg text-sm hover:bg-indigo-700 transition">
                            Giao hàng
                        </button>
                    </form>
                    <form method="POST" action="{{ route('admin.orders.update', $order->id) }}" class="inline">
                        @csrf @method('PUT')
                        <input type="hidden" name="status" value="cancelled">
                        <button type="submit" onclick="return confirm('Xác nhận hủy đơn hàng này?')"
                            class="px-3 py-1.5 rounded-lg text-sm border border-red-500 text-red-500 hover:bg-red-50 transition">
                            Hủy đơn
                        </button>
                    </form>
                @elseif(!empty($transitions))
                    {{-- Other statuses with transitions (e.g. pending → confirmed/cancelled) --}}
                    <form method="POST" action="{{ route('admin.orders.update', $order->id) }}" class="flex items-center gap-2">
                        @csrf @method('PUT')
                        <select name="status" class="border border-gray-300 rounded-lg pl-3 pr-8 py-1.5 text-sm min-w-36 appearance-auto">
                            @foreach($transitions as $val)
                            <option value="{{ $val }}">{{ $statusLabels[$val] }}</option>
                            @endforeach
                        </select>
                        <button type="submit" class="bg-indigo-600 text-white px-3 py-1.5 rounded-lg text-sm hover:bg-indigo-700 transition">
                            Cập nhật
                        </button>
                    </form>
                @elseif($order->status === 'shipping')
                    <span class="text-sm text-gray-500 italic">Chờ khách hàng xác nhận đã nhận hàng</span>
                @else
                    <span class="text-sm text-gray-500 italic">Đã kết thúc</span>
                @endif
            </div>
        </div>

        {{-- Customer info --}}
        <div class="border-t pt-4 grid grid-cols-2 gap-3 text-sm">
            <div><span class="text-gray-500">Khách hàng</span><p class="font-medium">{{ $order->user->name }}</p></div>
            <div><span class="text-gray-500">Email</span><p>{{ $order->user->email }}</p></div>
            <div><span class="text-gray-500">Người nhận</span><p class="font-medium">{{ $order->recipient_name }}</p></div>
            <div><span class="text-gray-500">SĐT</span><p>{{ $order->phone }}</p></div>
            <div class="col-span-2"><span class="text-gray-500">Địa chỉ</span><p>{{ $order->shipping_address }}</p></div>
            @if($order->note)
            <div class="col-span-2"><span class="text-gray-500">Ghi chú</span><p>{{ $order->note }}</p></div>
            @endif
            @if($order->shippingCarrier)
            <div><span class="text-gray-500">Đơn vị vận chuyển</span><p class="font-medium">{{ $order->shippingCarrier->name }}</p></div>
            <div><span class="text-gray-500">Mã vận đơn</span><p class="font-mono font-medium">{{ $order->tracking_code }}</p></div>
            @endif
        </div>

        {{-- Items --}}
        <div class="border-t pt-4">
            <h3 class="font-medium mb-3">Sản phẩm</h3>
            <div class="space-y-3">
                @foreach($order->items as $item)
                <div class="flex gap-3 text-sm">
                    <div class="flex-1">
                        <p class="font-medium">{{ $item->product_name }}</p>
                        <p class="text-gray-500">{{ number_format($item->price) }}đ × {{ $item->quantity }}</p>
                    </div>
                    <span class="font-medium">{{ number_format($item->subtotal) }}đ</span>
                </div>
                @endforeach
            </div>
        </div>

        {{-- Totals --}}
        <div class="border-t pt-4 space-y-1 text-sm">
            <div class="flex justify-between"><span class="text-gray-600">Tạm tính</span><span>{{ number_format($order->total_amount) }}đ</span></div>
            @if($order->discount_amount > 0)
            <div class="flex justify-between text-green-600">
                <span class="flex items-center gap-1.5">
                    Giảm giá
                    @if($order->voucher)
                    <span class="font-mono bg-green-100 text-green-700 text-xs px-1.5 py-0.5 rounded">
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
            <div class="flex justify-between"><span class="text-gray-600">Phí vận chuyển</span><span>{{ number_format($order->shipping_fee) }}đ</span></div>
            <div class="flex justify-between font-bold text-base border-t pt-2">
                <span>Tổng cộng</span>
                <span class="text-indigo-600">{{ number_format($order->final_amount) }}đ</span>
            </div>
            <div class="flex justify-between text-gray-500">
                <span>Thanh toán</span>
                <span>{{ $order->payment_method === 'cod' ? 'COD' : 'VNPay' }}</span>
            </div>
        </div>
    </div>
</div>

@endsection
