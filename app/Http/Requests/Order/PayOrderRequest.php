<?php

namespace App\Http\Requests\Order;

use Illuminate\Foundation\Http\FormRequest;

class PayOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        $order = $this->route('order');
        return $this->user() && $this->user()->id === $order->user_id;
    }

    public function rules(): array
    {
        $rules = [
            'payment_method' => ['required', 'in:mobile_money,card'],
        ];

        if ($this->input('payment_method') === 'mobile_money') {
            $rules['payment_provider'] = ['nullable', 'string', 'in:ORANGE,MTN,MOOV,WAVE'];
            $rules['phone'] = ['nullable', 'string', 'regex:/^\+226\s?\d{2}\s?\d{2}\s?\d{2}\s?\d{2}$/'];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'payment_method.required' => 'La méthode de paiement est requise',
            'payment_provider.in' => 'Le fournisseur Mobile Money n\'est pas supporté',
            'phone.regex' => 'Le format du téléphone est invalide (ex: +226 70 12 34 56)',
        ];
    }
}
