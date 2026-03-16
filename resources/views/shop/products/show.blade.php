@extends('layouts.app')

@section('title', $product->name)

@section('content')

<div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-12">

    {{-- Images --}}
    <div x-data="{ active: '{{ $product->image_url }}' }">
        <div class="aspect-square rounded-xl overflow-hidden bg-gray-100 mb-3">
            <img :src="active" alt="{{ $product->name }}" class="w-full h-full object-cover">
        </div>
        @if($product->images->count() > 1)
        <div class="flex gap-2 overflow-x-auto">
            @foreach($product->images as $img)
            <button @click="active = '{{ $img->url }}'"
                class="w-16 h-16 flex-shrink-0 rounded-lg overflow-hidden border-2 transition"
                :class="active === '{{ $img->url }}' ? 'border-indigo-500' : 'border-transparent'">
                <img src="{{ $img->url }}" alt="" class="w-full h-full object-cover">
            </button>
            @endforeach
        </div>
        @endif
    </div>

    {{-- Product info --}}
    <div>
        <nav class="text-sm text-gray-500 mb-2">
            <a href="{{ route('home') }}" class="hover:underline">Trang chủ</a> /
            <a href="{{ route('shop.categories.show', $product->category->slug) }}" class="hover:underline">{{ $product->category->name }}</a> /
            <span class="text-gray-700">{{ $product->name }}</span>
        </nav>

        <h1 class="text-2xl font-bold text-gray-900 mb-3">{{ $product->name }}</h1>

        <div class="flex items-center gap-3 mb-4">
            <span class="text-3xl font-bold text-indigo-600">{{ number_format($product->effective_price) }}đ</span>
            @if($product->sale_price)
            <span class="text-lg text-gray-400 line-through">{{ number_format($product->price) }}đ</span>
            <span class="bg-red-100 text-red-600 text-sm px-2 py-0.5 rounded-full">
                -{{ round((1 - $product->sale_price / $product->price) * 100) }}%
            </span>
            @endif
        </div>

        @if($product->attributes->isNotEmpty())
        <div class="mb-4 space-y-1">
            @foreach($product->attributes as $attr)
            <div class="text-sm"><span class="font-medium">{{ $attr->key }}:</span> {{ $attr->value }}</div>
            @endforeach
        </div>
        @endif

        <div x-data="{ qty: 1, loading: false, message: '', messageType: 'success' }">
            <div class="flex items-center gap-3 mb-4">
                <div class="flex items-center border border-gray-300 rounded-lg overflow-hidden">
                    <button @click="qty = Math.max(1, qty - 1)" class="px-3 py-2 hover:bg-gray-100">-</button>
                    <input type="number" x-model="qty" min="1" class="w-12 text-center border-x px-2 py-2 text-sm">
                    <button @click="qty++" class="px-3 py-2 hover:bg-gray-100">+</button>
                </div>

                @auth
                <button
                    :disabled="loading"
                    @click="
                        loading = true;
                        fetch('{{ route('shop.cart.store') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({ product_id: {{ $product->id }}, quantity: qty })
                        })
                        .then(r => r.json())
                        .then(d => {
                            messageType = d.success ? 'success' : 'error';
                            message = d.message;
                            if (d.success) {
                                window.dispatchEvent(new CustomEvent('cart-updated', { detail: { count: d.cart_count } }));
                                if (typeof _trackAddToCart !== 'undefined') _trackAddToCart(qty);
                            }
                            setTimeout(() => message = '', 3000);
                        })
                        .catch(() => {
                            messageType = 'error';
                            message = 'Không thể kết nối. Vui lòng thử lại.';
                            setTimeout(() => message = '', 3000);
                        })
                        .finally(() => loading = false)
                    "
                    class="flex-1 bg-indigo-600 text-white py-2 px-6 rounded-lg hover:bg-indigo-700 transition font-medium disabled:opacity-50">
                    <span x-text="loading ? 'Đang thêm...' : 'Thêm vào giỏ hàng'"></span>
                </button>
                @else
                <a href="{{ route('login') }}"
                   class="flex-1 bg-indigo-600 text-white py-2 px-6 rounded-lg hover:bg-indigo-700 transition font-medium text-center">
                    Đăng nhập để mua hàng
                </a>
                @endauth

                @auth
                <div x-data="{ inWishlist: {{ $inWishlist ? 'true' : 'false' }} }">
                    <button type="button"
                        @click="
                            fetch('{{ route('shop.wishlist.toggle') }}', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                                },
                                body: JSON.stringify({ product_id: {{ $product->id }} })
                            })
                            .then(r => r.json())
                            .then(data => {
                                if (data.success) {
                                    inWishlist = data.in_wishlist;
                                    message = data.message;
                                    messageType = 'success';
                                    setTimeout(() => message = '', 3000);
                                }
                            })
                            .catch(() => {
                                message = 'Không thể kết nối. Vui lòng thử lại.';
                                messageType = 'error';
                                setTimeout(() => message = '', 3000);
                            })
                        "
                        class="p-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition"
                        :title="inWishlist ? 'Xóa khỏi yêu thích' : 'Thêm vào yêu thích'">
                        <svg class="w-5 h-5 transition-colors"
                             :class="inWishlist ? 'text-red-500' : 'text-gray-400'"
                             :fill="inWishlist ? 'currentColor' : 'none'"
                             stroke="currentColor"
                             viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                        </svg>
                    </button>
                </div>
                @endauth
            </div>

            @auth
            <div x-show="message"
                 x-text="message"
                 :class="messageType === 'success' ? 'text-green-700 bg-green-50 border-green-300' : 'text-red-700 bg-red-50 border-red-300'"
                 class="border rounded-lg px-4 py-2 text-sm mt-2">
            </div>
            @endauth
        </div>

        @if($product->description)
        <div class="prose prose-sm max-w-none text-gray-600 border-t pt-4">
            {!! $product->description !!}
        </div>
        @endif
    </div>
