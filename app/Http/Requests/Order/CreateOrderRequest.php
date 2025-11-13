<?php

namespace App\Http\Requests\Order;

use Illuminate\Foundation\Http\FormRequest;

class CreateOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1'],
            'items.*.photo_id' => ['required', 'exists:photos,id'],
            'items.*.license_type' => ['required', 'in:standard,extended'],
            'subtotal' => ['required', 'integer', 'min:0'],
            'tax' => ['nullable', 'integer', 'min:0'],
            'discount' => ['nullable', 'integer', 'min:0'],
            'total' => ['required', 'integer', 'min:0'],
            'payment_method' => ['required', 'in:mobile_money,card'],
            'billing_email' => ['required', 'email'],
            'billing_first_name' => ['required', 'string'],
            'billing_last_name' => ['required', 'string'],
            'billing_phone' => ['required', 'string', 'regex:/^\+226\s?\d{2}\s?\d{2}\s?\d{2}\s?\d{2}$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'Votre panier est vide',
            'items.min' => 'Votre panier doit contenir au moins un article',
            'items.*.photo_id.exists' => 'Une des photos n\'existe pas',
            'items.*.license_type.in' => 'Type de licence invalide',
            'billing_email.required' => 'L\'email de facturation est requis',
            'billing_email.email' => 'L\'email de facturation doit être valide',
            'billing_phone.regex' => 'Le format du téléphone est invalide',
        ];
    }
}
