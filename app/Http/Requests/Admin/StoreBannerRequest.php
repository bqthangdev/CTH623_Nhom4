<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreBannerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
        ]);
    }

    public function rules(): array
    {
        return [
            'title'      => ['required', 'string', 'max:255'],
            'image'      => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'link'       => ['nullable', 'url', 'max:500'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active'  => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Vui lòng nhập tiêu đề banner.',
            'image.required' => 'Vui lòng tải lên hình ảnh banner.',
            'image.image'    => 'File phải là hình ảnh.',
            'image.max'      => 'Ảnh không được vượt quá 5MB.',
            'link.url'       => 'Đường dẫn không hợp lệ.',
        ];
    }
}
