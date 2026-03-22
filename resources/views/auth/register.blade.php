<x-guest-layout>
    <form method="POST" action="{{ route('register') }}">
        @csrf

        {{-- Họ tên --}}
        <div>
            <x-input-label for="name" value="Họ tên" />
            <x-text-input id="name" class="block mt-1 w-full" type="text" name="name"
                :value="old('name')" required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        {{-- Email --}}
        <div class="mt-4">
            <x-input-label for="email" value="Email" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email"
                :value="old('email')" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        {{-- Số điện thoại --}}
        <div class="mt-4">
            <x-input-label for="phone" value="Số điện thoại (tùy chọn)" />
            <x-text-input id="phone" class="block mt-1 w-full" type="tel" name="phone"
                :value="old('phone')" autocomplete="tel" />
            <x-input-error :messages="$errors->get('phone')" class="mt-2" />
        </div>

        {{-- Mật khẩu --}}
        <div class="mt-4">
            <x-input-label for="password" value="Mật khẩu" />
            <x-password-input id="password" class="block mt-1 w-full"
                name="password" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        {{-- Xác nhận mật khẩu --}}
        <div class="mt-4">
            <x-input-label for="password_confirmation" value="Xác nhận mật khẩu" />
            <x-password-input id="password_confirmation" class="block mt-1 w-full"
                name="password_confirmation" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-6">
            <a class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md
                       focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
               href="{{ route('login') }}">
                Đã có tài khoản?
            </a>
            <x-primary-button class="ms-4">
                Đăng ký
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
