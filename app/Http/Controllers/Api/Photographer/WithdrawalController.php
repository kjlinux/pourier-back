<?php

namespace App\Http\Controllers\Api\Photographer;

use App\Http\Controllers\Controller;
use App\Models\Revenue;
use App\Models\Withdrawal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WithdrawalController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $withdrawals = Withdrawal::where('photographer_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json(['success' => true, 'data' => $withdrawals]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'amount' => 'required|integer|min:10000',
            'payment_method' => 'required|in:mobile_money,bank_transfer',
            'payment_details' => 'required|array',
        ]);

        $available = Revenue::where('photographer_id', $request->user()->id)
            ->where('available_at', '<=', now())
            ->sum('photographer_amount');

        if ($request->amount > $available) {
            return response()->json(['success' => false, 'message' => 'Montant indisponible.'], 400);
        }

        $withdrawal = Withdrawal::create([
            'photographer_id' => $request->user()->id,
            'amount' => $request->amount,
            'payment_method' => $request->payment_method,
            'payment_details' => $request->payment_details,
            'status' => 'pending',
        ]);

        return response()->json(['success' => true, 'message' => 'Demande créée.', 'data' => $withdrawal]);
    }

    public function show(Request $request, Withdrawal $withdrawal): JsonResponse
    {
        if ($withdrawal->photographer_id !== $request->user()->id) {
            return response()->json(['success' => false, 'message' => 'Non autorisé.'], 403);
        }

        return response()->json(['success' => true, 'data' => $withdrawal]);
    }

    public function destroy(Request $request, Withdrawal $withdrawal): JsonResponse
    {
        if ($withdrawal->photographer_id !== $request->user()->id || $withdrawal->status !== 'pending') {
            return response()->json(['success' => false, 'message' => 'Impossible d\'annuler.'], 400);
        }

        $withdrawal->delete();
        return response()->json(['success' => true, 'message' => 'Demande annulée.']);
    }
}
