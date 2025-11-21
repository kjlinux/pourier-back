<?php

namespace App\Http\Controllers\Api\Photographer;

use App\Http\Controllers\Controller;
use App\Models\OrderItem;
use App\Models\Revenue;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Annotations as OA;

class RevenueController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/photographer/revenue",
     *     operationId="getPhotographerRevenue",
     *     tags={"Photographer - Revenue"},
     *     summary="List all revenue records",
     *     description="Get paginated list of all revenue records for the photographer. Each sale generates revenue after 20% platform commission.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Revenue records retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="data",
     *                     type="array",
     *                     @OA\Items(
     *                         @OA\Property(property="id", type="string", format="uuid"),
     *                         @OA\Property(property="photographer_id", type="string", format="uuid"),
     *                         @OA\Property(property="month", type="string", format="date", example="2025-11-01", description="Month of the revenue record"),
     *                         @OA\Property(property="total_sales", type="integer", example=50000, description="Total sales amount in FCFA"),
     *                         @OA\Property(property="commission", type="integer", example=10000, description="Platform commission (20%) in FCFA"),
     *                         @OA\Property(property="net_revenue", type="integer", example=40000, description="Photographer's net revenue (80%) in FCFA"),
     *                         @OA\Property(property="available_balance", type="integer", example=35000, description="Available balance for withdrawal in FCFA"),
     *                         @OA\Property(property="pending_balance", type="integer", example=5000, description="Pending balance in FCFA"),
     *                         @OA\Property(property="withdrawn", type="integer", example=0, description="Amount already withdrawn in FCFA"),
     *                         @OA\Property(property="sales_count", type="integer", example=10, description="Number of sales"),
     *                         @OA\Property(property="photos_sold", type="integer", example=8, description="Number of unique photos sold")
     *                     )
     *                 ),
     *                 @OA\Property(property="current_page", type="integer", example=1),
     *                 @OA\Property(property="per_page", type="integer", example=20),
     *                 @OA\Property(property="total", type="integer", example=80)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         ref="#/components/responses/UnauthorizedResponse"
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $revenues = Revenue::where('photographer_id', $request->user()->id)
            ->orderBy('month', 'desc')
            ->paginate(20);

        return response()->json(['success' => true, 'data' => $revenues]);
    }

    /**
     * @OA\Get(
     *     path="/api/photographer/revenue/available",
     *     operationId="getPhotographerRevenueAvailable",
     *     tags={"Photographer - Revenue"},
     *     summary="Get available balance",
     *     description="Get the total available balance ready for withdrawal (revenue where available_at <= now). This is the photographer's 80% share after 20% platform commission.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Available balance retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="available_amount", type="number", format="float", example=50000, description="Total available balance in FCFA ready for withdrawal")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         ref="#/components/responses/UnauthorizedResponse"
     *     )
     * )
     */
    public function available(Request $request): JsonResponse
    {
        $available = Revenue::where('photographer_id', $request->user()->id)
            ->sum('available_balance');

        return response()->json(['success' => true, 'data' => ['available_amount' => $available]]);
    }

    /**
     * @OA\Get(
     *     path="/api/photographer/revenue/pending",
     *     operationId="getPhotographerRevenuePending",
     *     tags={"Photographer - Revenue"},
     *     summary="Get pending revenue",
     *     description="Get all revenue records that are not yet available for withdrawal (available_at > now). Typically held for a period before becoming available.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Pending revenue retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="string", format="uuid"),
     *                     @OA\Property(property="photographer_id", type="string", format="uuid"),
     *                     @OA\Property(property="month", type="string", format="date", example="2025-11-01", description="Month of the revenue record"),
     *                     @OA\Property(property="total_sales", type="integer", example=50000, description="Total sales amount in FCFA"),
     *                     @OA\Property(property="commission", type="integer", example=10000, description="Platform commission (20%) in FCFA"),
     *                     @OA\Property(property="net_revenue", type="integer", example=40000, description="Photographer's net revenue (80%) in FCFA"),
     *                     @OA\Property(property="available_balance", type="integer", example=35000, description="Available balance for withdrawal in FCFA"),
     *                     @OA\Property(property="pending_balance", type="integer", example=5000, description="Pending balance in FCFA")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         ref="#/components/responses/UnauthorizedResponse"
     *     )
     * )
     */
    public function pending(Request $request): JsonResponse
    {
        $pending = Revenue::where('photographer_id', $request->user()->id)
            ->where('pending_balance', '>', 0)
            ->get();

        return response()->json(['success' => true, 'data' => $pending]);
    }

    /**
     * @OA\Get(
     *     path="/api/photographer/revenue/history",
     *     operationId="getPhotographerRevenueHistory",
     *     tags={"Photographer - Revenue"},
     *     summary="Get revenue history by month",
     *     description="Get monthly revenue summary with total earnings and sales count. Shows photographer's 80% share after 20% platform commission.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Revenue history retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="data",
     *                     type="array",
     *                     @OA\Items(
     *                         @OA\Property(property="month", type="string", format="date", example="2025-11-01", description="Month of sales"),
     *                         @OA\Property(property="total", type="integer", example=12000, description="Total photographer earnings for the month in FCFA"),
     *                         @OA\Property(property="sales", type="integer", example=3, description="Number of sales in this month")
     *                     )
     *                 ),
     *                 @OA\Property(property="current_page", type="integer", example=1),
     *                 @OA\Property(property="per_page", type="integer", example=30),
     *                 @OA\Property(property="total", type="integer", example=12, description="Total number of months with sales")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         ref="#/components/responses/UnauthorizedResponse"
     *     )
     * )
     */
    public function history(Request $request): JsonResponse
    {
        $history = Revenue::where('photographer_id', $request->user()->id)
            ->select('month', 'net_revenue as total', 'sales_count as sales')
            ->orderBy('month', 'desc')
            ->paginate(30);

        return response()->json(['success' => true, 'data' => $history]);
    }

    /**
     * @OA\Get(
     *     path="/api/photographer/revenue/transactions",
     *     operationId="getPhotographerRevenueTransactions",
     *     tags={"Photographer - Revenue"},
     *     summary="Get recent detailed transactions",
     *     description="Get paginated list of individual sales transactions with detailed information including photo title, sale date, net amount, and status.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Number of transactions per page",
     *         required=false,
     *         @OA\Schema(type="integer", default=15, minimum=1, maximum=100)
     *     ),
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter by transaction status",
     *         required=false,
     *         @OA\Schema(type="string", enum={"completed", "pending"})
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Transactions retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="data",
     *                     type="array",
     *                     @OA\Items(
     *                         @OA\Property(property="id", type="string", format="uuid", description="Transaction ID (OrderItem ID)"),
     *                         @OA\Property(property="description", type="string", example="Sunset over Abidjan", description="Photo title"),
     *                         @OA\Property(property="date", type="string", format="date-time", example="2025-11-15T14:30:00Z", description="Sale date"),
     *                         @OA\Property(property="amount", type="integer", example=4000, description="Net amount for photographer in FCFA (80% of sale)"),
     *                         @OA\Property(property="gross_amount", type="integer", example=5000, description="Gross sale amount in FCFA"),
     *                         @OA\Property(property="commission", type="integer", example=1000, description="Platform commission in FCFA (20%)"),
     *                         @OA\Property(property="status", type="string", enum={"completed", "pending"}, example="completed", description="Transaction status"),
     *                         @OA\Property(property="photo_id", type="string", format="uuid", description="Photo ID"),
     *                         @OA\Property(property="photo_thumbnail", type="string", example="https://...", description="Photo thumbnail URL"),
     *                         @OA\Property(property="license_type", type="string", example="standard", description="License type purchased"),
     *                         @OA\Property(property="order_number", type="string", example="ORD-20251115-ABC123", description="Order number")
     *                     )
     *                 ),
     *                 @OA\Property(property="current_page", type="integer", example=1),
     *                 @OA\Property(property="per_page", type="integer", example=15),
     *                 @OA\Property(property="total", type="integer", example=50)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         ref="#/components/responses/UnauthorizedResponse"
     *     )
     * )
     */
    public function transactions(Request $request): JsonResponse
    {
        $perPage = min($request->input('per_page', 15), 100);
        $statusFilter = $request->input('status');

        $query = OrderItem::where('photographer_id', $request->user()->id)
            ->with(['order:id,order_number,payment_status,paid_at,created_at'])
            ->orderBy('created_at', 'desc');

        // Filter by status if provided
        if ($statusFilter) {
            $query->whereHas('order', function ($q) use ($statusFilter) {
                $q->where('payment_status', $statusFilter);
            });
        }

        $orderItems = $query->paginate($perPage);

        // Transform the data to match the expected format
        $transactions = $orderItems->through(function ($item) {
            $order = $item->order;

            return [
                'id' => $item->id,
                'description' => $item->photo_title,
                'date' => $order->paid_at ?? $order->created_at,
                'amount' => $item->photographer_amount,
                'gross_amount' => $item->price,
                'commission' => $item->platform_commission,
                'status' => $order->payment_status === 'completed' ? 'completed' : 'pending',
                'photo_id' => $item->photo_id,
                'photo_thumbnail' => $item->photo_thumbnail,
                'license_type' => $item->license_type,
                'order_number' => $order->order_number,
            ];
        });

        return response()->json(['success' => true, 'data' => $transactions]);
    }
}
