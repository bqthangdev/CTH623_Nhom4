<?php

namespace App\Http\Requests\Shop;

use Illuminate\Foundation\Http\FormRequest;

class StoreVisualSearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'image' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ];
    }

    public function messages(): array
    {
        return [
            'image.required' => 'Vui lòng chọn một hình ảnh.',
            'image.image'    => 'File phải là hình ảnh.',
            'image.mimes'    => 'Chỉ chấp nhận ảnh định dạng jpg, jpeg, png, webp.',
            'image.max'      => 'Ảnh không được vượt quá 5MB.',
        ];
    }
}
