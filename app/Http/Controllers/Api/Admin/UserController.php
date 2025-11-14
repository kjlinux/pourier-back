<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * Get all users with filtering
     */
    public function index(Request $request): JsonResponse
    {
        $query = User::query();

        // Filter by account type
        if ($request->has('account_type')) {
            $query->where('account_type', $request->input('account_type'));
        }

        // Filter by status
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
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

        $users = $query->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $users,
        ]);
    }

    /**
     * Get user details
     */
    public function show(User $user): JsonResponse
    {
        // Load relationships based on account type
        if ($user->account_type === 'photographer') {
            $user->load(['photographerProfile', 'photos' => function ($query) {
                $query->latest()->take(10);
            }]);
        }

        return response()->json([
            'success' => true,
            'data' => $user,
        ]);
    }

    /**
     * Suspend user account
     */
    public function suspend(User $user): JsonResponse
    {
        if (!$user->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Ce compte est déjà suspendu.',
            ], 400);
        }

        $user->update(['is_active' => false]);

        return response()->json([
            'success' => true,
            'message' => 'Compte suspendu avec succès.',
        ]);
    }

    /**
     * Activate user account
     */
    public function activate(User $user): JsonResponse
    {
        if ($user->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Ce compte est déjà actif.',
            ], 400);
        }

        $user->update(['is_active' => true]);

        return response()->json([
            'success' => true,
            'message' => 'Compte activé avec succès.',
        ]);
    }

    /**
     * Delete user account (soft delete)
     */
    public function destroy(User $user): JsonResponse
    {
        if ($user->account_type === 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Impossible de supprimer un compte administrateur.',
            ], 403);
        }

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'Compte supprimé avec succès.',
        ]);
    }
}
