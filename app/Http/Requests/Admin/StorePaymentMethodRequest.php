<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePaymentMethodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'name'        => ['required', 'string', 'max:100'],
            'code'        => ['required', 'string', 'max:50', 'alpha_dash', 'unique:payment_methods,code'],
            'description' => ['nullable', 'string', 'max:500'],
            'is_external' => ['boolean'],
            'is_active'   => ['boolean'],
            'sort_order'  => ['nullable', 'integer', 'min:0'],
            'config'      => ['nullable', 'array'],
            'config.*'    => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'    => 'Vui lòng nhập tên phương thức thanh toán.',
            'code.required'    => 'Vui lòng nhập mã định danh.',
            'code.alpha_dash'  => 'Mã định danh chỉ được chứa chữ, số, dấu gạch dưới và gạch ngang.',
            'code.unique'      => 'Mã định danh đã tồn tại.',
        ];
    }
}
