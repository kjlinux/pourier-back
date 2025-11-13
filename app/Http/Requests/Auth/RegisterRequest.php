<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'min:2', 'max:50'],
            'last_name' => ['required', 'string', 'min:2', 'max:50'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => [
                'required',
                'confirmed',
                Password::min(8)
                    ->letters()
                    ->numbers()
                    ->symbols()
                    ->uncompromised(),
            ],
            'account_type' => ['required', 'in:buyer,photographer'],
            'phone' => ['nullable', 'string', 'regex:/^\+226\s?\d{2}\s?\d{2}\s?\d{2}\s?\d{2}$/'],
        ];
    }

    /**
     * Get custom error messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'first_name.required' => 'Le prénom est obligatoire.',
            'first_name.string' => 'Le prénom doit être une chaîne de caractères.',
            'first_name.min' => 'Le prénom doit contenir au moins 2 caractères.',
            'first_name.max' => 'Le prénom ne peut pas dépasser 50 caractères.',

            'last_name.required' => 'Le nom est obligatoire.',
            'last_name.string' => 'Le nom doit être une chaîne de caractères.',
            'last_name.min' => 'Le nom doit contenir au moins 2 caractères.',
            'last_name.max' => 'Le nom ne peut pas dépasser 50 caractères.',

            'email.required' => 'L\'adresse e-mail est obligatoire.',
            'email.email' => 'L\'adresse e-mail doit être valide.',
            'email.max' => 'L\'adresse e-mail ne peut pas dépasser 255 caractères.',
            'email.unique' => 'Cette adresse e-mail est déjà utilisée.',

            'password.required' => 'Le mot de passe est obligatoire.',
            'password.confirmed' => 'La confirmation du mot de passe ne correspond pas.',

            'account_type.required' => 'Le type de compte est obligatoire.',
            'account_type.in' => 'Le type de compte doit être "buyer" ou "photographer".',

            'phone.regex' => 'Le numéro de téléphone doit être au format burkinabè (+226 XX XX XX XX).',
        ];
    }
}
