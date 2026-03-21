@extends('layouts.app')

@section('title', 'Thanh toán')

@section('content')

<h1 class="text-2xl font-bold mb-6">Thanh toán</h1>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6"
     x-data="checkoutPage({{ $subtotal }}, {{ Illuminate\Support\Js::from($addresses->values()->toArray()) }})">

    {{-- Checkout form --}}
    <div class="lg:col-span-2">
        <form method="POST" action="{{ $formAction ?? route('shop.checkout.store') }}" class="bg-white rounded-lg shadow p-6 space-y-4">
            @csrf

            {{-- Address selector (only shown when saved addresses exist) --}}
            @if($addresses->isNotEmpty())
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Địa chỉ giao hàng <span class="text-red-500">*</span></label>
                <select x-model="selectedAddressId" @change="onAddressChange()"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-300 focus:outline-none bg-white">
                    @foreach($addresses as $addr)
                    <option value="{{ $addr->id }}">
                        {{ $addr->recipient_name }} — {{ $addr->phone }} — {{ Str::limit($addr->address, 50) }}
                        @if($addr->is_default) (Mặc định) @endif
                    </option>
                    @endforeach
                    <option value="">+ Nhập địa chỉ mới</option>
                </select>

                {{-- Display card for selected saved address --}}
                <div x-show="selectedAddressId !== ''" x-cloak
                    class="mt-2 p-3 bg-indigo-50 border border-indigo-200 rounded-lg text-sm text-gray-700 space-y-0.5">
                    <p class="font-medium" x-text="currentAddress.recipient_name"></p>
                    <p x-text="currentAddress.phone"></p>
                    <p x-text="currentAddress.address"></p>
                </div>
            </div>

            {{-- Hidden inputs for selected saved address --}}
            <input type="hidden" name="address_id" :value="selectedAddressId !== '' ? selectedAddressId : ''">

            {{-- Inline address fields (shown when "new address" or no addresses) --}}
            <div x-show="selectedAddressId === ''" x-cloak>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Họ tên người nhận <span class="text-red-500">*</span></label>
                        <input type="text" name="recipient_name" value="{{ old('recipient_name', auth()->user()->name) }}"
                            class="w-full border @error('recipient_name') border-red-400 @else border-gray-300 @enderror rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-300 focus:outline-none">
                        @error('recipient_name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Số điện thoại <span class="text-red-500">*</span></label>
                        <input type="text" name="phone" value="{{ old('phone', auth()->user()->phone) }}"
                            class="w-full border @error('phone') border-red-400 @else border-gray-300 @enderror rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-300 focus:outline-none">
                        @error('phone')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                </div>
                <div class="mt-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Địa chỉ giao hàng chi tiết <span class="text-red-500">*</span></label>
                    <input type="text" name="shipping_address" value="{{ old('shipping_address', auth()->user()->address) }}"
                        class="w-full border @error('shipping_address') border-red-400 @else border-gray-300 @enderror rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-300 focus:outline-none">
                    @error('shipping_address')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
            </div>

            @else
            {{-- No saved addresses: always show inline fields --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Họ tên người nhận <span class="text-red-500">*</span></label>
                    <input type="text" name="recipient_name" value="{{ old('recipient_name', auth()->user()->name) }}"
                        class="w-full border @error('recipient_name') border-red-400 @else border-gray-300 @enderror rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-300 focus:outline-none">
                    @error('recipient_name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Số điện thoại <span class="text-red-500">*</span></label>
                    <input type="text" name="phone" value="{{ old('phone', auth()->user()->phone) }}"
                        class="w-full border @error('phone') border-red-400 @else border-gray-300 @enderror rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-300 focus:outline-none">
                    @error('phone')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Địa chỉ giao hàng <span class="text-red-500">*</span></label>
                <input type="text" name="shipping_address" value="{{ old('shipping_address', auth()->user()->address) }}"
                    class="w-full border @error('shipping_address') border-red-400 @else border-gray-300 @enderror rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-300 focus:outline-none">
                @error('shipping_address')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
            @endif

            {{-- Voucher --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Mã voucher</label>
                <div class="flex gap-2">
                    <input type="text" name="voucher_code" x-model="voucherCode"
                        placeholder="Nhập mã giảm giá (nếu có)"
                        class="flex-1 border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-300 focus:outline-none">
                    <button type="button" @click="validateVoucher()"
                        :disabled="voucherLoading"
                        class="bg-indigo-100 text-indigo-700 border border-indigo-300 px-4 py-2 rounded-lg text-sm font-medium hover:bg-indigo-200 transition disabled:opacity-50 disabled:cursor-not-allowed whitespace-nowrap">
                        <span x-text="voucherLoading ? 'Đang kiểm tra...' : 'Áp dụng'"></span>
                    </button>
                </div>
                <p x-show="voucherMessage" x-text="voucherMessage"
                   :class="voucherStatus === 'success' ? 'text-green-600' : 'text-red-500'"
                   class="text-xs mt-1"></p>
                @error('voucher_code')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            {{-- Payment method --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Phương thức thanh toán <span class="text-red-500">*</span></label>
                <div class="space-y-2">
                    @foreach($paymentMethods as $pm)
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="payment_method" value="{{ $pm->code }}"
                            {{ old('payment_method', $paymentMethods->first()?->code) === $pm->code ? 'checked' : '' }}>
                        <span class="text-sm">{{ $pm->name }}</span>
                        @if($pm->description)
                        <span class="text-xs text-gray-400">— {{ $pm->description }}</span>
                        @endif
                    </label>
                    @endforeach
                </div>
                @error('payment_method')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            {{-- Note --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Ghi chú</label>
                <textarea name="note" rows="2" placeholder="Ghi chú cho đơn hàng (tùy chọn)"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-300 focus:outline-none">{{ old('note') }}</textarea>
            </div>

            <button type="submit"
                class="w-full bg-indigo-600 text-white py-3 rounded-lg hover:bg-indigo-700 transition font-medium text-lg">
                Đặt hàng
            </button>
        </form>
    </div>

    {{-- Order summary --}}
    <div class="bg-white rounded-lg shadow p-5 h-fit">
        <h2 class="font-semibold text-lg mb-4">Đơn hàng của bạn</h2>
        @foreach($cartItems as $item)
        <div class="flex justify-between text-sm mb-2">
            <span class="truncate mr-2">{{ $item->product->name }} × {{ $item->quantity }}</span>
            <span class="flex-shrink-0">{{ number_format($item->subtotal) }}đ</span>
        </div>
        @endforeach
        <div class="border-t pt-3 mt-3 space-y-1.5 text-sm">
            <div class="flex justify-between text-gray-600">
                <span>Tạm tính</span>
                <span>{{ number_format($subtotal) }}đ</span>
            </div>
            <div x-show="discount > 0" class="flex justify-between text-green-600">
                <span>Giảm giá</span>
                <span>-<span x-text="discountFmt"></span>đ</span>
            </div>
            <div class="flex justify-between text-gray-600">
                <span>Phí vận chuyển</span>
                <span>30.000đ</span>
            </div>
            <div class="flex justify-between font-bold text-base pt-1.5 border-t">
                <span>Tổng cộng</span>
                <span class="text-indigo-600" x-text="totalFmt + 'đ'"></span>
            </div>
        </div>
    </div>

</div>

@endsection

@push('scripts')
<script>
function checkoutPage(subtotal, addresses) {
    const defaultAddress = addresses.find(a => a.is_default) || addresses[0] || null;

    return {
        // Address
        addresses: addresses,
        selectedAddressId: defaultAddress ? String(defaultAddress.id) : '',
        get currentAddress() {
            const id = parseInt(this.selectedAddressId);
            return this.addresses.find(a => a.id === id) || {};
        },
        onAddressChange() {
            // no extra work needed — currentAddress getter handles it
        },

        // Voucher
        voucherCode: '{{ old('voucher_code') }}',
        voucherLoading: false,
        voucherMessage: '',
        voucherStatus: '',

        // Totals
        subtotal: subtotal,
        shippingFee: 30000,
        discount: 0,
        get total() { return Math.max(0, this.subtotal - this.discount + this.shippingFee); },
        get totalFmt() { return this.total.toLocaleString('vi-VN'); },
        get discountFmt() { return this.discount.toLocaleString('vi-VN'); },

        validateVoucher() {
            const code = this.voucherCode.trim();
            if (!code) {
                this.voucherMessage = 'Vui lòng nhập mã voucher.';
                this.voucherStatus = 'error';
                return;
            }
            this.voucherLoading = true;
            this.voucherMessage = '';
            fetch('{{ route('shop.checkout.validate-voucher') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                },
                body: JSON.stringify({ voucher_code: code, subtotal: this.subtotal }),
            })
            .then(r => r.json())
            .then(data => {
                this.voucherStatus = data.success ? 'success' : 'error';
                this.voucherMessage = data.message;
                if (data.success) {
                    this.discount = data.discount;
                } else {
                    this.discount = 0;
                }
            })
            .catch(() => {
                this.voucherStatus = 'error';
                this.voucherMessage = 'Không thể kết nối. Vui lòng thử lại.';
            })
            .finally(() => { this.voucherLoading = false; });
        },
    };
}

(function() {
    if (typeof gtag === 'undefined') return;
    gtag('event', 'begin_checkout', {
        currency: 'VND',
        value: {{ $subtotal + 30000 }},
        items: [
            @foreach($cartItems as $item)
            {
                item_id: '{{ $item->product_id }}',
                item_name: {{ Illuminate\Support\Js::from($item->product->name) }},
                price: {{ $item->product->effective_price }},
                quantity: {{ $item->quantity }}
            },
            @endforeach
        ]
    });
})();
</script>
@endpush

