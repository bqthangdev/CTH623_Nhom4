<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreShippingCarrierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->role === 'admin';
    }

    public function rules(): array
    {
        return [
            'name'      => ['required', 'string', 'max:100', 'unique:shipping_carriers,name'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Vui lòng nhập tên đơn vị vận chuyển.',
            'name.unique'   => 'Tên đơn vị vận chuyển đã tồn tại.',
            'name.max'      => 'Tên không được vượt quá 100 ký tự.',
        ];
    }
}
