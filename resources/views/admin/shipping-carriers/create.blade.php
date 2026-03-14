@extends('layouts.admin')

@section('title', 'Thêm đơn vị vận chuyển')

@section('content')

<div class="max-w-lg">
    <a href="{{ route('admin.shipping-carriers.index') }}" class="text-sm text-gray-500 hover:text-gray-700 mb-4 inline-block">← Quay lại</a>

    <form method="POST" action="{{ route('admin.shipping-carriers.store') }}"
        class="bg-white rounded-lg shadow p-6">
        @csrf
        @include('admin.shipping-carriers._form')
        <div class="mt-5">
            <button type="submit" class="bg-indigo-600 text-white px-6 py-2 rounded-lg hover:bg-indigo-700 transition">
                Thêm đơn vị vận chuyển
            </button>
        </div>
    </form>
</div>

@endsection
