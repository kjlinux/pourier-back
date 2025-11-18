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
use OpenApi\Annotations as OA;

class OrderController extends Controller
{
    private const COMMISSION_RATE = 0.20; // 20%

    public function __construct(
        private PaymentService $paymentService
    ) {
        $this->middleware('auth:api');
    }

    /**
     * @OA\Get(
     *     path="/api/orders",
     *     operationId="getOrders",
     *     tags={"Orders"},
     *     summary="List user's orders",
     *     description="Get all orders for the authenticated user with pagination",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Number of orders per page",
     *         required=false,
     *         @OA\Schema(type="integer", default=20, example=20)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Orders retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Order")),
     *             @OA\Property(property="meta", ref="#/components/schemas/PaginationMeta"),
     *             @OA\Property(property="links", ref="#/components/schemas/PaginationLinks")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         ref="#/components/responses/UnauthorizedResponse"
     *     )
     * )
     */
    public function index(Request $request)
    {
        $orders = Order::query()
            ->where('user_id', $request->user()->id)
            ->with('items.photo')
            ->latest()
            ->paginate($request->get('per_page', 20));

        return OrderResource::collection($orders);
    }

    /**
     * @OA\Post(
     *     path="/api/orders",
     *     operationId="storeOrders",
     *     tags={"Orders"},
     *     summary="Create new order",
     *     description="Create a new order with items from cart. Platform takes 20% commission.",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"items", "subtotal", "total", "payment_method", "billing_email", "billing_first_name", "billing_last_name", "billing_phone"},
     *             @OA\Property(
     *                 property="items",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="photo_id", type="string", format="uuid", example="9d445a1c-85c5-4b6d-9c38-99a4915d6dac"),
     *                     @OA\Property(property="license_type", type="string", enum={"standard", "extended"}, example="standard")
     *                 ),
     *                 example={{"photo_id": "9d445a1c-85c5-4b6d-9c38-99a4915d6dac", "license_type": "standard"}}
     *             ),
     *             @OA\Property(property="subtotal", type="number", format="float", example=15000, description="Sum of all item prices in FCFA"),
     *             @OA\Property(property="tax", type="number", format="float", example=0, description="Tax amount (currently 0)"),
     *             @OA\Property(property="discount", type="number", format="float", example=0, description="Discount amount"),
     *             @OA\Property(property="total", type="number", format="float", example=15000, description="Total amount in FCFA"),
     *             @OA\Property(property="payment_method", type="string", enum={"mobile_money", "card"}, example="mobile_money"),
     *             @OA\Property(property="billing_email", type="string", format="email", example="user@example.com"),
     *             @OA\Property(property="billing_first_name", type="string", example="Jean"),
     *             @OA\Property(property="billing_last_name", type="string", example="Dupont"),
     *             @OA\Property(property="billing_phone", type="string", example="+226 70 12 34 56", description="Burkinabé phone format")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Order created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Commande créée avec succès"),
     *             @OA\Property(property="data", ref="#/components/schemas/Order")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Order creation failed - photo unavailable or validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Erreur lors de la création de la commande: La photo 'xyz' n'est plus disponible")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         ref="#/components/responses/UnauthorizedResponse"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         ref="#/components/responses/ValidationErrorResponse"
     *     )
     * )
     */
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

    /**
     * @OA\Get(
     *     path="/api/orders/{order}",
     *     operationId="getOrder",
     *     tags={"Orders"},
     *     summary="Get order details",
     *     description="Retrieve detailed information about a specific order (must be order owner)",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="order",
     *         in="path",
     *         description="Order UUID",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid", example="9d445a1c-85c5-4b6d-9c38-99a4915d6dac")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Order details retrieved successfully",
     *         @OA\JsonContent(ref="#/components/schemas/Order")
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - user is not order owner",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Accès non autorisé")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Order not found",
     *         ref="#/components/responses/NotFoundResponse"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         ref="#/components/responses/UnauthorizedResponse"
     *     )
     * )
     */
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

    /**
     * @OA\Post(
     *     path="/api/orders/{order}/pay",
     *     operationId="payOrders",
     *     tags={"Orders"},
     *     summary="Initiate payment for order",
     *     description="Start payment process via CinetPay (Mobile Money or Card). Returns payment URL to redirect user.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="order",
     *         in="path",
     *         description="Order UUID",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid", example="9d445a1c-85c5-4b6d-9c38-99a4915d6dac")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"payment_method", "payment_provider"},
     *             @OA\Property(property="payment_method", type="string", enum={"mobile_money", "card"}, example="mobile_money", description="Payment method type"),
     *             @OA\Property(property="payment_provider", type="string", enum={"FLOOZ", "TMONEY", "MOOV", "CARD"}, example="FLOOZ", description="CinetPay payment provider code"),
     *             @OA\Property(property="phone", type="string", example="+22670123456", description="Phone number for mobile money (required for mobile_money)")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Payment initiated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Paiement initié avec succès"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="payment_url", type="string", format="url", example="https://client.cinetpay.com/payment/xyz123", description="URL to redirect user for payment"),
     *                 @OA\Property(property="payment_token", type="string", example="xyz123abc456", description="CinetPay payment token")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Payment initiation failed - order already processed or payment error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Cette commande a déjà été traitée")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         ref="#/components/responses/UnauthorizedResponse"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         ref="#/components/responses/ValidationErrorResponse"
     *     )
     * )
     */
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

    /**
     * @OA\Get(
     *     path="/api/orders/{order}/status",
     *     operationId="getOrdersStatus",
     *     tags={"Orders"},
     *     summary="Check order payment status",
     *     description="Check the current payment status of an order with CinetPay (must be order owner)",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="order",
     *         in="path",
     *         description="Order UUID",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid", example="9d445a1c-85c5-4b6d-9c38-99a4915d6dac")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Payment status retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Statut de paiement récupéré"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="payment_status", type="string", enum={"pending", "completed", "failed", "cancelled"}, example="completed"),
     *                 @OA\Property(property="order_status", type="string", example="completed"),
     *                 @OA\Property(property="transaction_id", type="string", example="CP123456789")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - user is not order owner",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Accès non autorisé")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         ref="#/components/responses/UnauthorizedResponse"
     *     )
     * )
     */
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
