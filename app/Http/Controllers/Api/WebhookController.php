<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function __construct(
        private PaymentService $paymentService
    ) {}

    public function handleCinetPayWebhook(Request $request)
    {
        Log::info('CinetPay Webhook received', $request->all());

        // Récupérer les données du webhook
        $token = $request->input('cpm_trans_id');
        $transactionId = $request->input('cpm_custom'); // order_number
        $amount = $request->input('cpm_amount');
        $status = $request->input('cpm_result'); // '00' = success
        $signature = $request->input('signature');

        // Vérifier la signature du webhook pour sécurité
        $apiKey = config('services.cinetpay.api_key');
        $siteId = config('services.cinetpay.site_id');

        // Calculer la signature attendue
        $expectedSignature = hash('sha256', $siteId . $transactionId . $apiKey);

        if ($signature !== $expectedSignature) {
            Log::warning('CinetPay webhook signature mismatch', [
                'expected' => $expectedSignature,
                'received' => $signature,
            ]);
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        // Trouver la commande
        $order = Order::where('order_number', $transactionId)->first();

        if (!$order) {
            Log::error('CinetPay webhook: Order not found', ['transaction_id' => $transactionId]);
            return response()->json(['error' => 'Order not found'], 404);
        }

        // Traiter selon le statut
        if ($status === '00' && $order->isPending()) {
            // Paiement réussi
            $order->update([
                'payment_status' => 'completed',
                'payment_id' => $token,
                'cinetpay_transaction_id' => $token,
                'payment_provider' => $request->input('payment_method'),
                'paid_at' => now(),
            ]);

            // Compléter la commande
            $this->paymentService->completeOrder($order, $token);

            Log::info('CinetPay payment completed', [
                'order_id' => $order->id,
                'transaction_id' => $token,
            ]);

        } elseif ($status !== '00') {
            // Paiement échoué
            $order->markAsFailed();

            Log::warning('CinetPay payment failed', [
                'order_id' => $order->id,
                'status' => $status,
            ]);
        }

        return response()->json(['status' => 'success']);
    }

    public function handleCinetPayReturn(Request $request, string $orderId)
    {
        // Page de retour après paiement
        $order = Order::findOrFail($orderId);

        // Vérifier le statut du paiement auprès de CinetPay
        $result = $this->paymentService->checkPaymentStatus($order);

        $frontendUrl = config('app.frontend_url', config('app.url'));

        if ($result['success'] && ($result['status'] === 'ACCEPTED' || $result['status'] === '00')) {
            return redirect()->away($frontendUrl . '/orders/' . $order->id . '/success');
        }

        return redirect()->away($frontendUrl . '/orders/' . $order->id . '/failed');
    }
}
