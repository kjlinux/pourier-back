<?php

namespace App\Http\Requests\Order;

use Illuminate\Foundation\Http\FormRequest;

class ValidateOtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        $order = $this->route('order');

        return $this->user() && $this->user()->id === $order->user_id;
    }

    public function rules(): array
    {
        return [
            'otp' => [
                'required',
                'string',
                'digits:6',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'otp.required' => 'Le code OTP est requis',
            'otp.digits' => 'Le code OTP doit contenir exactement 6 chiffres',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $order = $this->route('order');

            // Check if OTP was requested
            if (! $order->payment_phone) {
                $validator->errors()->add(
                    'otp',
                    'Aucune demande OTP n\'a été faite pour cette commande'
                );

                return;
            }

            // Check if OTP expired
            if ($order->isOtpExpired()) {
                $validator->errors()->add(
                    'otp',
                    'Le code OTP a expiré. Veuillez demander un nouveau code.'
                );
            }

            // Check if order is still pending
            if (! $order->isPending()) {
                $validator->errors()->add(
                    'order',
                    'Cette commande a déjà été traitée'
                );
            }
        });
    }
}
