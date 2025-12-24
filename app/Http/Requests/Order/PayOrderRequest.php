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
            // UEMOA: Burkina(226), Côte d'Ivoire(225), Mali(223), Sénégal(221), Togo(228), Bénin(229), Niger(227), Guinée-Bissau(245)
            $rules['phone'] = ['nullable', 'string', 'regex:/^(\+|00)?(221|223|225|226|227|228|229|245)[0-9]{8,10}$/'];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'payment_method.required' => 'La méthode de paiement est requise',
            'payment_provider.in' => 'Le fournisseur Mobile Money n\'est pas supporté',
            'phone.regex' => 'Le format du téléphone est invalide. Utilisez un numéro UEMOA (ex: +226 70 12 34 56, +225 07 12 34 56 78)',
        ];
    }
}
