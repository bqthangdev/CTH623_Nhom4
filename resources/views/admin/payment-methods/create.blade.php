@extends('layouts.admin')

@section('title', 'Thêm phương thức thanh toán')

@section('content')

<div class="max-w-2xl">
    <a href="{{ route('admin.payment-methods.index') }}" class="text-sm text-gray-500 hover:text-gray-700 mb-4 inline-block">← Quay lại</a>

    <form method="POST" action="{{ route('admin.payment-methods.store') }}"
        class="bg-white rounded-lg shadow p-6 space-y-4">
        @csrf

        @include('admin.payment-methods._form')

        <button type="submit" class="bg-indigo-600 text-white px-6 py-2 rounded-lg hover:bg-indigo-700 transition">
            Tạo phương thức
        </button>
    </form>
</div>

@endsection
