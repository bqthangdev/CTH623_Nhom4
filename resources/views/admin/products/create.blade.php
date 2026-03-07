@extends('layouts.admin')

@section('title', 'Thêm sản phẩm')

@section('content')

<div class="max-w-2xl">
    <a href="{{ route('admin.products.index') }}" class="text-sm text-gray-500 hover:text-gray-700 mb-4 inline-block">← Quay lại</a>

    <form method="POST" action="{{ route('admin.products.store') }}" enctype="multipart/form-data"
        class="bg-white rounded-lg shadow p-6 space-y-5">
        @csrf

        @include('admin.products._form')

        <div class="pt-2">
            <button type="submit" class="bg-indigo-600 text-white px-6 py-2 rounded-lg hover:bg-indigo-700 transition">
                Tạo sản phẩm
            </button>
        </div>
    </form>
</div>

@endsection
