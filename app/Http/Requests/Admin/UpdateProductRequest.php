<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'name'               => ['required', 'string', 'max:255'],
            'price'              => ['required', 'numeric', 'min:0'],
            'sale_price'         => ['nullable', 'numeric', 'min:0', 'lt:price'],
            'category_id'        => ['required', 'exists:categories,id'],
            'stock'              => ['required', 'integer', 'min:0'],
            'description'        => ['nullable', 'string', 'max:20000'],
            'status'             => ['boolean'],
            'is_featured'        => ['boolean'],
            'images'             => ['nullable', 'array', 'max:10'],
            'images.*'           => ['image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'attributes'         => ['nullable', 'array'],
            'attributes.*.key'   => ['required_with:attributes', 'string', 'max:100'],
            'attributes.*.value' => ['required_with:attributes', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'        => 'Vui lòng nhập tên sản phẩm.',
            'price.required'       => 'Vui lòng nhập giá sản phẩm.',
            'sale_price.lt'        => 'Giá khuyến mãi phải nhỏ hơn giá gốc.',
            'category_id.required' => 'Vui lòng chọn danh mục.',
            'category_id.exists'   => 'Danh mục không tồn tại.',
            'images.*.max'         => 'Mỗi ảnh không được vượt quá 2MB.',
        ];
    }
}