</div>

{{-- Recommendations --}}
@if($recommendations->isNotEmpty())
<section class="mb-12">
    <h2 class="text-xl font-bold text-gray-800 mb-4">Sản phẩm liên quan</h2>
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
        @foreach($recommendations as $rec)
        <x-product-card :product="$rec" />
        @endforeach
    </div>
</section>
@endif

{{-- Reviews --}}
<section>
    <h2 class="text-xl font-bold text-gray-800 mb-4">Đánh giá ({{ $product->reviews->count() }})</h2>

    @auth
    <form method="POST" action="{{ route('shop.reviews.store', $product) }}" class="bg-white rounded-lg shadow p-4 mb-6">
        @csrf
        <div class="mb-3">
            <label class="block text-sm font-medium text-gray-700 mb-1">Đánh giá của bạn</label>
            <div class="flex gap-1">
                @for($i = 1; $i <= 5; $i++)
                <label class="cursor-pointer">
                    <input type="radio" name="rating" value="{{ $i }}" class="sr-only" required>
                    <svg class="w-7 h-7 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                    </svg>
                </label>
                @endfor
            </div>
        </div>
        <textarea name="comment" rows="3" placeholder="Nhập nhận xét..."
            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-300 focus:outline-none mb-3"></textarea>
        <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-indigo-700 transition">
            Gửi đánh giá
        </button>
    </form>
    @endauth

    @forelse($product->reviews->load('user') as $review)
    <div class="bg-white rounded-lg shadow p-4 mb-3">
        <div class="flex items-center gap-2 mb-1">
            <span class="font-medium text-sm">{{ $review->user->name }}</span>
            <span class="text-yellow-400 text-sm">{{ str_repeat('★', $review->rating) }}{{ str_repeat('☆', 5 - $review->rating) }}</span>
            <span class="text-xs text-gray-400 ml-auto">{{ $review->created_at->diffForHumans() }}</span>
        </div>
        @if($review->comment)
        <p class="text-sm text-gray-600">{{ $review->comment }}</p>
        @endif
    </div>
    @empty
    <p class="text-gray-500 text-sm">Chưa có đánh giá nào.</p>
    @endforelse
</section>

@endsection

@push('scripts')
<script>
window._trackAddToCart = function(qty) {
    if (typeof gtag === 'undefined') return;
    gtag('event', 'add_to_cart', {
        currency: 'VND',
        value: {{ $product->effective_price }} * qty,
        items: [{
            item_id: '{{ $product->id }}',
            item_name: {{ Illuminate\Support\Js::from($product->name) }},
            item_category: {{ Illuminate\Support\Js::from($product->category->name) }},
            price: {{ $product->effective_price }},
            quantity: qty
        }]
    });
};
(function() {
    if (typeof gtag === 'undefined') return;
    gtag('event', 'view_item', {
        currency: 'VND',
        value: {{ $product->effective_price }},
        items: [{
            item_id: '{{ $product->id }}',
            item_name: {{ Illuminate\Support\Js::from($product->name) }},
            item_category: {{ Illuminate\Support\Js::from($product->category->name) }},
            price: {{ $product->effective_price }},
            quantity: 1
        }]
    });
})();
</script>
@endpush
