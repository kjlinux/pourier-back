<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaymentService
{
    private const COMMISSION_RATE = 0.20; // 20%

    public function processPayment(Order $order, string $paymentMethod, ?string $paymentProvider = null, ?string $phone = null): array
    {
        try {
            // Préparer les items pour la facture Ligdicash
            $invoiceItems = [];
            foreach ($order->items as $item) {
                $invoiceItems[] = [
                    'name' => $item->photo_title,
                    'description' => 'Photo par ' . $item->photographer_name . ' - Licence: ' . $item->license_type,
                    'quantity' => 1,
                    'unit_price' => $item->price,
                    'total_price' => $item->price,
                ];
            }

            // Initialiser le paiement via Ligdicash
            $ligdicashData = [
                'commande' => [
                    'invoice' => [
                        'items' => $invoiceItems,
                        'total_amount' => $order->total,
                        'devise' => 'XOF',
                        'description' => 'Achat photos Pouire - Commande ' . $order->order_number,
                        'customer' => '',
                        'customer_firstname' => $order->billing_first_name,
                        'customer_lastname' => $order->billing_last_name,
                        'customer_email' => $order->billing_email,
                        'external_id' => '',
                        'otp' => '',
                    ],
                    'store' => [
                        'name' => config('services.ligdicash.store_name'),
                        'website_url' => config('services.ligdicash.store_website'),
                    ],
                    'actions' => [
                        'cancel_url' => config('services.ligdicash.cancel_url') . '/' . $order->id,
                        'return_url' => config('services.ligdicash.return_url') . '/' . $order->id,
                        'callback_url' => config('services.ligdicash.callback_url'),
                    ],
                    'custom_data' => [
                        'order_id' => $order->id,
                        'order_number' => $order->order_number,
                        'user_id' => $order->user_id,
                    ],
                ],
            ];

            $response = Http::withHeaders([
                'Apikey' => config('services.ligdicash.api_key'),
                'Authorization' => 'Bearer ' . config('services.ligdicash.auth_token'),
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])->post(config('services.ligdicash.api_url') . '/create', $ligdicashData);

            if ($response->successful() && $response->json('response_code') === '00') {
                $token = $response->json('token');
                $paymentUrl = $response->json('response_text');

                // Mettre à jour la commande avec le token Ligdicash
                $order->update([
                    'ligdicash_token' => $token,
                    'payment_provider' => $paymentProvider,
                ]);

                return [
                    'success' => true,
                    'message' => 'Paiement initialisé avec succès',
                    'payment_url' => $paymentUrl,
                    'payment_token' => $token,
                ];
            }

            $order->markAsFailed();

            return [
                'success' => false,
                'message' => $response->json('response_text', 'Échec de l\'initialisation du paiement'),
            ];
        } catch (\Exception $e) {
            Log::error('Erreur processPayment: ' . $e->getMessage());
            $order->markAsFailed();

            return [
                'success' => false,
                'message' => 'Erreur lors du traitement du paiement: ' . $e->getMessage(),
            ];
        }
    }

    public function checkPaymentStatus(Order $order): array
    {
        try {
            if (!$order->ligdicash_token) {
                return [
                    'success' => false,
                    'message' => 'Token de paiement introuvable',
                ];
            }

            $response = Http::withHeaders([
                'Apikey' => config('services.ligdicash.api_key'),
                'Authorization' => 'Bearer ' . config('services.ligdicash.auth_token'),
                'Accept' => 'application/json',
            ])->get(config('services.ligdicash.api_url') . '/confirm/', [
                'invoiceToken' => $order->ligdicash_token,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $responseCode = $data['response_code'] ?? null;
                $status = $data['status'] ?? 'UNKNOWN';

                // Ligdicash: response_code = "00" et status = "completed" = paiement réussi
                $isPaid = ($responseCode === '00' && $status === 'completed');

                return [
                    'success' => true,
                    'status' => $status,
                    'is_paid' => $isPaid,
                    'data' => $data,
                ];
            }

            return [
                'success' => false,
                'message' => 'Impossible de vérifier le statut du paiement',
            ];
        } catch (\Exception $e) {
            Log::error('Erreur checkPaymentStatus: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage(),
            ];
        }
    }

    public function completeOrder(Order $order, string $transactionId): void
    {
        DB::transaction(function () use ($order, $transactionId) {
            // Marquer la commande comme complétée
            $order->markAsCompleted($transactionId);

            // Générer les URLs de téléchargement
            foreach ($order->items as $item) {
                $item->generateDownloadUrl();
            }

            // Mettre à jour les statistiques des photos
            foreach ($order->items as $item) {
                $item->photo->incrementSales();
                $item->photo->incrementDownloads();
            }

            Log::info('Order completed', ['order_id' => $order->id, 'transaction_id' => $transactionId]);
        });
    }
}
