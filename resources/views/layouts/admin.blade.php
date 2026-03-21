<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Admin — @yield('title', 'Quản trị') | {{ config('app.name') }}</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="bg-gray-100 text-gray-800">

<div class="flex min-h-screen">

    {{-- Sidebar --}}
    <aside class="w-64 bg-gray-900 text-white flex-shrink-0 flex flex-col">
        <div class="px-6 py-5 border-b border-gray-700">
            <a href="{{ route('admin.dashboard') }}" class="text-lg font-bold text-white">
                {{ config('app.name') }} Admin
            </a>
        </div>

        <nav class="flex-1 px-4 py-6 space-y-1">
            @php
                $navItems = [
                    ['route' => 'admin.dashboard',        'label' => 'Tổng quan',  'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>'],
                    ['route' => 'admin.products.index',   'label' => 'Sản phẩm',   'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>'],
                    ['route' => 'admin.categories.index', 'label' => 'Danh mục',   'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>'],
                    ['route' => 'admin.orders.index',     'label' => 'Đơn hàng',   'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>'],
                    ['route' => 'admin.customers.index',  'label' => 'Khách hàng', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>'],
                    ['route' => 'admin.banners.index',           'label' => 'Banner',     'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>'],
                    ['route' => 'admin.vouchers.index',           'label' => 'Voucher',    'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/>'],
                    ['route' => 'admin.payment-methods.index',   'label' => 'Thanh toán', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>'],
                    ['route' => 'admin.shipping-carriers.index', 'label' => 'Vận chuyển', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0zM13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1"/>'],
                ];
            @endphp
            @foreach($navItems as $item)
            <a href="{{ route($item['route']) }}"
               class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition
                      {{ request()->routeIs($item['route'] . '*') ? 'bg-indigo-600 text-white' : 'text-gray-300 hover:bg-gray-700' }}">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    {!! $item['icon'] !!}
                </svg>
                {{ $item['label'] }}
            </a>
            @endforeach
        </nav>

        <div class="px-4 py-4 border-t border-gray-700">
            <a href="{{ route('home') }}" class="block text-xs text-gray-400 hover:text-white mb-2">← Về cửa hàng</a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="text-xs text-gray-400 hover:text-white">Đăng xuất</button>
            </form>
        </div>
    </aside>

    {{-- Main area --}}
    <div class="flex-1 flex flex-col min-w-0">

        {{-- Top bar --}}
        <header class="bg-white shadow-sm px-6 py-4 flex items-center justify-between">
            <h1 class="text-xl font-semibold text-gray-700">@yield('title', 'Tổng quan')</h1>
            <span class="text-sm text-gray-500">{{ auth()->user()->name }}</span>
        </header>

        {{-- Flash messages --}}
        <div class="px-6 pt-4">
            @if(session('success'))
            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
                class="bg-green-100 border border-green-400 text-green-800 px-4 py-3 rounded mb-3">
                {{ session('success') }}
            </div>
            @endif
            @if(session('error'))
            <div class="bg-red-100 border border-red-400 text-red-800 px-4 py-3 rounded mb-3">
                {{ session('error') }}
            </div>
            @endif
        </div>

        {{-- Page content --}}
        <main class="flex-1 px-6 py-4">
            @yield('content')
        </main>

    </div>
</div>

@stack('scripts')
</body>
</html>
