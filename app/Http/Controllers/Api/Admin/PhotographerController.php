<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\PhotographerProfile;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Annotations as OA;

class PhotographerController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/admin/photographers",
     *     operationId="getAdminPhotographers",
     *     tags={"Admin - Photographers"},
     *     summary="Get all photographers",
     *     description="Retrieve all photographers with filtering by status and search capabilities. Requires admin role.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter by photographer profile status",
     *         required=false,
     *         @OA\Schema(type="string", enum={"pending", "approved", "rejected"}, example="approved")
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
     *         description="Photographers retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="current_page", type="integer", example=1),
     *                 @OA\Property(property="data", type="array", @OA\Items(type="object")),
     *                 @OA\Property(property="total", type="integer", example=400)
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
     * @OA\Get(
     *     path="/api/admin/photographers/pending",
     *     operationId="getAdminPhotographersPending",
     *     tags={"Admin - Photographers"},
     *     summary="Get pending photographer profiles",
     *     description="Retrieve all photographer profiles awaiting approval. Requires admin role.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Number of items per page",
     *         required=false,
     *         @OA\Schema(type="integer", default=20, example=20)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Pending photographers retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="current_page", type="integer", example=1),
     *                 @OA\Property(property="data", type="array", @OA\Items(type="object")),
     *                 @OA\Property(property="total", type="integer", example=25)
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
     * @OA\Get(
     *     path="/api/admin/photographers/{photographer}",
     *     operationId="getAdminPhotographer",
     *     tags={"Admin - Photographers"},
     *     summary="Get photographer details",
     *     description="Retrieve detailed information about a specific photographer including profile, recent photos, and statistics (total photos, sales, downloads). Requires admin role.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="photographer",
     *         in="path",
     *         description="Photographer UUID",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid", example="9d445a1c-85c5-4b6d-9c38-99a4915d6dac")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Photographer details retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="User is not a photographer",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Cet utilisateur n'est pas un photographe.")
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
     *         description="Photographer not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Photographe introuvable")
     *         )
     *     )
     * )
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
            'approved_photos' => $photographer->photos()->where('status', 'approved')->count(),
            'pending_photos' => $photographer->photos()->where('status', 'pending')->count(),
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
     * @OA\Put(
     *     path="/api/admin/photographers/{photographer}/approve",
     *     operationId="approveAdminPhotographer",
     *     tags={"Admin - Photographers"},
     *     summary="Approve photographer profile",
     *     description="Approve a pending photographer profile, allowing them to upload and sell photos on the platform. Requires admin role.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="photographer",
     *         in="path",
     *         description="Photographer UUID",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid", example="9d445a1c-85c5-4b6d-9c38-99a4915d6dac")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Photographer profile approved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Profil photographe approuvé avec succès."),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid request (not a photographer or already approved)",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Ce profil est déjà approuvé.")
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
     *         description="Photographer or profile not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Profil photographe introuvable.")
     *         )
     *     )
     * )
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
     * @OA\Put(
     *     path="/api/admin/photographers/{photographer}/reject",
     *     operationId="rejectAdminPhotographer",
     *     tags={"Admin - Photographers"},
     *     summary="Reject photographer profile",
     *     description="Reject a pending photographer profile with optional rejection reason. Requires admin role.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="photographer",
     *         in="path",
     *         description="Photographer UUID",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid", example="9d445a1c-85c5-4b6d-9c38-99a4915d6dac")
     *     ),
     *     @OA\RequestBody(
     *         required=false,
     *         @OA\JsonContent(
     *             @OA\Property(property="rejection_reason", type="string", maxLength=500, example="Profile information incomplete or invalid", description="Reason for rejection")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Photographer profile rejected successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Profil photographe rejeté avec succès."),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid request (not a photographer or already rejected)",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Ce profil est déjà rejeté.")
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
     *         description="Photographer or profile not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Profil photographe introuvable.")
     *         )
     *     )
     * )
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
     * @OA\Put(
     *     path="/api/admin/photographers/{photographer}/suspend",
     *     operationId="suspendAdminPhotographer",
     *     tags={"Admin - Photographers"},
     *     summary="Suspend photographer account",
     *     description="Suspend a photographer's account, preventing them from accessing the platform. Requires admin role.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="photographer",
     *         in="path",
     *         description="Photographer UUID",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid", example="9d445a1c-85c5-4b6d-9c38-99a4915d6dac")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Photographer account suspended successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Compte photographe suspendu avec succès.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="User is not a photographer",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Cet utilisateur n'est pas un photographe.")
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
     *         description="Photographer not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Photographe introuvable")
     *         )
     *     )
     * )
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
     * @OA\Put(
     *     path="/api/admin/photographers/{photographer}/activate",
     *     operationId="activateAdminPhotographer",
     *     tags={"Admin - Photographers"},
     *     summary="Activate photographer account",
     *     description="Activate a suspended photographer's account, restoring their access to the platform. Requires admin role.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="photographer",
     *         in="path",
     *         description="Photographer UUID",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid", example="9d445a1c-85c5-4b6d-9c38-99a4915d6dac")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Photographer account activated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Compte photographe activé avec succès.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="User is not a photographer",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Cet utilisateur n'est pas un photographe.")
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
     *         description="Photographer not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Photographe introuvable")
     *         )
     *     )
     * )
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
