@extends('layouts.admin')

@section('title', 'Sửa phương thức thanh toán')

@section('content')

<div class="max-w-2xl">
    <a href="{{ route('admin.payment-methods.index') }}" class="text-sm text-gray-500 hover:text-gray-700 mb-4 inline-block">← Quay lại</a>

    @if(session('error'))
    <div class="mb-4 px-4 py-3 bg-red-50 border border-red-200 text-red-800 rounded-lg text-sm">{{ session('error') }}</div>
    @endif

    <form method="POST" action="{{ route('admin.payment-methods.update', $paymentMethod->id) }}"
        class="bg-white rounded-lg shadow p-6 space-y-4">
        @csrf @method('PUT')

        @include('admin.payment-methods._form', ['method' => $paymentMethod])

        <button type="submit" class="bg-indigo-600 text-white px-6 py-2 rounded-lg hover:bg-indigo-700 transition">
            Lưu thay đổi
        </button>
    </form>
</div>

@endsection
