<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name') }}  @yield('title', 'Mua sắm thông minh')</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <x-analytics />
    @stack('styles')
</head>
<body class="bg-gray-50 text-gray-800">

{{-- Navigation --}}
<nav class="bg-white shadow-sm sticky top-0 z-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
            <a href="{{ route('home') }}" class="text-xl font-bold text-indigo-600">
                {{ config('app.name') }}
            </a>
            <form action="{{ route('shop.products.index') }}" method="GET" class="hidden md:flex flex-1 mx-8">
                <input type="text" name="q" value="{{ request('q') }}"
                    placeholder="Tìm kiếm sản phẩm..."
                    class="w-full border border-gray-300 rounded-l-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-300">
                <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-r-lg hover:bg-indigo-700 transition">
                    Tìm
                </button>
            </form>
            <div class="flex items-center gap-4">
                <a href="{{ route('shop.visual-search') }}" class="text-gray-600 hover:text-indigo-600" title="Tìm bằng hình ảnh">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </a>
                @auth
                <a href="{{ route('shop.wishlist.index') }}" class="text-gray-600 hover:text-indigo-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                    </svg>
                </a>
                @endauth
                <div x-data="{ count: {{ auth()->check() ? app(\App\Services\CartService::class)->getCount(auth()->user()) : 0 }} }"
                     x-on:cart-updated.window="count = $event.detail.count">
                    <a href="{{ route('shop.cart.index') }}" class="relative text-gray-600 hover:text-indigo-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                        <span x-show="count > 0" x-text="count" class="absolute -top-1 -right-1 bg-red-500 text-white text-xs w-4 h-4 rounded-full flex items-center justify-center"></span>
                    </a>
                </div>
                @auth
                <div x-data="{ open: false }" class="relative">
                    <button @click="open = !open" class="flex items-center gap-1 text-gray-700 hover:text-indigo-600">
                        <span class="hidden sm:inline text-sm">{{ auth()->user()->name }}</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div x-show="open" @click.outside="open = false" class="absolute right-0 mt-2 w-48 bg-white shadow-lg rounded-lg py-1 z-50">
                        <a href="{{ route('shop.orders.index') }}" class="flex items-center gap-2.5 px-4 py-2 text-sm hover:bg-gray-100 whitespace-nowrap">
                            <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
                            Đơn hàng
                        </a>
                        <a href="{{ route('shop.addresses.index') }}" class="flex items-center gap-2.5 px-4 py-2 text-sm hover:bg-gray-100 whitespace-nowrap">
                            <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            Quản lý địa chỉ
                        </a>
                        <a href="{{ route('password.change') }}" class="flex items-center gap-2.5 px-4 py-2 text-sm hover:bg-gray-100 whitespace-nowrap">
                            <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                            Đổi mật khẩu
                        </a>
                        @if(auth()->user()->isAdmin())
                        <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2.5 px-4 py-2 text-sm hover:bg-gray-100 whitespace-nowrap">
                            <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065zM15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            Quản trị
                        </a>
                        @endif
                        <div class="border-t border-gray-100 my-1"></div>
                        <form method="POST" action="{{ route('logout') }}">@csrf
                            <button type="submit" class="w-full flex items-center gap-2.5 px-4 py-2 text-sm hover:bg-gray-100 text-red-600 whitespace-nowrap">
                                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                                Đăng xuất
                            </button>
                        </form>
                    </div>
                </div>
                @else
                <a href="{{ route('login') }}" class="text-sm text-gray-600 hover:underline">Đăng nhập</a>
                <a href="{{ route('register') }}" class="text-sm bg-indigo-600 text-white px-3 py-1.5 rounded-lg hover:bg-indigo-700 transition">Đăng ký</a>
                @endauth
            </div>
        </div>
    </div>
    <div class="border-t bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex gap-6 overflow-x-auto py-2 text-sm">
                @foreach(\App\Models\Category::active()->whereNull('parent_id')->orderBy('sort_order')->get() as $cat)
                <a href="{{ route('shop.categories.show', $cat->slug) }}" class="whitespace-nowrap text-gray-600 hover:text-indigo-600">{{ $cat->name }}</a>
                @endforeach
            </div>
        </div>
    </div>
</nav>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
    @if(session('success'))
    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)" class="bg-green-100 border border-green-400 text-green-800 px-4 py-3 rounded relative mb-3">{{ session('success') }}</div>
    @endif
    @if(session('error'))
    <div class="bg-red-100 border border-red-400 text-red-800 px-4 py-3 rounded mb-3">{{ session('error') }}</div>
    @endif
</div>

<main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
    @yield('content')
</main>

<footer class="bg-white border-t mt-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 text-center text-sm text-gray-500">
        &copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
    </div>
</footer>

@stack('scripts')
</body>
</html>
