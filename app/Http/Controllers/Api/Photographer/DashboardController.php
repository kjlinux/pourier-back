<?php

namespace App\Http\Controllers\Api\Photographer;

use App\Http\Controllers\Controller;
use App\Models\Photo;
use App\Models\Revenue;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Annotations as OA;

class DashboardController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/photographer/dashboard",
     *     operationId="getPhotographerDashboard",
     *     tags={"Photographer - Dashboard"},
     *     summary="Get photographer dashboard",
     *     description="Comprehensive dashboard with photo statistics, revenue (80% photographer share after 20% platform commission), sales data, and recent activity",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Dashboard data retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="photos",
     *                     type="object",
     *                     @OA\Property(property="total", type="integer", example=150, description="Total photos uploaded"),
     *                     @OA\Property(property="approved", type="integer", example=120, description="Approved photos available for sale"),
     *                     @OA\Property(property="pending", type="integer", example=25, description="Photos pending moderation"),
     *                     @OA\Property(property="rejected", type="integer", example=5, description="Rejected photos"),
     *                     @OA\Property(property="views", type="integer", example=15000, description="Total views across all photos")
     *                 ),
     *                 @OA\Property(
     *                     property="revenue",
     *                     type="object",
     *                     @OA\Property(property="available", type="number", format="float", example=50000, description="Available balance ready for withdrawal in FCFA"),
     *                     @OA\Property(property="pending", type="number", format="float", example=20000, description="Pending revenue not yet available in FCFA"),
     *                     @OA\Property(property="total_earnings", type="number", format="float", example=200000, description="Total lifetime earnings in FCFA"),
     *                     @OA\Property(property="this_month", type="number", format="float", example=15000, description="Current month earnings in FCFA")
     *                 ),
     *                 @OA\Property(
     *                     property="sales",
     *                     type="object",
     *                     @OA\Property(property="total_sales", type="integer", example=80, description="Total number of sales"),
     *                     @OA\Property(property="total_downloads", type="integer", example=95, description="Total downloads across all sales"),
     *                     @OA\Property(property="this_month_sales", type="integer", example=12, description="Sales this month")
     *                 ),
     *                 @OA\Property(property="recent_sales", type="array", @OA\Items(type="object"), description="5 most recent sales with photo and order details"),
     *                 @OA\Property(property="recent_photos", type="array", @OA\Items(type="object"), description="5 most recently uploaded photos")
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
        $photographer = $request->user();

        $stats = [
            // Photo statistics
            'photos' => [
                'total' => $photographer->photos()->count(),
                'approved' => $photographer->photos()->where('moderation_status', 'approved')->count(),
                'pending' => $photographer->photos()->where('moderation_status', 'pending')->count(),
                'rejected' => $photographer->photos()->where('moderation_status', 'rejected')->count(),
                'views' => $photographer->photos()->sum('view_count'),
            ],

            // Revenue statistics
            'revenue' => [
                'available' => Revenue::where('photographer_id', $photographer->id)
                    ->where('available_at', '<=', now())
                    ->sum('photographer_amount'),

                'pending' => Revenue::where('photographer_id', $photographer->id)
                    ->where('available_at', '>', now())
                    ->sum('photographer_amount'),

                'total_earnings' => Revenue::where('photographer_id', $photographer->id)
                    ->sum('photographer_amount'),

                'this_month' => Revenue::where('photographer_id', $photographer->id)
                    ->whereMonth('sold_at', now()->month)
                    ->sum('photographer_amount'),
            ],

            // Sales statistics
            'sales' => [
                'total_sales' => $photographer->orderItems()->count(),
                'total_downloads' => $photographer->orderItems()->sum('download_count'),
                'this_month_sales' => $photographer->orderItems()
                    ->whereHas('order', function ($q) {
                        $q->whereMonth('created_at', now()->month);
                    })
                    ->count(),
            ],

            // Recent activity
            'recent_sales' => $photographer->orderItems()
                ->with(['photo:id,title', 'order:id,order_number,created_at'])
                ->latest()
                ->take(5)
                ->get(),

            'recent_photos' => $photographer->photos()
                ->latest()
                ->take(5)
                ->get(['id', 'title', 'moderation_status', 'view_count', 'created_at']),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/photographer/dashboard/stats",
     *     operationId="getPhotographerDashboardStats",
     *     tags={"Photographer - Dashboard"},
     *     summary="Get photographer profile statistics",
     *     description="Get profile completion percentage, average photo pricing, and best-selling photo information",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Profile statistics retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="profile_completion", type="integer", example=83, description="Profile completion percentage (0-100)"),
     *                 @OA\Property(property="average_photo_price", type="number", format="float", example=7500, description="Average price of approved photos in FCFA"),
     *                 @OA\Property(
     *                     property="best_selling_photo",
     *                     type="object",
     *                     description="Photo with most sales",
     *                     @OA\Property(property="id", type="string", format="uuid"),
     *                     @OA\Property(property="title", type="string", example="Sunset over Ouagadougou"),
     *                     @OA\Property(property="order_items_count", type="integer", example=25, description="Number of times sold")
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
    public function stats(Request $request): JsonResponse
    {
        $photographer = $request->user();

        return response()->json([
            'success' => true,
            'data' => [
                'profile_completion' => $this->calculateProfileCompletion($photographer),
                'average_photo_price' => $photographer->photos()
                    ->where('moderation_status', 'approved')
                    ->avg('price'),
                'best_selling_photo' => $photographer->photos()
                    ->withCount('orderItems')
                    ->orderByDesc('order_items_count')
                    ->first(),
            ],
        ]);
    }

    private function calculateProfileCompletion($user): int
    {
        $fields = ['avatar_url', 'phone', 'bio'];
        $completed = 0;

        foreach ($fields as $field) {
            if (!empty($user->$field)) {
                $completed++;
            }
        }

        if ($user->photographerProfile) {
            $profileFields = ['portfolio_url', 'instagram_handle', 'payment_method'];
            foreach ($profileFields as $field) {
                if (!empty($user->photographerProfile->$field)) {
                    $completed++;
                }
            }
        }

        return round(($completed / 6) * 100);
    }
}
