@props(['disabled' => false])

<div x-data="{ show: false }" class="relative">
    <input
        :type="show ? 'text' : 'password'"
        @disabled($disabled)
        {{ $attributes->merge(['class' => 'border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm pr-10']) }}
    >
    <button
        type="button"
        @click="show = !show"
        :aria-label="show ? 'Ẩn mật khẩu' : 'Hiện mật khẩu'"
        tabindex="-1"
        class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-600 focus:outline-none"
    >
        {{-- Eye open: shown when password is hidden --}}
        <svg x-show="!show" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7
                   -1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
        </svg>
        {{-- Eye off: shown when password is visible --}}
        <svg x-show="show" x-cloak class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M13.875 18.825A10.05 10.05 0 0112 19c-4.477 0-8.268-2.943-9.542-7
                   a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243
                   M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29
                   m7.532 7.532l3.29 3.29M3 3l18 18"/>
        </svg>
    </button>
</div>
