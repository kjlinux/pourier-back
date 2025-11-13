<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Order\CreateOrderRequest;
use App\Http\Requests\Order\PayOrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Photo;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    private const COMMISSION_RATE = 0.20; // 20%

    public function __construct(
        private PaymentService $paymentService
    ) {
        $this->middleware('auth:api');
    }

    public function index(Request $request)
    {
        $orders = Order::query()
            ->where('user_id', $request->user()->id)
            ->with('items.photo')
            ->latest()
            ->paginate($request->get('per_page', 20));

        return OrderResource::collection($orders);
    }

    public function store(CreateOrderRequest $request)
    {
        try {
            $order = DB::transaction(function () use ($request) {
                // Créer la commande
                $order = Order::create([
                    'user_id' => $request->user()->id,
                    'subtotal' => $request->subtotal,
                    'tax' => $request->tax ?? 0,
                    'discount' => $request->discount ?? 0,
                    'total' => $request->total,
                    'payment_method' => $request->payment_method,
                    'payment_status' => 'pending',
                    'billing_email' => $request->billing_email,
                    'billing_first_name' => $request->billing_first_name,
                    'billing_last_name' => $request->billing_last_name,
                    'billing_phone' => $request->billing_phone,
                ]);

                // Créer les items
                foreach ($request->items as $item) {
                    $photo = Photo::findOrFail($item['photo_id']);

                    // Vérifier disponibilité
                    if (!$photo->is_public || $photo->status !== 'approved') {
                        throw new \Exception("La photo '{$photo->title}' n'est plus disponible");
                    }

                    $price = $item['license_type'] === 'standard'
                        ? $photo->price_standard
                        : $photo->price_extended;

                    // Calculer les commissions
                    $platformCommission = (int) round($price * self::COMMISSION_RATE);
                    $photographerAmount = $price - $platformCommission;

                    OrderItem::create([
                        'order_id' => $order->id,
                        'photo_id' => $photo->id,
                        'photo_title' => $photo->title,
                        'photo_thumbnail' => $photo->thumbnail_url,
                        'photographer_id' => $photo->photographer_id,
                        'photographer_name' => $photo->photographer->first_name . ' ' . $photo->photographer->last_name,
                        'license_type' => $item['license_type'],
                        'price' => $price,
                        'photographer_amount' => $photographerAmount,
                        'platform_commission' => $platformCommission,
                    ]);
                }

                return $order;
            });

            return response()->json([
                'success' => true,
                'message' => 'Commande créée avec succès',
                'data' => new OrderResource($order->load('items')),
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création de la commande: ' . $e->getMessage(),
            ], 400);
        }
    }

    public function show(Order $order, Request $request)
    {
        // Vérifier que l'utilisateur est propriétaire
        if ($order->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé',
            ], 403);
        }

        $order->load('items.photo');

        return new OrderResource($order);
    }

    public function pay(PayOrderRequest $request, Order $order)
    {
        // Vérifier que la commande est en attente
        if (!$order->isPending()) {
            return response()->json([
                'success' => false,
                'message' => 'Cette commande a déjà été traitée',
            ], 400);
        }

        // Initier le paiement via CinetPay
        $result = $this->paymentService->processPayment(
            $order,
            $request->payment_method,
            $request->payment_provider,
            $request->phone
        );

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'data' => [
                    'payment_url' => $result['payment_url'],
                    'payment_token' => $result['payment_token'],
                ],
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => $result['message'],
        ], 400);
    }

    public function checkStatus(Order $order, Request $request)
    {
        // Vérifier ownership
        if ($order->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé',
            ], 403);
        }

        $result = $this->paymentService->checkPaymentStatus($order);

        return response()->json($result);
    }
}
