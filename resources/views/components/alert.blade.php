@props(['type' => 'success', 'message'])

@php
    $classes = [
        'success' => 'bg-green-100 border-green-400 text-green-800',
        'error'   => 'bg-red-100 border-red-400 text-red-800',
        'info'    => 'bg-blue-100 border-blue-400 text-blue-800',
        'warning' => 'bg-yellow-100 border-yellow-400 text-yellow-800',
    ][$type] ?? 'bg-gray-100 border-gray-400 text-gray-800';
@endphp

<div class="border {{ $classes }} px-4 py-3 rounded">
    {{ $message ?? $slot }}
</div>
