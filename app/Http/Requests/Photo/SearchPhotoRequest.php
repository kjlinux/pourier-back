<?php

namespace App\Http\Requests\Photo;

use Illuminate\Foundation\Http\FormRequest;

class SearchPhotoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'query' => ['nullable', 'string', 'max:200'],
            'categories' => ['nullable', 'array'],
            'categories.*' => ['exists:categories,id'],
            'photographer_id' => ['nullable', 'exists:users,id'],
            'min_price' => ['nullable', 'integer', 'min:0'],
            'max_price' => ['nullable', 'integer', 'gte:min_price'],
            'orientation' => ['nullable', 'in:landscape,portrait,square'],
            'sort_by' => ['nullable', 'in:popularity,date,price_asc,price_desc'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
