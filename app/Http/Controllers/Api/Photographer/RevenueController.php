<?php

namespace App\Http\Controllers\Api\Photographer;

use App\Http\Controllers\Controller;
use App\Models\Revenue;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RevenueController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $revenues = Revenue::where('photographer_id', $request->user()->id)
            ->with('photo:id,title')
            ->orderBy('sold_at', 'desc')
            ->paginate(20);

        return response()->json(['success' => true, 'data' => $revenues]);
    }

    public function available(Request $request): JsonResponse
    {
        $available = Revenue::where('photographer_id', $request->user()->id)
            ->where('available_at', '<=', now())
            ->sum('photographer_amount');

        return response()->json(['success' => true, 'data' => ['available_amount' => $available]]);
    }

    public function pending(Request $request): JsonResponse
    {
        $pending = Revenue::where('photographer_id', $request->user()->id)
            ->where('available_at', '>', now())
            ->with('photo:id,title')
            ->get();

        return response()->json(['success' => true, 'data' => $pending]);
    }

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
