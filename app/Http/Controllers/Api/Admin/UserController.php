<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Annotations as OA;

class UserController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/admin/users",
     *     operationId="getAdminUsers",
     *     tags={"Admin - Users"},
     *     summary="Get all users with filtering",
     *     description="Retrieve all users with advanced filtering options by account type, active status, and search term. Requires admin role.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="account_type",
     *         in="query",
     *         description="Filter by account type",
     *         required=false,
     *         @OA\Schema(type="string", enum={"buyer", "photographer", "admin"}, example="buyer")
     *     ),
     *     @OA\Parameter(
     *         name="is_active",
     *         in="query",
     *         description="Filter by active status",
     *         required=false,
     *         @OA\Schema(type="boolean", example=true)
     *     ),
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Search by name or email",
     *         required=false,
     *         @OA\Schema(type="string", example="Jean")
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Number of items per page",
     *         required=false,
     *         @OA\Schema(type="integer", default=20, example=20)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Users retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="current_page", type="integer", example=1),
     *                 @OA\Property(property="data", type="array", @OA\Items(type="object")),
     *                 @OA\Property(property="total", type="integer", example=1250)
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
     * @OA\Get(
     *     path="/api/admin/users/{user}",
     *     operationId="getAdminUser",
     *     tags={"Admin - Users"},
     *     summary="Get user details",
     *     description="Retrieve detailed information about a specific user. For photographers, includes profile and recent photos. Requires admin role.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="user",
     *         in="path",
     *         description="User UUID",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid", example="9d445a1c-85c5-4b6d-9c38-99a4915d6dac")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User details retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object")
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
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Utilisateur introuvable")
     *         )
     *     )
     * )
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
     * @OA\Put(
     *     path="/api/admin/users/{user}/suspend",
     *     operationId="suspendAdminUser",
     *     tags={"Admin - Users"},
     *     summary="Suspend user account",
     *     description="Suspend a user's account, preventing them from accessing the platform. Requires admin role.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="user",
     *         in="path",
     *         description="User UUID",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid", example="9d445a1c-85c5-4b6d-9c38-99a4915d6dac")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User account suspended successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Compte suspendu avec succès.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Account already suspended",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Ce compte est déjà suspendu.")
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
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Utilisateur introuvable")
     *         )
     *     )
     * )
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
     * @OA\Put(
     *     path="/api/admin/users/{user}/activate",
     *     operationId="activateAdminUser",
     *     tags={"Admin - Users"},
     *     summary="Activate user account",
     *     description="Activate a suspended user's account, restoring their access to the platform. Requires admin role.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="user",
     *         in="path",
     *         description="User UUID",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid", example="9d445a1c-85c5-4b6d-9c38-99a4915d6dac")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User account activated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Compte activé avec succès.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Account already active",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Ce compte est déjà actif.")
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
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Utilisateur introuvable")
     *         )
     *     )
     * )
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
     * @OA\Delete(
     *     path="/api/admin/users/{user}",
     *     operationId="deleteAdminUser",
     *     tags={"Admin - Users"},
     *     summary="Delete user account",
     *     description="Soft delete a user account. Admin accounts cannot be deleted. Requires admin role.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="user",
     *         in="path",
     *         description="User UUID",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid", example="9d445a1c-85c5-4b6d-9c38-99a4915d6dac")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User account deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Compte supprimé avec succès.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         ref="#/components/responses/UnauthorizedResponse"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - Cannot delete admin account or admin role required",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Impossible de supprimer un compte administrateur.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Utilisateur introuvable")
     *         )
     *     )
     * )
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
