<?php

namespace App\Http\Requests\Shop;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAddressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'recipient_name' => ['required', 'string', 'max:255'],
            'phone'          => ['required', 'string', 'max:20'],
            'address'        => ['required', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'recipient_name.required' => 'Vui lòng nhập họ tên người nhận.',
            'phone.required'          => 'Vui lòng nhập số điện thoại.',
            'address.required'        => 'Vui lòng nhập địa chỉ giao hàng.',
        ];
    }
}
