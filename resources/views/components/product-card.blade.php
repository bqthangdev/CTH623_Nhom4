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
                @endif
            </div>
            <div class="h-6 mt-1">
                @if($product->sale_price)
                <span class="inline-block text-xs bg-red-100 text-red-600 px-2 py-0.5 rounded-full">
                    -{{ round((1 - $product->sale_price / $product->price) * 100) }}%
                </span>
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
