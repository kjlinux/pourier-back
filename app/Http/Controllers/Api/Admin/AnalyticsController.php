<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Photo;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    /**
     * Get revenue analytics
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
     * Get sales analytics
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
     * Get photographer analytics
     */
    public function photographers(Request $request): JsonResponse
    {
        $topPhotographers = User::where('account_type', 'photographer')
            ->withCount(['photos as approved_photos_count' => function ($query) {
                $query->where('moderation_status', 'approved');
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
     * Get user growth analytics
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
