@extends('layouts.admin')

@section('title', 'Sửa danh mục')

@section('content')

<div class="max-w-lg">
    <a href="{{ route('admin.categories.index') }}" class="text-sm text-gray-500 hover:text-gray-700 mb-4 inline-block">← Quay lại</a>

    <form method="POST" action="{{ route('admin.categories.update', $category->id) }}" enctype="multipart/form-data"
        class="bg-white rounded-lg shadow p-6">
        @csrf @method('PUT')
        @include('admin.categories._form', ['category' => $category, 'parents' => $parents])
        <div class="mt-5">
            <button type="submit" class="bg-indigo-600 text-white px-6 py-2 rounded-lg hover:bg-indigo-700 transition">
                Lưu thay đổi
            </button>
        </div>
    </form>
</div>

@endsection
