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
            // Initialiser le paiement via CinetPay
            $cinetpayData = [
                'apikey' => config('services.cinetpay.api_key'),
                'site_id' => config('services.cinetpay.site_id'),
                'transaction_id' => $order->order_number,
                'amount' => $order->total, // en FCFA (integer)
                'currency' => 'XOF',
                'description' => 'Achat photos Pouire - Commande ' . $order->order_number,
                'notify_url' => config('services.cinetpay.notify_url'),
                'return_url' => config('services.cinetpay.return_url') . '/' . $order->id,
                'channels' => $this->getCinetPayChannels($paymentMethod, $paymentProvider),
                'metadata' => json_encode([
                    'order_id' => $order->id,
                    'user_id' => $order->user_id,
                ]),
            ];

            // Ajouter le numéro de téléphone si fourni (pour Mobile Money)
            if ($phone) {
                $cinetpayData['customer_phone_number'] = $phone;
            }

            $response = Http::post(config('services.cinetpay.api_url') . '/payment', $cinetpayData);

            if ($response->successful() && $response->json('code') === '201') {
                $data = $response->json('data');

                // Mettre à jour la commande avec l'ID de transaction CinetPay
                $order->update([
                    'cinetpay_transaction_id' => $data['payment_token'],
                    'payment_provider' => $paymentProvider,
                ]);

                return [
                    'success' => true,
                    'message' => 'Paiement initialisé avec succès',
                    'payment_url' => $data['payment_url'],
                    'payment_token' => $data['payment_token'],
                ];
            }

            $order->markAsFailed();

            return [
                'success' => false,
                'message' => $response->json('message', 'Échec de l\'initialisation du paiement'),
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

    private function getCinetPayChannels(string $paymentMethod, ?string $provider = null): string
    {
        // Déterminer les canaux CinetPay selon la méthode de paiement
        if ($paymentMethod === 'mobile_money') {
            // Si un provider spécifique est demandé
            if ($provider) {
                return match ($provider) {
                    'ORANGE' => 'ORANGE_MONEY_BF',
                    'MTN' => 'MTN_MONEY_BF',
                    'MOOV' => 'MOOV_MONEY_BF',
                    'WAVE' => 'WAVE_BF',
                    default => 'ALL', // Tous les Mobile Money si non reconnu
                };
            }
            return 'ALL'; // Tous les Mobile Money
        }

        if ($paymentMethod === 'card') {
            return 'CARD'; // Paiement par carte
        }

        return 'ALL'; // Par défaut, tous les moyens de paiement
    }

    public function checkPaymentStatus(Order $order): array
    {
        try {
            $response = Http::post(config('services.cinetpay.api_url') . '/check', [
                'apikey' => config('services.cinetpay.api_key'),
                'site_id' => config('services.cinetpay.site_id'),
                'transaction_id' => $order->order_number,
            ]);

            if ($response->successful()) {
                $data = $response->json('data');

                return [
                    'success' => true,
                    'status' => $data['status'] ?? 'UNKNOWN',
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
