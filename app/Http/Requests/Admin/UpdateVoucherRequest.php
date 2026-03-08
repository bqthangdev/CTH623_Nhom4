<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateVoucherRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        $voucherId = $this->route('voucher')?->id;

        return [
            'code'       => ['required', 'string', 'max:50', Rule::unique('vouchers', 'code')->ignore($voucherId)],
            'type'       => ['required', 'in:fixed,percent'],
            'value'      => ['required', 'numeric', 'min:0'],
            'min_order'  => ['nullable', 'numeric', 'min:0'],
            'max_uses'   => ['nullable', 'integer', 'min:1'],
            'expires_at' => ['nullable', 'date'],
            'is_active'  => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required'  => 'Vui lòng nhập mã voucher.',
            'code.unique'    => 'Mã voucher đã tồn tại.',
            'type.required'  => 'Vui lòng chọn loại giảm giá.',
            'type.in'        => 'Loại giảm giá không hợp lệ.',
            'value.required' => 'Vui lòng nhập giá trị giảm.',
        ];
    }
}
