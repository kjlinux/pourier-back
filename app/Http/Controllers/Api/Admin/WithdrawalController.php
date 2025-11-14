<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Withdrawal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WithdrawalController extends Controller
{
    /**
     * Get all withdrawals with filtering
     */
    public function index(Request $request): JsonResponse
    {
        $query = Withdrawal::with('photographer:id,first_name,last_name,email');

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        // Filter by photographer
        if ($request->has('photographer_id')) {
            $query->where('photographer_id', $request->input('photographer_id'));
        }

        $withdrawals = $query->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $withdrawals,
        ]);
    }

    /**
     * Get pending withdrawals
     */
    public function pending(Request $request): JsonResponse
    {
        $withdrawals = Withdrawal::with('photographer:id,first_name,last_name,email')
            ->where('status', 'pending')
            ->orderBy('created_at', 'asc')
            ->paginate($request->input('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $withdrawals,
        ]);
    }

    /**
     * Get withdrawal details
     */
    public function show(Withdrawal $withdrawal): JsonResponse
    {
        $withdrawal->load('photographer:id,first_name,last_name,email,phone');

        return response()->json([
            'success' => true,
            'data' => $withdrawal,
        ]);
    }

    /**
     * Approve withdrawal
     */
    public function approve(Request $request, Withdrawal $withdrawal): JsonResponse
    {
        $request->validate([
            'transaction_reference' => 'nullable|string|max:255',
            'admin_notes' => 'nullable|string|max:500',
        ]);

        if ($withdrawal->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Seules les demandes en attente peuvent être approuvées.',
            ], 400);
        }

        $withdrawal->update([
            'status' => 'approved',
            'processed_at' => now(),
            'transaction_reference' => $request->input('transaction_reference'),
            'admin_notes' => $request->input('admin_notes'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Demande de retrait approuvée avec succès.',
            'data' => $withdrawal->load('photographer'),
        ]);
    }

    /**
     * Reject withdrawal
     */
    public function reject(Request $request, Withdrawal $withdrawal): JsonResponse
    {
        $request->validate([
            'rejection_reason' => 'required|string|max:500',
        ]);

        if ($withdrawal->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Seules les demandes en attente peuvent être rejetées.',
            ], 400);
        }

        $withdrawal->update([
            'status' => 'rejected',
            'processed_at' => now(),
            'rejection_reason' => $request->input('rejection_reason'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Demande de retrait rejetée avec succès.',
            'data' => $withdrawal->load('photographer'),
        ]);
    }

    /**
     * Mark withdrawal as completed (payment sent)
     */
    public function complete(Request $request, Withdrawal $withdrawal): JsonResponse
    {
        $request->validate([
            'transaction_reference' => 'required|string|max:255',
        ]);

        if ($withdrawal->status !== 'approved') {
            return response()->json([
                'success' => false,
                'message' => 'Seules les demandes approuvées peuvent être marquées comme complétées.',
            ], 400);
        }

        $withdrawal->update([
            'status' => 'completed',
            'transaction_reference' => $request->input('transaction_reference'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Retrait marqué comme complété avec succès.',
            'data' => $withdrawal,
        ]);
    }
}
