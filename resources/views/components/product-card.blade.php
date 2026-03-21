@props(['product'])

<div class="bg-white rounded-lg shadow hover:shadow-md transition overflow-hidden group flex flex-col">
    <a href="{{ route('shop.products.show', $product->slug) }}" class="block flex-1">
        <div class="aspect-square overflow-hidden bg-gray-100">
            <img src="{{ $product->image_url }}" alt="{{ $product->name }}"
                class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
        </div>
        <div class="p-4">
            <h3 class="text-sm font-medium text-gray-800 truncate">{{ $product->name }}</h3>
            <div class="mt-1 flex items-center gap-2">
                <span class="text-indigo-600 font-semibold">
                    {{ number_format($product->effective_price) }}đ
                </span>
                @if($product->sale_price)
                <span class="text-xs text-gray-400 line-through">
                    {{ number_format($product->price) }}đ
                </span>
                <span class="text-xs bg-red-100 text-red-600 px-2 py-0.5 rounded-full">
                    -{{ round((1 - $product->sale_price / $product->price) * 100) }}%
                </span>
                @endif
            </div>
            <div class="h-5 mt-1 flex items-center">
                @if($product->average_rating !== null)
                <div class="flex items-center gap-1">
                    <svg class="w-3.5 h-3.5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                    </svg>
                    <span class="text-xs text-gray-600">{{ number_format($product->average_rating, 1) }}</span>
                    <span class="text-xs text-gray-400">({{ $product->reviews_count }})</span>
                </div>
                @else
                <span class="text-xs text-gray-400 italic">Chưa có đánh giá</span>
                @endif
            </div>
        </div>
    </a>
    <div class="px-4 pb-4">
        @auth
        <button
            x-data="{ loading: false, added: false }"
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
                    body: JSON.stringify({ product_id: {{ $product->id }}, quantity: 1 })
                })
                .then(r => r.json())
                .then(d => {
                    if (d.success) {
                        added = true;
                        window.dispatchEvent(new CustomEvent('cart-updated', { detail: { count: d.cart_count } }));
                        setTimeout(() => added = false, 2000);
                    }
                })
                .finally(() => loading = false)
            "
            :class="added ? 'bg-green-600 hover:bg-green-700' : 'bg-indigo-600 hover:bg-indigo-700'"
            class="w-full text-white py-1.5 rounded-lg text-sm transition disabled:opacity-50">
            <span x-text="loading ? 'Đang thêm...' : (added ? '✓ Đã thêm' : 'Thêm vào giỏ')"></span>
        </button>
        @else
        <a href="{{ route('login') }}"
           class="block w-full bg-indigo-600 text-white py-1.5 rounded-lg text-sm hover:bg-indigo-700 transition text-center">
            Thêm vào giỏ
        </a>
        @endauth
    </div>
</div>
