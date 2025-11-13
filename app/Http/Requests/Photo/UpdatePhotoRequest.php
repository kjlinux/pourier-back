<?php

namespace App\Http\Requests\Photo;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePhotoRequest extends FormRequest
{
    public function authorize(): bool
    {
        $photo = $this->route('photo');
        return $this->user() && $this->user()->id === $photo->photographer_id;
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'min:5', 'max:200'],
            'description' => ['nullable', 'string', 'max:2000'],
            'category_id' => ['sometimes', 'exists:categories,id'],
            'tags' => ['sometimes', 'string', function ($attribute, $value, $fail) {
                $tags = array_filter(array_map('trim', explode(',', $value)));
                if (count($tags) < 3) {
                    $fail('Vous devez fournir au moins 3 tags');
                }
                if (count($tags) > 20) {
                    $fail('Vous ne pouvez pas fournir plus de 20 tags');
                }
            }],
            'price_standard' => ['sometimes', 'integer', 'min:500'],
            'price_extended' => ['sometimes', 'integer', 'gte:price_standard', function ($attribute, $value, $fail) {
                $photo = $this->route('photo');
                $priceStandard = $this->input('price_standard', $photo->price_standard);
                if ($value < ($priceStandard * 2)) {
                    $fail('Le prix extended doit être au moins le double du prix standard');
                }
            }],
            'location' => ['nullable', 'string', 'max:100'],
        ];
    }
}
