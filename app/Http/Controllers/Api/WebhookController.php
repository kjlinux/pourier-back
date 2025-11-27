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
     *     path="/api/webhooks/ligdicash",
     *     operationId="storeWebhooksLigdicash",
     *     tags={"Webhooks"},
     *     summary="Ligdicash payment webhook (callback)",
     *     description="Receives payment notifications from Ligdicash when payment status changes. This endpoint is called by Ligdicash servers only. Ligdicash sends two POST requests (application/x-www-form-urlencoded and application/json) with the same data.",
     *     @OA\RequestBody(
     *         required=true,
     *         description="Ligdicash webhook payload",
     *         @OA\JsonContent(
     *             @OA\Property(property="response_code", type="string", example="00", description="Response code ('00' = success, '01' = error)"),
     *             @OA\Property(property="token", type="string", example="eyJhbGci...", description="Transaction token"),
     *             @OA\Property(property="status", type="string", example="completed", description="Transaction status (completed, pending, notcompleted)"),
     *             @OA\Property(property="amount", type="integer", example=15000, description="Payment amount in FCFA"),
     *             @OA\Property(property="operator_name", type="string", example="Orange Money", description="Payment operator name"),
     *             @OA\Property(property="custom_data", type="object", description="Custom data sent in initial request",
     *                 @OA\Property(property="order_id", type="string", example="9d445a1c-85c5-4b6d-9c38-99a4915d6dac"),
     *                 @OA\Property(property="order_number", type="string", example="ORD-20231127-ABC123"),
     *                 @OA\Property(property="user_id", type="string", example="9d445a1c-85c5-4b6d-9c38-99a4915d6dac")
     *             )
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
     *         response=404,
     *         description="Order not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Order not found")
     *         )
     *     )
     * )
     */
    public function handleLigdicashWebhook(Request $request)
    {
        Log::info('Ligdicash Webhook received', $request->all());

        // Récupérer les données du webhook
        $responseCode = $request->input('response_code');
        $token = $request->input('token');
        $status = $request->input('status'); // completed, pending, notcompleted
        $amount = $request->input('amount');
        $operatorName = $request->input('operator_name');
        $customData = $request->input('custom_data', []);

        // Extraire l'order_id depuis custom_data
        $orderId = $customData['order_id'] ?? null;

        if (!$orderId) {
            Log::error('Ligdicash webhook: Order ID not found in custom_data');
            return response()->json(['error' => 'Order ID not found'], 400);
        }

        // Trouver la commande
        $order = Order::find($orderId);

        if (!$order) {
            Log::error('Ligdicash webhook: Order not found', ['order_id' => $orderId]);
            return response()->json(['error' => 'Order not found'], 404);
        }

        // Vérifier si la commande n'a pas déjà été traitée (éviter la duplication)
        if ($order->isCompleted()) {
            Log::info('Ligdicash webhook: Order already completed', ['order_id' => $orderId]);
            return response()->json(['status' => 'success', 'message' => 'Already processed']);
        }

        // Traiter selon le statut
        if ($responseCode === '00' && $status === 'completed' && $order->isPending()) {
            // Paiement réussi
            $order->update([
                'payment_status' => 'completed',
                'payment_id' => $token,
                'ligdicash_token' => $token,
                'payment_provider' => $operatorName,
                'paid_at' => now(),
            ]);

            // Compléter la commande
            $this->paymentService->completeOrder($order, $token);

            Log::info('Ligdicash payment completed', [
                'order_id' => $order->id,
                'token' => $token,
                'operator' => $operatorName,
            ]);
        } elseif ($responseCode !== '00' || $status === 'notcompleted') {
            // Paiement échoué
            $order->markAsFailed();

            Log::warning('Ligdicash payment failed', [
                'order_id' => $order->id,
                'response_code' => $responseCode,
                'status' => $status,
            ]);
        }

        return response()->json(['status' => 'success']);
    }

    /**
     * @OA\Get(
     *     path="/api/webhooks/ligdicash/return/{order}",
     *     operationId="getWebhooksLigdicashReturn",
     *     tags={"Webhooks"},
     *     summary="Ligdicash payment return URL",
     *     description="User is redirected here after completing payment on Ligdicash. Checks payment status and redirects to frontend success/failure page.",
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
     *                 example="https://pouire.bf/orders/9d445a1c-85c5-4b6d-9c38-99a4915d6dac/success"
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
    public function handleLigdicashReturn(Request $request, string $orderId)
    {
        // Page de retour après paiement
        $order = Order::findOrFail($orderId);

        // Vérifier le statut du paiement auprès de Ligdicash
        $result = $this->paymentService->checkPaymentStatus($order);

        $frontendUrl = config('app.frontend_url', config('app.url'));

        if ($result['success'] && isset($result['is_paid']) && $result['is_paid']) {
            return redirect()->away($frontendUrl . '/orders/' . $order->id . '/success');
        }

        return redirect()->away($frontendUrl . '/orders/' . $order->id . '/failed');
    }

    /**
     * @OA\Get(
     *     path="/api/webhooks/ligdicash/cancel/{order}",
     *     operationId="getWebhooksLigdicashCancel",
     *     tags={"Webhooks"},
     *     summary="Ligdicash payment cancel URL",
     *     description="User is redirected here when cancelling payment on Ligdicash.",
     *     @OA\Parameter(
     *         name="order",
     *         in="path",
     *         description="Order UUID",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid", example="9d445a1c-85c5-4b6d-9c38-99a4915d6dac")
     *     ),
     *     @OA\Response(
     *         response=302,
     *         description="Redirect to frontend cancelled page",
     *         @OA\Header(
     *             header="Location",
     *             description="Frontend URL",
     *             @OA\Schema(
     *                 type="string",
     *                 example="https://pouire.bf/orders/9d445a1c-85c5-4b6d-9c38-99a4915d6dac/cancelled"
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
    public function handleLigdicashCancel(Request $request, string $orderId)
    {
        // Page d'annulation après paiement
        $order = Order::findOrFail($orderId);

        // Marquer la commande comme échouée si elle est toujours en attente
        if ($order->isPending()) {
            $order->markAsFailed();
            Log::info('Ligdicash payment cancelled by user', ['order_id' => $order->id]);
        }

        $frontendUrl = config('app.frontend_url', config('app.url'));

        return redirect()->away($frontendUrl . '/orders/' . $order->id . '/cancelled');
    }
}
