<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LigdicashOtpService
{
    public function __construct(
        private PaymentService $paymentService
    ) {}

    /**
     * Request OTP code for payment
     */
    public function requestOtp(Order $order, string $phone, string $provider): array
    {
        // Validate OTP eligibility
        if (! $this->canRequestOtp($order)) {
            return [
                'success' => false,
                'message' => $this->getOtpErrorMessage($order),
            ];
        }

        // Normalize phone number (remove spaces, country code)
        $normalizedPhone = $this->normalizePhone($phone);

        try {
            // GET https://app.ligdicash.com/pay/v02/debitotp/{phone}/{amount}
            $response = Http::withHeaders([
                'Apikey' => config('services.ligdicash.api_key'),
                'Authorization' => 'Bearer '.config('services.ligdicash.auth_token'),
                'Accept' => 'application/json',
            ])->get(config('services.ligdicash.otp_api_url')."/debitotp/{$normalizedPhone}/{$order->total}");

            if ($response->successful() && $response->json('error') === false) {
                // Update order with OTP tracking
                $expiryMinutes = config('services.ligdicash.otp_expiry_minutes', 5);
                $order->update([
                    'payment_phone' => $phone,
                    'payment_provider' => $provider,
                    'payment_flow' => 'otp',
                    'otp_requested_at' => now(),
                    'otp_expires_at' => now()->addMinutes($expiryMinutes),
                ]);
                $order->incrementOtpRequestCount();

                Log::info('OTP requested successfully', [
                    'order_id' => $order->id,
                    'phone' => $this->maskPhone($phone),
                    'provider' => $provider,
                ]);

                return [
                    'success' => true,
                    'message' => 'Code OTP envoyé avec succès. Vérifiez votre téléphone.',
                    'data' => [
                        'expires_at' => $order->otp_expires_at->toISOString(),
                        'attempts_remaining' => config('services.ligdicash.max_otp_requests') - $order->otp_request_count,
                    ],
                ];
            }

            $errorMessage = $response->json('message', 'Impossible d\'envoyer le code OTP');
            Log::error('OTP request failed', [
                'order_id' => $order->id,
                'response' => $response->json(),
            ]);

            return [
                'success' => false,
                'message' => $errorMessage,
            ];

        } catch (\Exception $e) {
            Log::error('OTP request exception', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Erreur lors de l\'envoi du code OTP: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Validate OTP and process payment
     */
    public function validateOtpAndPay(Order $order, string $otp): array
    {
        // Validate OTP state
        if (! $this->canValidateOtp($order)) {
            return [
                'success' => false,
                'message' => $this->getOtpValidationErrorMessage($order),
            ];
        }

        try {
            // Build invoice using shared logic
            $invoiceData = $this->buildInvoiceData($order, $otp);

            // POST https://app.ligdicash.com/pay/v02/debitwallet/withotp
            $response = Http::withHeaders([
                'Apikey' => config('services.ligdicash.api_key'),
                'Authorization' => 'Bearer '.config('services.ligdicash.auth_token'),
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])->post(config('services.ligdicash.otp_api_url').'/debitwallet/withotp', $invoiceData);

            if ($response->successful() && $response->json('response_code') === '00') {
                $token = $response->json('token');

                // Update order with token
                $order->update([
                    'ligdicash_token' => $token,
                    'payment_status' => 'processing',
                ]);

                // Verify transaction status
                $verificationResult = $this->verifyTransaction($order);

                if ($verificationResult['success'] && $verificationResult['is_paid']) {
                    // Complete order
                    $this->paymentService->completeOrder($order, $token);

                    Log::info('OTP payment completed', [
                        'order_id' => $order->id,
                        'token' => $token,
                    ]);

                    return [
                        'success' => true,
                        'message' => 'Paiement effectué avec succès',
                        'data' => [
                            'order_id' => $order->id,
                            'transaction_id' => $token,
                        ],
                    ];
                }

                // Verification failed
                $order->markAsFailed();

                return [
                    'success' => false,
                    'message' => 'La vérification du paiement a échoué',
                ];
            }

            // Payment failed
            $errorMessage = $response->json('response_text', 'Code OTP invalide ou paiement refusé');
            Log::warning('OTP validation failed', [
                'order_id' => $order->id,
                'response_code' => $response->json('response_code'),
                'message' => $errorMessage,
            ]);

            return [
                'success' => false,
                'message' => $errorMessage,
            ];

        } catch (\Exception $e) {
            Log::error('OTP validation exception', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Erreur lors de la validation du code OTP: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Verify transaction status with Ligdicash
     */
    private function verifyTransaction(Order $order): array
    {
        // Use existing PaymentService verification logic
        return $this->paymentService->checkPaymentStatus($order);
    }

    /**
     * Build invoice data for OTP payment
     */
    private function buildInvoiceData(Order $order, string $otp): array
    {
        // Prepare invoice items
        $invoiceItems = [];
        foreach ($order->items as $item) {
            $invoiceItems[] = [
                'name' => $item->photo_title,
                'description' => 'Photo par '.$item->photographer_name.' - Licence: '.$item->license_type,
                'quantity' => 1,
                'unit_price' => $item->price,
                'total_price' => $item->price,
            ];
        }

        return [
            'commande' => [
                'invoice' => [
                    'items' => $invoiceItems,
                    'total_amount' => $order->total,
                    'devise' => 'XOF',
                    'description' => 'Achat photos Pouire - Commande '.$order->order_number,
                    'customer' => $this->normalizePhone($order->payment_phone),
                    'customer_firstname' => $order->billing_first_name,
                    'customer_lastname' => $order->billing_last_name,
                    'customer_email' => $order->billing_email,
                    'external_id' => $order->order_number,
                    'otp' => $otp,
                ],
                'store' => [
                    'name' => config('services.ligdicash.store_name'),
                    'website_url' => config('services.ligdicash.store_website'),
                ],
                'actions' => [
                    'cancel_url' => config('services.ligdicash.cancel_url').'/'.$order->id,
                    'return_url' => config('services.ligdicash.return_url').'/'.$order->id,
                    'callback_url' => config('services.ligdicash.callback_url'),
                ],
                'custom_data' => [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'user_id' => $order->user_id,
                    'payment_flow' => 'otp',
                ],
            ],
        ];
    }

    /**
     * Check if order can request OTP
     */
    private function canRequestOtp(Order $order): bool
    {
        return $order->canRequestOtp();
    }

    /**
     * Check if order can validate OTP
     */
    private function canValidateOtp(Order $order): bool
    {
        if (! $order->isPending()) {
            return false;
        }

        if (! $order->payment_phone) {
            return false;
        }

        if ($order->isOtpExpired()) {
            return false;
        }

        return true;
    }

    /**
     * Normalize phone number for API call
     */
    private function normalizePhone(string $phone): string
    {
        // Remove spaces and country code for API
        // +226 70 12 34 56 → 70123456
        return preg_replace('/[\s+]/', '', str_replace('+226', '', $phone));
    }

    /**
     * Mask phone number for logging
     */
    private function maskPhone(string $phone): string
    {
        // +226 70 12 34 56 → 226XX***456
        $normalized = preg_replace('/\s+/', '', $phone);
        if (strlen($normalized) > 6) {
            return substr($normalized, 0, 5).'***'.substr($normalized, -3);
        }

        return '***';
    }

    /**
     * Get error message when OTP request fails
     */
    private function getOtpErrorMessage(Order $order): string
    {
        if (! $order->isPending()) {
            return 'Cette commande a déjà été traitée';
        }

        if ($order->otp_request_count >= config('services.ligdicash.max_otp_requests', 3)) {
            return 'Nombre maximum de tentatives atteint. Veuillez créer une nouvelle commande.';
        }

        return 'Impossible de demander un code OTP pour cette commande';
    }

    /**
     * Get error message when OTP validation fails
     */
    private function getOtpValidationErrorMessage(Order $order): string
    {
        if (! $order->isPending()) {
            return 'Cette commande a déjà été traitée';
        }

        if (! $order->payment_phone) {
            return 'Aucune demande OTP n\'a été faite pour cette commande';
        }

        if ($order->isOtpExpired()) {
            return 'Le code OTP a expiré. Veuillez demander un nouveau code.';
        }

        return 'Impossible de valider le code OTP';
    }

    /**
     * Check if provider supports OTP flow
     */
    public static function supportsOtp(string $provider): bool
    {
        $otpProviders = config('services.ligdicash.otp_providers', ['ORANGE', 'LIGDICASH_WALLET']);

        return in_array(strtoupper($provider), $otpProviders);
    }
}
