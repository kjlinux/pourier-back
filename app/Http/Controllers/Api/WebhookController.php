<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use OpenApi\Annotations as OA;

class WebhookController extends Controller
{
    public function __construct(
        private PaymentService $paymentService
    ) {}

    /**
     * @OA\Post(
     *     path="/api/webhooks/cinetpay",
     *     tags={"Webhooks"},
     *     summary="CinetPay payment webhook (callback)",
     *     description="Receives payment notifications from CinetPay when payment status changes. Uses HMAC signature verification for security. This endpoint is called by CinetPay servers only.",
     *     @OA\RequestBody(
     *         required=true,
     *         description="CinetPay webhook payload",
     *         @OA\JsonContent(
     *             @OA\Property(property="cpm_trans_id", type="string", example="CP123456789", description="CinetPay transaction ID"),
     *             @OA\Property(property="cpm_custom", type="string", example="ORD-123456", description="Order number (custom field)"),
     *             @OA\Property(property="cpm_amount", type="number", format="float", example=15000, description="Payment amount in FCFA"),
     *             @OA\Property(property="cpm_result", type="string", example="00", description="Payment result code ('00' = success)"),
     *             @OA\Property(property="signature", type="string", example="abc123def456...", description="HMAC signature = sha256(site_id + order_number + api_key)"),
     *             @OA\Property(property="payment_method", type="string", example="FLOOZ", description="Payment provider used")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Webhook processed successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid signature",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Invalid signature")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Order not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Order not found")
     *         )
     *     )
     * )
     */
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

    /**
     * @OA\Get(
     *     path="/api/webhooks/cinetpay/return/{order}",
     *     tags={"Webhooks"},
     *     summary="CinetPay payment return URL",
     *     description="User is redirected here after completing payment on CinetPay. Checks payment status and redirects to frontend success/failure page.",
     *     @OA\Parameter(
     *         name="order",
     *         in="path",
     *         description="Order UUID",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid", example="9d445a1c-85c5-4b6d-9c38-99a4915d6dac")
     *     ),
     *     @OA\Response(
     *         response=302,
     *         description="Redirect to frontend success or failure page",
     *         @OA\Header(
     *             header="Location",
     *             description="Frontend URL",
     *             @OA\Schema(
     *                 type="string",
     *                 example="https://pourier.bf/orders/9d445a1c-85c5-4b6d-9c38-99a4915d6dac/success"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Order not found",
     *         ref="#/components/responses/NotFoundResponse"
     *     )
     * )
     */
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
