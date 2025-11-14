<?php

namespace App\Http\Controllers\Api\Photographer;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
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
