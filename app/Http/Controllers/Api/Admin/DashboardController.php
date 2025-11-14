<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Photo;
use App\Models\User;
use App\Models\Withdrawal;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Get admin dashboard statistics
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
                'pending' => Photo::where('moderation_status', 'pending')->count(),
                'approved' => Photo::where('moderation_status', 'approved')->count(),
                'rejected' => Photo::where('moderation_status', 'rejected')->count(),
                'featured' => Photo::where('is_featured', true)->count(),
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
                    ->get(['id', 'title', 'photographer_id', 'moderation_status', 'created_at']),

                'latest_users' => User::latest()
                    ->take(5)
                    ->get(['id', 'first_name', 'last_name', 'email', 'account_type', 'created_at']),
            ],

            // Platform health
            'platform' => [
                'active_photographers' => User::where('account_type', 'photographer')
                    ->where('is_active', true)
                    ->whereHas('photos', function ($query) {
                        $query->where('moderation_status', 'approved');
                    })
                    ->count(),

                'average_photo_price' => Photo::where('moderation_status', 'approved')
                    ->avg('price'),

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
                $query->where('moderation_status', 'approved');
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
