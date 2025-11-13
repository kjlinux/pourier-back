<?php

namespace App\Http\Requests\Photo;

use Illuminate\Foundation\Http\FormRequest;

class StorePhotoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->account_type === 'photographer';
    }

    public function rules(): array
    {
        return [
            'photos' => ['required', 'array', 'min:1'],
            'photos.*' => ['required', 'image', 'mimes:jpeg,jpg,png', 'max:51200'], // 50MB
            'title' => ['required', 'string', 'min:5', 'max:200'],
            'description' => ['nullable', 'string', 'max:2000'],
            'category_id' => ['required', 'exists:categories,id'],
            'tags' => ['required', 'string', function ($attribute, $value, $fail) {
                $tags = array_filter(array_map('trim', explode(',', $value)));
                if (count($tags) < 3) {
                    $fail('Vous devez fournir au moins 3 tags');
                }
                if (count($tags) > 20) {
                    $fail('Vous ne pouvez pas fournir plus de 20 tags');
                }
            }],
            'price_standard' => ['required', 'integer', 'min:500'], // en FCFA
            'price_extended' => ['required', 'integer', 'gte:price_standard', function ($attribute, $value, $fail) {
                $priceStandard = $this->input('price_standard');
                if ($value < ($priceStandard * 2)) {
                    $fail('Le prix extended doit être au moins le double du prix standard');
                }
            }],
            'location' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'photos.required' => 'Vous devez uploader au moins une photo',
            'photos.*.required' => 'Chaque photo est requise',
            'photos.*.image' => 'Le fichier doit être une image',
            'photos.*.mimes' => 'Formats acceptés: JPG, JPEG, PNG',
            'photos.*.max' => 'La taille maximale est de 50MB',
            'title.required' => 'Le titre est requis',
            'title.min' => 'Le titre doit contenir au moins 5 caractères',
            'title.max' => 'Le titre ne peut pas dépasser 200 caractères',
            'category_id.required' => 'La catégorie est requise',
            'category_id.exists' => 'Cette catégorie n\'existe pas',
            'tags.required' => 'Les tags sont requis',
            'price_standard.required' => 'Le prix standard est requis',
            'price_standard.min' => 'Le prix standard minimum est de 500 FCFA',
            'price_extended.required' => 'Le prix extended est requis',
            'price_extended.gte' => 'Le prix extended doit être supérieur ou égal au prix standard',
        ];
    }
}
