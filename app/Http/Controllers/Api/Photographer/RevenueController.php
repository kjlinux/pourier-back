<?php

namespace App\Http\Controllers\Api\Photographer;

use App\Http\Controllers\Controller;
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
     *                         @OA\Property(property="photo_id", type="string", format="uuid"),
     *                         @OA\Property(
     *                             property="photo",
     *                             type="object",
     *                             @OA\Property(property="id", type="string", format="uuid"),
     *                             @OA\Property(property="title", type="string", example="Sunset over Ouagadougou")
     *                         ),
     *                         @OA\Property(property="photographer_amount", type="number", format="float", example=4000, description="Photographer's share (80%) in FCFA"),
     *                         @OA\Property(property="platform_commission", type="number", format="float", example=1000, description="Platform commission (20%) in FCFA"),
     *                         @OA\Property(property="sold_at", type="string", format="date-time", example="2025-11-10T14:30:00Z"),
     *                         @OA\Property(property="available_at", type="string", format="date-time", example="2025-11-20T14:30:00Z", description="When revenue becomes available for withdrawal")
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
            ->with('photo:id,title')
            ->orderBy('sold_at', 'desc')
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
            ->where('available_at', '<=', now())
            ->sum('photographer_amount');

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
     *                     @OA\Property(property="photo_id", type="string", format="uuid"),
     *                     @OA\Property(
     *                         property="photo",
     *                         type="object",
     *                         @OA\Property(property="id", type="string", format="uuid"),
     *                         @OA\Property(property="title", type="string", example="Mountain Landscape")
     *                     ),
     *                     @OA\Property(property="photographer_amount", type="number", format="float", example=4000, description="Photographer's share (80%) in FCFA"),
     *                     @OA\Property(property="sold_at", type="string", format="date-time", example="2025-11-12T10:00:00Z"),
     *                     @OA\Property(property="available_at", type="string", format="date-time", example="2025-11-22T10:00:00Z", description="When this revenue will become available")
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
            ->where('available_at', '>', now())
            ->with('photo:id,title')
            ->get();

        return response()->json(['success' => true, 'data' => $pending]);
    }

    /**
     * @OA\Get(
     *     path="/api/photographer/revenue/history",
     *     operationId="getPhotographerRevenueHistory",
     *     tags={"Photographer - Revenue"},
     *     summary="Get revenue history by date",
     *     description="Get daily revenue summary with total earnings and sales count, grouped by date. Shows photographer's 80% share after 20% platform commission.",
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
     *                         @OA\Property(property="date", type="string", format="date", example="2025-11-14", description="Date of sales"),
     *                         @OA\Property(property="total", type="number", format="float", example=12000, description="Total photographer earnings for the day in FCFA"),
     *                         @OA\Property(property="sales", type="integer", example=3, description="Number of sales on this date")
     *                     )
     *                 ),
     *                 @OA\Property(property="current_page", type="integer", example=1),
     *                 @OA\Property(property="per_page", type="integer", example=30),
     *                 @OA\Property(property="total", type="integer", example=90, description="Total number of days with sales")
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
            ->selectRaw('DATE(sold_at) as date, SUM(photographer_amount) as total, COUNT(*) as sales')
            ->groupBy('date')
            ->orderBy('date', 'desc')
            ->paginate(30);

        return response()->json(['success' => true, 'data' => $history]);
    }
}
