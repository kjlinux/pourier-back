<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Photo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use OpenApi\Annotations as OA;

class PhotoModerationController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/admin/photos/pending",
     *     operationId="getAdminPhotosPending",
     *     tags={"Admin - Photo Moderation"},
     *     summary="Get pending photos for moderation",
     *     description="Retrieve all photos awaiting moderation review with photographer and category information. Requires admin role.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Number of items per page",
     *         required=false,
     *         @OA\Schema(type="integer", default=20, example=20)
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number",
     *         required=false,
     *         @OA\Schema(type="integer", default=1, example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Pending photos retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="current_page", type="integer", example=1),
     *                 @OA\Property(property="data", type="array", @OA\Items(type="object")),
     *                 @OA\Property(property="total", type="integer", example=120)
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
        $photos = Photo::with(['photographer:id,first_name,last_name,email', 'category:id,name'])
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $photos,
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/admin/photos",
     *     operationId="getAdminPhotos",
     *     tags={"Admin - Photo Moderation"},
     *     summary="Get all photos with filtering",
     *     description="Retrieve all photos with advanced filtering options by status, photographer, and search term. Requires admin role.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter by moderation status",
     *         required=false,
     *         @OA\Schema(type="string", enum={"pending", "approved", "rejected"}, example="pending")
     *     ),
     *     @OA\Parameter(
     *         name="photographer_id",
     *         in="query",
     *         description="Filter by photographer UUID",
     *         required=false,
     *         @OA\Schema(type="string", format="uuid", example="9d445a1c-85c5-4b6d-9c38-99a4915d6dac")
     *     ),
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Search by photo title",
     *         required=false,
     *         @OA\Schema(type="string", example="sunset")
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
     *         description="Photos retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="current_page", type="integer", example=1),
     *                 @OA\Property(property="data", type="array", @OA\Items(type="object")),
     *                 @OA\Property(property="total", type="integer", example=5000)
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
        $query = Photo::with(['photographer:id,first_name,last_name,email', 'category:id,name']);

        // Filter by moderation status
        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        // Filter by photographer
        if ($request->has('photographer_id')) {
            $query->where('photographer_id', $request->input('photographer_id'));
        }

        // Search by title
        if ($request->has('search')) {
            $query->where('title', 'ILIKE', '%' . $request->input('search') . '%');
        }

        $photos = $query->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $photos,
        ]);
    }

    /**
     * @OA\Put(
     *     path="/api/admin/photos/{photo}/approve",
     *     operationId="approveAdminPhoto",
     *     tags={"Admin - Photo Moderation"},
     *     summary="Approve a photo",
     *     description="Approve a pending photo, making it publicly visible. The photo's moderation status is set to 'approved' and visibility is changed to 'public'. Requires admin role.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="photo",
     *         in="path",
     *         description="Photo UUID",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid", example="9d445a1c-85c5-4b6d-9c38-99a4915d6dac")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Photo approved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Photo approuvée avec succès."),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Photo already approved",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Cette photo est déjà approuvée.")
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
     *         description="Photo not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Photo introuvable")
     *         )
     *     )
     * )
     */
    public function approve(Photo $photo): JsonResponse
    {
        if ($photo->status === 'approved') {
            return response()->json([
                'success' => false,
                'message' => 'Cette photo est déjà approuvée.',
            ], 400);
        }

        DB::transaction(function () use ($photo) {
            $photo->update([
                'status' => 'approved',
                'moderated_at' => now(),
                'is_public' => true, // Rendre la photo publique
            ]);

            // Envoyer notification au photographe (via Job)
            // dispatch(new PhotoApprovedNotification($photo));
        });

        return response()->json([
            'success' => true,
            'message' => 'Photo approuvée avec succès.',
            'data' => $photo->load('photographer:id,first_name,last_name,email'),
        ]);
    }

    /**
     * @OA\Put(
     *     path="/api/admin/photos/{photo}/reject",
     *     operationId="rejectAdminPhoto",
     *     tags={"Admin - Photo Moderation"},
     *     summary="Reject a photo",
     *     description="Reject a pending photo with optional rejection reason. The photo's moderation status is set to 'rejected' and visibility is changed to 'private'. Requires admin role.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="photo",
     *         in="path",
     *         description="Photo UUID",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid", example="9d445a1c-85c5-4b6d-9c38-99a4915d6dac")
     *     ),
     *     @OA\RequestBody(
     *         required=false,
     *         @OA\JsonContent(
     *             @OA\Property(property="rejection_reason", type="string", maxLength=500, example="Image quality does not meet platform standards", description="Reason for rejection")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Photo rejected successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Photo rejetée avec succès."),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Photo already rejected",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Cette photo est déjà rejetée.")
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
     *         description="Photo not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Photo introuvable")
     *         )
     *     )
     * )
     */
    public function reject(Request $request, Photo $photo): JsonResponse
    {
        $request->validate([
            'rejection_reason' => 'nullable|string|max:500',
        ]);

        if ($photo->status === 'rejected') {
            return response()->json([
                'success' => false,
                'message' => 'Cette photo est déjà rejetée.',
            ], 400);
        }

        DB::transaction(function () use ($photo, $request) {
            $photo->update([
                'status' => 'rejected',
                'moderated_at' => now(),
                'rejection_reason' => $request->input('rejection_reason'),
                'is_public' => false,
            ]);

            // Envoyer notification au photographe (via Job)
            // dispatch(new PhotoRejectedNotification($photo));
        });

        return response()->json([
            'success' => true,
            'message' => 'Photo rejetée avec succès.',
            'data' => $photo->load('photographer:id,first_name,last_name,email'),
        ]);
    }

    /**
     * @OA\Put(
     *     path="/api/admin/photos/{photo}/toggle-featured",
     *     operationId="toggleAdminPhotoFeatured",
     *     tags={"Admin - Photo Moderation"},
     *     summary="Toggle photo featured status",
     *     description="Feature or unfeature an approved photo. Only approved photos can be featured. Requires admin role.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="photo",
     *         in="path",
     *         description="Photo UUID",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid", example="9d445a1c-85c5-4b6d-9c38-99a4915d6dac")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Photo featured status toggled successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Photo mise en avant avec succès."),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Photo not approved",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Seules les photos approuvées peuvent être mises en avant.")
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
     *         description="Photo not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Photo introuvable")
     *         )
     *     )
     * )
     */
    public function toggleFeatured(Photo $photo): JsonResponse
    {
        if ($photo->status !== 'approved') {
            return response()->json([
                'success' => false,
                'message' => 'Seules les photos approuvées peuvent être mises en avant.',
            ], 400);
        }

        $photo->update([
            'featured' => !$photo->featured,
        ]);

        $message = $photo->featured
            ? 'Photo mise en avant avec succès.'
            : 'Photo retirée des photos en avant.';

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $photo,
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/admin/photos/{photo}",
     *     operationId="deleteAdminPhoto",
     *     tags={"Admin - Photo Moderation"},
     *     summary="Delete a photo",
     *     description="Permanently delete a photo and its associated files from storage. This action cannot be undone. Requires admin role.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="photo",
     *         in="path",
     *         description="Photo UUID",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid", example="9d445a1c-85c5-4b6d-9c38-99a4915d6dac")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Photo deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Photo supprimée avec succès.")
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
     *         description="Photo not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Photo introuvable")
     *         )
     *     )
     * )
     */
    public function destroy(Photo $photo): JsonResponse
    {
        DB::transaction(function () use ($photo) {
            // Supprimer les fichiers du storage
            // $this->storageService->deletePhoto($photo);

            // Supprimer la photo
            $photo->delete();
        });

        return response()->json([
            'success' => true,
            'message' => 'Photo supprimée avec succès.',
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/admin/photos/bulk-approve",
     *     operationId="bulkApproveAdminPhotos",
     *     tags={"Admin - Photo Moderation"},
     *     summary="Bulk approve photos",
     *     description="Approve multiple pending photos at once. Only pending photos will be updated. All specified photos will have their moderation status set to 'approved' and visibility changed to 'public'. Requires admin role.",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"photo_ids"},
     *             @OA\Property(
     *                 property="photo_ids",
     *                 type="array",
     *                 description="Array of photo UUIDs to approve",
     *                 @OA\Items(type="string", format="uuid"),
     *                 example={"9d445a1c-85c5-4b6d-9c38-99a4915d6dac", "9d445a1c-85c5-4b6d-9c38-99a4915d6dad"}
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Photos approved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="5 photo(s) approuvée(s) avec succès.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Validation failed"),
     *             @OA\Property(property="errors", type="object")
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
    public function bulkApprove(Request $request): JsonResponse
    {
        $request->validate([
            'photo_ids' => 'required|array',
            'photo_ids.*' => 'required|uuid|exists:photos,id',
        ]);

        $photoIds = $request->input('photo_ids');

        $updated = Photo::whereIn('id', $photoIds)
            ->where('status', 'pending')
            ->update([
                'status' => 'approved',
                'moderated_at' => now(),
                'is_public' => true,
            ]);

        return response()->json([
            'success' => true,
            'message' => "$updated photo(s) approuvée(s) avec succès.",
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/admin/photos/bulk-reject",
     *     operationId="bulkRejectAdminPhotos",
     *     tags={"Admin - Photo Moderation"},
     *     summary="Bulk reject photos",
     *     description="Reject multiple pending photos at once with optional rejection reason. Only pending photos will be updated. All specified photos will have their moderation status set to 'rejected' and visibility changed to 'private'. Requires admin role.",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"photo_ids"},
     *             @OA\Property(
     *                 property="photo_ids",
     *                 type="array",
     *                 description="Array of photo UUIDs to reject",
     *                 @OA\Items(type="string", format="uuid"),
     *                 example={"9d445a1c-85c5-4b6d-9c38-99a4915d6dac", "9d445a1c-85c5-4b6d-9c38-99a4915d6dad"}
     *             ),
     *             @OA\Property(property="rejection_reason", type="string", maxLength=500, example="Images do not meet quality standards", description="Reason for rejection applied to all photos")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Photos rejected successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="5 photo(s) rejetée(s) avec succès.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Validation failed"),
     *             @OA\Property(property="errors", type="object")
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
    public function bulkReject(Request $request): JsonResponse
    {
        $request->validate([
            'photo_ids' => 'required|array',
            'photo_ids.*' => 'required|uuid|exists:photos,id',
            'rejection_reason' => 'nullable|string|max:500',
        ]);

        $photoIds = $request->input('photo_ids');
        $reason = $request->input('rejection_reason');

        $updated = Photo::whereIn('id', $photoIds)
            ->where('status', 'pending')
            ->update([
                'status' => 'rejected',
                'moderated_at' => now(),
                'rejection_reason' => $reason,
                'is_public' => false,
            ]);

        return response()->json([
            'success' => true,
            'message' => "$updated photo(s) rejetée(s) avec succès.",
        ]);
    }
}
