<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrderShippingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->role === 'admin';
    }

    public function rules(): array
    {
        return [
            'shipping_carrier_id' => ['required', 'exists:shipping_carriers,id'],
            'tracking_code'       => ['required', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'shipping_carrier_id.required' => 'Vui lòng chọn đơn vị vận chuyển.',
            'shipping_carrier_id.exists'   => 'Đơn vị vận chuyển không hợp lệ.',
            'tracking_code.required'       => 'Vui lòng nhập mã vận đơn.',
            'tracking_code.max'            => 'Mã vận đơn không được vượt quá 100 ký tự.',
        ];
    }
}
