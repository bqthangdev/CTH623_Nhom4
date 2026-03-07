<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Admin — @yield('title', 'Quản trị') | {{ config('app.name') }}</title>
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
                    ['route' => 'admin.dashboard',        'label' => 'Tổng quan'],
                    ['route' => 'admin.products.index',   'label' => 'Sản phẩm'],
                    ['route' => 'admin.categories.index', 'label' => 'Danh mục'],
                    ['route' => 'admin.orders.index',     'label' => 'Đơn hàng'],
                    ['route' => 'admin.customers.index',  'label' => 'Khách hàng'],
                    ['route' => 'admin.banners.index',    'label' => 'Banner'],
                    ['route' => 'admin.vouchers.index',   'label' => 'Voucher'],
                ];
            @endphp
            @foreach($navItems as $item)
            <a href="{{ route($item['route']) }}"
               class="block px-3 py-2 rounded-lg text-sm transition
                      {{ request()->routeIs($item['route'] . '*') ? 'bg-indigo-600 text-white' : 'text-gray-300 hover:bg-gray-700' }}">
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
