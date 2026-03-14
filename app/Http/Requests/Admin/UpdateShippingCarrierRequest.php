<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateShippingCarrierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->role === 'admin';
    }

    public function rules(): array
    {
        return [
            'name'      => ['required', 'string', 'max:100', Rule::unique('shipping_carriers', 'name')->ignore($this->shipping_carrier)],
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
