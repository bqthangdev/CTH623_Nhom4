<?php

namespace App\Http\Requests\Shop;

use Illuminate\Foundation\Http\FormRequest;

class StoreReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'rating'   => ['required', 'integer', 'min:1', 'max:5'],
            'comment'  => ['nullable', 'string', 'max:1000'],
            'order_id' => ['nullable', 'integer', 'exists:orders,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'rating.required' => 'Vui lòng chọn số sao đánh giá.',
            'rating.min'      => 'Đánh giá tối thiểu là 1 sao.',
            'rating.max'      => 'Đánh giá tối đa là 5 sao.',
        ];
    }
}
