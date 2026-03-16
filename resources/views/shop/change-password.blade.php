@extends('layouts.app')

@section('title', 'Đổi mật khẩu')

@section('content')

<div class="max-w-md mx-auto">

    @if(auth()->user()->must_change_password)
    <div class="mb-6 bg-amber-50 border border-amber-300 rounded-xl px-4 py-3 text-sm text-amber-800 flex items-start gap-3">
        <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
        </svg>
        <span>Tài khoản của bạn đang dùng mật khẩu tạm thời do quản trị viên cấp.
              Vui lòng đặt mật khẩu mới để tiếp tục sử dụng.</span>
    </div>
    @endif

    <div class="bg-white rounded-lg shadow p-6">
        <h1 class="text-xl font-bold mb-6">Đổi mật khẩu</h1>

        <form method="POST" action="{{ route('password.change.store') }}" class="space-y-5">
            @csrf

            {{-- Mật khẩu hiện tại (chỉ yêu cầu khi đổi tự nguyện) --}}
            @if(! auth()->user()->must_change_password)
            <div>
                <label for="current_password" class="block text-sm font-medium text-gray-700 mb-1">
                    Mật khẩu hiện tại
                </label>
                <input id="current_password" type="password" name="current_password"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-300
                           @error('current_password') border-red-400 @enderror"
                    autocomplete="current-password">
                @error('current_password')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
            @endif

            {{-- Mật khẩu mới --}}
            <div>
                <label for="password" class="block text-sm font-medium text-gray-700 mb-1">
                    Mật khẩu mới
                </label>
                <input id="password" type="password" name="password"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-300
                           @error('password') border-red-400 @enderror"
                    autocomplete="new-password">
                @error('password')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Xác nhận mật khẩu mới --}}
            <div>
                <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">
                    Xác nhận mật khẩu mới
                </label>
                <input id="password_confirmation" type="password" name="password_confirmation"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-300"
                    autocomplete="new-password">
            </div>

            <button type="submit"
                class="w-full bg-indigo-600 text-white py-2.5 rounded-lg hover:bg-indigo-700 transition font-medium">
                Cập nhật mật khẩu
            </button>
        </form>
    </div>

</div>

@endsection
