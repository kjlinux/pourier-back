<?php

namespace App\Http\Controllers\Api\Photographer;

use App\Http\Controllers\Controller;
use App\Models\Photo;
use App\Models\Revenue;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Get photographer dashboard statistics
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
     * Get photographer profile stats
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
