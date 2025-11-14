<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\PhotographerProfile;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PhotographerController extends Controller
{
    /**
     * Get all photographers
     */
    public function index(Request $request): JsonResponse
    {
        $query = User::with('photographerProfile')
            ->where('account_type', 'photographer');

        // Filter by status
        if ($request->has('status')) {
            $query->whereHas('photographerProfile', function ($q) use ($request) {
                $q->where('status', $request->input('status'));
            });
        }

        // Search by name or email
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'ILIKE', "%$search%")
                  ->orWhere('last_name', 'ILIKE', "%$search%")
                  ->orWhere('email', 'ILIKE', "%$search%");
            });
        }

        $photographers = $query->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $photographers,
        ]);
    }

    /**
     * Get pending photographer profiles
     */
    public function pending(Request $request): JsonResponse
    {
        $photographers = User::with('photographerProfile')
            ->where('account_type', 'photographer')
            ->whereHas('photographerProfile', function ($q) {
                $q->where('status', 'pending');
            })
            ->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $photographers,
        ]);
    }

    /**
     * Get photographer details
     */
    public function show(User $photographer): JsonResponse
    {
        if ($photographer->account_type !== 'photographer') {
            return response()->json([
                'success' => false,
                'message' => 'Cet utilisateur n\'est pas un photographe.',
            ], 400);
        }

        $photographer->load(['photographerProfile', 'photos' => function ($query) {
            $query->latest()->take(10);
        }]);

        // Add statistics
        $photographer->stats = [
            'total_photos' => $photographer->photos()->count(),
            'approved_photos' => $photographer->photos()->where('moderation_status', 'approved')->count(),
            'pending_photos' => $photographer->photos()->where('moderation_status', 'pending')->count(),
            'total_sales' => $photographer->orderItems()->whereHas('order', function ($q) {
                $q->where('payment_status', 'completed');
            })->sum('photographer_amount'),
            'total_downloads' => $photographer->orderItems()->sum('download_count'),
        ];

        return response()->json([
            'success' => true,
            'data' => $photographer,
        ]);
    }

    /**
     * Approve photographer profile
     */
    public function approve(User $photographer): JsonResponse
    {
        if ($photographer->account_type !== 'photographer') {
            return response()->json([
                'success' => false,
                'message' => 'Cet utilisateur n\'est pas un photographe.',
            ], 400);
        }

        $profile = $photographer->photographerProfile;

        if (!$profile) {
            return response()->json([
                'success' => false,
                'message' => 'Profil photographe introuvable.',
            ], 404);
        }

        if ($profile->status === 'approved') {
            return response()->json([
                'success' => false,
                'message' => 'Ce profil est déjà approuvé.',
            ], 400);
        }

        $profile->update([
            'status' => 'approved',
            'reviewed_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Profil photographe approuvé avec succès.',
            'data' => $photographer->load('photographerProfile'),
        ]);
    }

    /**
     * Reject photographer profile
     */
    public function reject(Request $request, User $photographer): JsonResponse
    {
        $request->validate([
            'rejection_reason' => 'nullable|string|max:500',
        ]);

        if ($photographer->account_type !== 'photographer') {
            return response()->json([
                'success' => false,
                'message' => 'Cet utilisateur n\'est pas un photographe.',
            ], 400);
        }

        $profile = $photographer->photographerProfile;

        if (!$profile) {
            return response()->json([
                'success' => false,
                'message' => 'Profil photographe introuvable.',
            ], 404);
        }

        if ($profile->status === 'rejected') {
            return response()->json([
                'success' => false,
                'message' => 'Ce profil est déjà rejeté.',
            ], 400);
        }

        $profile->update([
            'status' => 'rejected',
            'rejection_reason' => $request->input('rejection_reason'),
            'reviewed_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Profil photographe rejeté avec succès.',
            'data' => $photographer->load('photographerProfile'),
        ]);
    }

    /**
     * Suspend photographer account
     */
    public function suspend(User $photographer): JsonResponse
    {
        if ($photographer->account_type !== 'photographer') {
            return response()->json([
                'success' => false,
                'message' => 'Cet utilisateur n\'est pas un photographe.',
            ], 400);
        }

        $photographer->update(['is_active' => false]);

        return response()->json([
            'success' => true,
            'message' => 'Compte photographe suspendu avec succès.',
        ]);
    }

    /**
     * Activate photographer account
     */
    public function activate(User $photographer): JsonResponse
    {
        if ($photographer->account_type !== 'photographer') {
            return response()->json([
                'success' => false,
                'message' => 'Cet utilisateur n\'est pas un photographe.',
            ], 400);
        }

        $photographer->update(['is_active' => true]);

        return response()->json([
            'success' => true,
            'message' => 'Compte photographe activé avec succès.',
        ]);
    }
}
