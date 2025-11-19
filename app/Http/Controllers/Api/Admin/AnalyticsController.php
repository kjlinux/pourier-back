<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Photo;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use OpenApi\Annotations as OA;

class AnalyticsController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/admin/analytics/revenue",
     *     operationId="getAdminAnalyticsRevenue",
     *     tags={"Admin - Analytics"},
     *     summary="Get revenue analytics",
     *     description="Retrieve revenue analytics with daily breakdown for a specified time period. Shows total revenue and daily revenue trends. Requires admin role.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="period",
     *         in="query",
     *         description="Time period for analytics",
     *         required=false,
     *         @OA\Schema(type="string", enum={"7days", "30days", "90days", "year"}, default="30days", example="30days")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Revenue analytics retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="period", type="string", example="30days"),
     *                 @OA\Property(property="start_date", type="string", format="date", example="2024-01-01"),
     *                 @OA\Property(property="end_date", type="string", format="date", example="2024-01-31"),
     *                 @OA\Property(property="total_revenue", type="number", format="float", example=750000),
     *                 @OA\Property(
     *                     property="daily_revenue",
     *                     type="array",
     *                     @OA\Items(
     *                         @OA\Property(property="date", type="string", format="date", example="2024-01-15"),
     *                         @OA\Property(property="total_revenue", type="number", format="float", example=25000),
     *                         @OA\Property(property="order_count", type="integer", example=5)
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         ref="#/components/responses/UnauthorizedResponse"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - Admin role required",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Accès non autorisé")
     *         )
     *     )
     * )
     */
    public function revenue(Request $request): JsonResponse
    {
        $period = $request->input('period', '30days'); // 7days, 30days, 90days, year

        $startDate = match($period) {
            '7days' => now()->subDays(7),
            '30days' => now()->subDays(30),
            '90days' => now()->subDays(90),
            'year' => now()->subYear(),
            default => now()->subDays(30),
        };

        $revenue = Order::where('payment_status', 'completed')
            ->where('created_at', '>=', $startDate)
            ->selectRaw('DATE(created_at) as date, SUM(total) as total_revenue, COUNT(*) as order_count')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $totalRevenue = Order::where('payment_status', 'completed')
            ->where('created_at', '>=', $startDate)
            ->sum('total');

        return response()->json([
            'success' => true,
            'data' => [
                'period' => $period,
                'start_date' => $startDate->toDateString(),
                'end_date' => now()->toDateString(),
                'total_revenue' => $totalRevenue,
                'daily_revenue' => $revenue,
            ],
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/admin/analytics/sales",
     *     operationId="getAdminAnalyticsSales",
     *     tags={"Admin - Analytics"},
     *     summary="Get sales analytics",
     *     description="Retrieve sales analytics including total orders, photos sold, average order value, and top selling photos for a specified time period. Requires admin role.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="period",
     *         in="query",
     *         description="Time period for analytics",
     *         required=false,
     *         @OA\Schema(type="string", enum={"7days", "30days", "90days", "year"}, default="30days", example="30days")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Sales analytics retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="period", type="string", example="30days"),
     *                 @OA\Property(property="total_orders", type="integer", example=150),
     *                 @OA\Property(property="total_photos_sold", type="integer", example=250),
     *                 @OA\Property(property="average_order_value", type="number", format="float", example=5000),
     *                 @OA\Property(
     *                     property="top_selling_photos",
     *                     type="array",
     *                     description="Top 10 best-selling photos in the period",
     *                     @OA\Items(type="object")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         ref="#/components/responses/UnauthorizedResponse"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - Admin role required",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Accès non autorisé")
     *         )
     *     )
     * )
     */
    public function sales(Request $request): JsonResponse
    {
        $period = $request->input('period', '30days');

        $startDate = match($period) {
            '7days' => now()->subDays(7),
            '30days' => now()->subDays(30),
            '90days' => now()->subDays(90),
            'year' => now()->subYear(),
            default => now()->subDays(30),
        };

        $sales = Order::where('payment_status', 'completed')
            ->where('created_at', '>=', $startDate)
            ->with('items:id,order_id,photo_id,price')
            ->get();

        $totalOrders = $sales->count();
        $totalPhotos = $sales->sum(fn($order) => $order->items->count());
        $averageOrderValue = $totalOrders > 0 ? $sales->sum('total') / $totalOrders : 0;

        // Top selling photos
        $topPhotos = Photo::withCount(['orderItems as sales_count' => function ($query) use ($startDate) {
                $query->whereHas('order', function ($q) use ($startDate) {
                    $q->where('payment_status', 'completed')
                      ->where('created_at', '>=', $startDate);
                });
            }])
            ->having('sales_count', '>', 0)
            ->orderByDesc('sales_count')
            ->take(10)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'period' => $period,
                'total_orders' => $totalOrders,
                'total_photos_sold' => $totalPhotos,
                'average_order_value' => round($averageOrderValue, 2),
                'top_selling_photos' => $topPhotos,
            ],
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/admin/analytics/photographers",
     *     operationId="getAdminAnalyticsPhotographers",
     *     tags={"Admin - Analytics"},
     *     summary="Get photographer analytics",
     *     description="Retrieve analytics for top 20 photographers ranked by total earnings, including approved photos count and total earnings. Requires admin role.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Photographer analytics retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="top_photographers",
     *                     type="array",
     *                     description="Top 20 photographers by earnings",
     *                     @OA\Items(
     *                         @OA\Property(property="id", type="string", format="uuid", example="9d445a1c-85c5-4b6d-9c38-99a4915d6dac"),
     *                         @OA\Property(property="first_name", type="string", example="Jean"),
     *                         @OA\Property(property="last_name", type="string", example="Dupont"),
     *                         @OA\Property(property="email", type="string", example="jean.dupont@example.com"),
     *                         @OA\Property(property="created_at", type="string", format="datetime"),
     *                         @OA\Property(property="approved_photos_count", type="integer", example=125),
     *                         @OA\Property(property="total_earnings", type="number", format="float", example=450000)
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         ref="#/components/responses/UnauthorizedResponse"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - Admin role required",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Accès non autorisé")
     *         )
     *     )
     * )
     */
    public function photographers(Request $request): JsonResponse
    {
        $topPhotographers = User::where('account_type', 'photographer')
            ->withCount(['photos as approved_photos_count' => function ($query) {
                $query->where('status', 'approved');
            }])
            ->withSum(['orderItems as total_earnings' => function ($query) {
                $query->whereHas('order', function ($q) {
                    $q->where('payment_status', 'completed');
                });
            }], 'photographer_amount')
            ->orderByDesc('total_earnings')
            ->take(20)
            ->get(['id', 'first_name', 'last_name', 'email', 'created_at']);

        return response()->json([
            'success' => true,
            'data' => [
                'top_photographers' => $topPhotographers,
            ],
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/admin/analytics/user-growth",
     *     operationId="getAdminAnalyticsUserGrowth",
     *     tags={"Admin - Analytics"},
     *     summary="Get user growth analytics",
     *     description="Retrieve user growth analytics showing daily new user registrations grouped by account type for a specified time period. Requires admin role.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="period",
     *         in="query",
     *         description="Time period for analytics",
     *         required=false,
     *         @OA\Schema(type="string", enum={"7days", "30days", "90days", "year"}, default="30days", example="30days")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User growth analytics retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="period", type="string", example="30days"),
     *                 @OA\Property(
     *                     property="user_growth",
     *                     type="object",
     *                     description="User registrations grouped by date and account type",
     *                     additionalProperties=true
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         ref="#/components/responses/UnauthorizedResponse"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - Admin role required",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Accès non autorisé")
     *         )
     *     )
     * )
     */
    public function userGrowth(Request $request): JsonResponse
    {
        $period = $request->input('period', '30days');

        $startDate = match($period) {
            '7days' => now()->subDays(7),
            '30days' => now()->subDays(30),
            '90days' => now()->subDays(90),
            'year' => now()->subYear(),
            default => now()->subDays(30),
        };

        $userGrowth = User::where('created_at', '>=', $startDate)
            ->selectRaw('DATE(created_at) as date, account_type, COUNT(*) as count')
            ->groupBy('date', 'account_type')
            ->orderBy('date')
            ->get()
            ->groupBy('date');

        return response()->json([
            'success' => true,
            'data' => [
                'period' => $period,
                'user_growth' => $userGrowth,
            ],
        ]);
    }
}
