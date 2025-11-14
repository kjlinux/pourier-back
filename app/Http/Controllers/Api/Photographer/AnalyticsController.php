<?php

namespace App\Http\Controllers\Api\Photographer;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Annotations as OA;

class AnalyticsController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/photographer/analytics/sales",
     *     tags={"Photographer - Analytics"},
     *     summary="Get sales analytics",
     *     description="Get sales analytics for a specified period (7, 30, or 90 days). Revenue shown is photographer's 80% share after 20% platform commission.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="period",
     *         in="query",
     *         description="Analytics period",
     *         required=false,
     *         @OA\Schema(type="string", enum={"7days", "30days", "90days"}, default="30days", example="30days")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Sales analytics retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="total_sales", type="integer", example=45, description="Total number of sales in the period"),
     *                 @OA\Property(property="total_revenue", type="number", format="float", example=180000, description="Total photographer revenue (80% share) in FCFA"),
     *                 @OA\Property(property="average_sale", type="number", format="float", example=4000, description="Average revenue per sale in FCFA")
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
    public function sales(Request $request): JsonResponse
    {
        $photographer = $request->user();
        $period = $request->input('period', '30days');

        $startDate = match($period) {
            '7days' => now()->subDays(7),
            '30days' => now()->subDays(30),
            '90days' => now()->subDays(90),
            default => now()->subDays(30),
        };

        $sales = $photographer->orderItems()
            ->whereHas('order', fn($q) => $q->where('created_at', '>=', $startDate))
            ->with('photo:id,title')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'total_sales' => $sales->count(),
                'total_revenue' => $sales->sum('photographer_amount'),
                'average_sale' => $sales->count() > 0 ? $sales->avg('photographer_amount') : 0,
            ],
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/photographer/analytics/popular-photos",
     *     tags={"Photographer - Analytics"},
     *     summary="Get popular photos",
     *     description="Get top 10 best-selling photos ranked by total number of sales",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Popular photos retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="string", format="uuid"),
     *                     @OA\Property(property="title", type="string", example="Sunset over Ouagadougou"),
     *                     @OA\Property(property="thumbnail_url", type="string", format="url", example="https://pourier.s3.amazonaws.com/thumbnails/xyz.jpg"),
     *                     @OA\Property(property="price_standard", type="number", format="float", example=5000),
     *                     @OA\Property(property="price_extended", type="number", format="float", example=10000),
     *                     @OA\Property(property="view_count", type="integer", example=1500),
     *                     @OA\Property(property="order_items_count", type="integer", example=25, description="Total number of times this photo was sold"),
     *                     @OA\Property(property="created_at", type="string", format="date-time")
     *                 ),
     *                 description="Top 10 photos by sales count"
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
    public function popularPhotos(Request $request): JsonResponse
    {
        $topPhotos = $request->user()->photos()
            ->withCount('orderItems')
            ->orderByDesc('order_items_count')
            ->take(10)
            ->get();

        return response()->json(['success' => true, 'data' => $topPhotos]);
    }
}
