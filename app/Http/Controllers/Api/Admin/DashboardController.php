<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Photo;
use App\Models\User;
use App\Models\Withdrawal;
use Illuminate\Http\JsonResponse;
use OpenApi\Annotations as OA;

class DashboardController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/admin/dashboard",
     *     operationId="getAdminDashboard",
     *     tags={"Admin - Dashboard"},
     *     summary="Get admin dashboard statistics",
     *     description="Retrieve comprehensive platform statistics including users, photos, orders, withdrawals, recent activity, and platform health metrics. Requires admin role.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Dashboard statistics retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="users",
     *                     type="object",
     *                     @OA\Property(property="total", type="integer", example=1250),
     *                     @OA\Property(property="buyers", type="integer", example=800),
     *                     @OA\Property(property="photographers", type="integer", example=400),
     *                     @OA\Property(property="admins", type="integer", example=5),
     *                     @OA\Property(property="verified", type="integer", example=950),
     *                     @OA\Property(property="new_this_month", type="integer", example=45)
     *                 ),
     *                 @OA\Property(
     *                     property="photos",
     *                     type="object",
     *                     @OA\Property(property="total", type="integer", example=5000),
     *                     @OA\Property(property="pending", type="integer", example=120),
     *                     @OA\Property(property="approved", type="integer", example=4500),
     *                     @OA\Property(property="rejected", type="integer", example=380),
     *                     @OA\Property(property="featured", type="integer", example=50),
     *                     @OA\Property(property="uploaded_this_month", type="integer", example=200)
     *                 ),
     *                 @OA\Property(
     *                     property="orders",
     *                     type="object",
     *                     @OA\Property(property="total", type="integer", example=3500),
     *                     @OA\Property(property="pending", type="integer", example=25),
     *                     @OA\Property(property="completed", type="integer", example=3400),
     *                     @OA\Property(property="failed", type="integer", example=75),
     *                     @OA\Property(property="total_revenue", type="number", format="float", example=17500000),
     *                     @OA\Property(property="orders_this_month", type="integer", example=150),
     *                     @OA\Property(property="revenue_this_month", type="number", format="float", example=750000)
     *                 ),
     *                 @OA\Property(
     *                     property="withdrawals",
     *                     type="object",
     *                     @OA\Property(property="total", type="integer", example=450),
     *                     @OA\Property(property="pending", type="integer", example=15),
     *                     @OA\Property(property="approved", type="integer", example=420),
     *                     @OA\Property(property="rejected", type="integer", example=15),
     *                     @OA\Property(property="total_amount", type="number", format="float", example=8400000),
     *                     @OA\Property(property="pending_amount", type="number", format="float", example=150000)
     *                 ),
     *                 @OA\Property(
     *                     property="recent_activity",
     *                     type="object",
     *                     @OA\Property(property="latest_orders", type="array", @OA\Items(type="object")),
     *                     @OA\Property(property="latest_photos", type="array", @OA\Items(type="object")),
     *                     @OA\Property(property="latest_users", type="array", @OA\Items(type="object"))
     *                 ),
     *                 @OA\Property(
     *                     property="platform",
     *                     type="object",
     *                     @OA\Property(property="active_photographers", type="integer", example=250),
     *                     @OA\Property(property="average_photo_price", type="number", format="float", example=3500),
     *                     @OA\Property(property="conversion_rate", type="number", format="float", example=35.5),
     *                     @OA\Property(property="top_photographers", type="array", @OA\Items(type="object"))
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
    public function index(): JsonResponse
    {
        $stats = [
            // User statistics
            'users' => [
                'total' => User::count(),
                'buyers' => User::where('account_type', 'buyer')->count(),
                'photographers' => User::where('account_type', 'photographer')->count(),
                'admins' => User::where('account_type', 'admin')->count(),
                'verified' => User::where('is_verified', true)->count(),
                'new_this_month' => User::whereMonth('created_at', now()->month)->count(),
            ],

            // Photo statistics
            'photos' => [
                'total' => Photo::count(),
                'pending' => Photo::where('status', 'pending')->count(),
                'approved' => Photo::where('status', 'approved')->count(),
                'rejected' => Photo::where('status', 'rejected')->count(),
                'featured' => Photo::where('featured', true)->count(),
                'uploaded_this_month' => Photo::whereMonth('created_at', now()->month)->count(),
            ],

            // Order statistics
            'orders' => [
                'total' => Order::count(),
                'pending' => Order::where('payment_status', 'pending')->count(),
                'completed' => Order::where('payment_status', 'completed')->count(),
                'failed' => Order::where('payment_status', 'failed')->count(),
                'total_revenue' => Order::where('payment_status', 'completed')->sum('total'),
                'orders_this_month' => Order::whereMonth('created_at', now()->month)->count(),
                'revenue_this_month' => Order::where('payment_status', 'completed')
                    ->whereMonth('created_at', now()->month)
                    ->sum('total'),
            ],

            // Withdrawal statistics
            'withdrawals' => [
                'total' => Withdrawal::count(),
                'pending' => Withdrawal::where('status', 'pending')->count(),
                'approved' => Withdrawal::where('status', 'approved')->count(),
                'rejected' => Withdrawal::where('status', 'rejected')->count(),
                'total_amount' => Withdrawal::where('status', 'approved')->sum('amount'),
                'pending_amount' => Withdrawal::where('status', 'pending')->sum('amount'),
            ],

            // Recent activity
            'recent_activity' => [
                'latest_orders' => Order::with('user:id,first_name,last_name,email')
                    ->latest()
                    ->take(5)
                    ->get(['id', 'order_number', 'user_id', 'total', 'payment_status', 'created_at']),

                'latest_photos' => Photo::with('photographer:id,first_name,last_name')
                    ->latest()
                    ->take(5)
                    ->get(['id', 'title', 'photographer_id', 'status', 'created_at']),

                'latest_users' => User::latest()
                    ->take(5)
                    ->get(['id', 'first_name', 'last_name', 'email', 'account_type', 'created_at']),
            ],

            // Platform health
            'platform' => [
                'active_photographers' => User::where('account_type', 'photographer')
                    ->where('is_active', true)
                    ->whereHas('photos', function ($query) {
                        $query->where('status', 'approved');
                    })
                    ->count(),

                'average_photo_price' => Photo::where('status', 'approved')
                    ->avg('price_standard'),

                'conversion_rate' => $this->calculateConversionRate(),

                'top_photographers' => $this->getTopPhotographers(),
            ],
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Calculate conversion rate (orders / total users)
     */
    private function calculateConversionRate(): float
    {
        $totalBuyers = User::where('account_type', 'buyer')->count();

        if ($totalBuyers === 0) {
            return 0;
        }

        $buyersWithOrders = Order::where('payment_status', 'completed')
            ->distinct('user_id')
            ->count('user_id');

        return round(($buyersWithOrders / $totalBuyers) * 100, 2);
    }

    /**
     * Get top 5 photographers by sales
     */
    private function getTopPhotographers(): array
    {
        return User::where('account_type', 'photographer')
            ->withCount(['photos' => function ($query) {
                $query->where('status', 'approved');
            }])
            ->withSum(['orderItems as total_sales' => function ($query) {
                $query->whereHas('order', function ($q) {
                    $q->where('payment_status', 'completed');
                });
            }], 'photographer_amount')
            ->orderByDesc('total_sales')
            ->take(5)
            ->get(['id', 'first_name', 'last_name', 'email'])
            ->map(function ($photographer) {
                return [
                    'id' => $photographer->id,
                    'name' => $photographer->first_name . ' ' . $photographer->last_name,
                    'email' => $photographer->email,
                    'photos_count' => $photographer->photos_count,
                    'total_sales' => $photographer->total_sales ?? 0,
                ];
            })
            ->toArray();
    }
}
