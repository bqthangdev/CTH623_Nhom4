<?php

namespace App\Http\Requests\Shop;

use App\Models\PaymentMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $activeCodes = PaymentMethod::active()->pluck('code')->all();

        return [
            'recipient_name'   => ['required', 'string', 'max:255'],
            'phone'            => ['required', 'string', 'max:20'],
            'shipping_address' => ['required', 'string', 'max:500'],
            'payment_method'   => ['required', 'string', Rule::in($activeCodes)],
            'voucher_code'     => ['nullable', 'string', 'max:50'],
            'note'             => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'recipient_name.required'   => 'Vui lòng nhập họ tên người nhận.',
            'phone.required'            => 'Vui lòng nhập số điện thoại.',
            'shipping_address.required' => 'Vui lòng nhập địa chỉ giao hàng.',
            'payment_method.required'   => 'Vui lòng chọn phương thức thanh toán.',
            'payment_method.in'         => 'Phương thức thanh toán không hợp lệ.',
        ];
    }
}
