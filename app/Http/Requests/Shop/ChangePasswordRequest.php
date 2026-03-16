<?php

namespace App\Http\Requests\Shop;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class ChangePasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'password' => ['required', 'confirmed', Password::defaults()],
        ];

        // Khi bị buộc đổi mật khẩu (admin reset), không cần nhập mật khẩu hiện tại
        // vì user vừa đăng nhập bằng mật khẩu tạm thời — đủ xác minh danh tính
        if (! $this->user()->must_change_password) {
            $rules['current_password'] = ['required', 'current_password'];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'current_password.required'     => 'Vui lòng nhập mật khẩu hiện tại.',
            'current_password.current_password' => 'Mật khẩu hiện tại không đúng.',
            'password.required'             => 'Vui lòng nhập mật khẩu mới.',
            'password.confirmed'            => 'Xác nhận mật khẩu không khớp.',
        ];
    }
}
