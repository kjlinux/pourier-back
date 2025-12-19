<?php

namespace App\Http\Requests\Order;

use App\Services\LigdicashOtpService;
use Illuminate\Foundation\Http\FormRequest;

class RequestOtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        $order = $this->route('order');

        return $this->user() && $this->user()->id === $order->user_id;
    }

    public function rules(): array
    {
        return [
            'phone' => [
                'required',
                'string',
                'regex:/^\+226\s?\d{2}\s?\d{2}\s?\d{2}\s?\d{2}$/',
            ],
            'payment_provider' => [
                'required',
                'string',
                'in:ORANGE,LIGDICASH_WALLET',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.required' => 'Le numéro de téléphone est requis',
            'phone.regex' => 'Le format du numéro est invalide (ex: +226 70 12 34 56)',
            'payment_provider.required' => 'Le fournisseur de paiement est requis',
            'payment_provider.in' => 'Ce fournisseur ne supporte pas le paiement par OTP',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $order = $this->route('order');

            // Check if order can request OTP
            if (! $order->canRequestOtp()) {
                $maxAttempts = config('services.ligdicash.max_otp_requests', 3);
                if ($order->otp_request_count >= $maxAttempts) {
                    $validator->errors()->add(
                        'otp_limit',
                        "Nombre maximum de tentatives ({$maxAttempts}) atteint."
                    );
                }
            }

            // Check if provider supports OTP
            $provider = $this->input('payment_provider');
            if ($provider && ! LigdicashOtpService::supportsOtp($provider)) {
                $validator->errors()->add(
                    'payment_provider',
                    'Ce fournisseur ne supporte pas le paiement par OTP'
                );
            }
        });
    }
}
