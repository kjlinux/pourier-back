<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Photo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PhotoModerationController extends Controller
{
    /**
     * Get pending photos for moderation
     */
    public function pending(Request $request): JsonResponse
    {
        $photos = Photo::with(['photographer:id,first_name,last_name,email', 'category:id,name'])
            ->where('moderation_status', 'pending')
            ->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $photos,
        ]);
    }

    /**
     * Get all photos with filtering
     */
    public function index(Request $request): JsonResponse
    {
        $query = Photo::with(['photographer:id,first_name,last_name,email', 'category:id,name']);

        // Filter by moderation status
        if ($request->has('status')) {
            $query->where('moderation_status', $request->input('status'));
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
     * Approve a photo
     */
    public function approve(Photo $photo): JsonResponse
    {
        if ($photo->moderation_status === 'approved') {
            return response()->json([
                'success' => false,
                'message' => 'Cette photo est déjà approuvée.',
            ], 400);
        }

        DB::transaction(function () use ($photo) {
            $photo->update([
                'moderation_status' => 'approved',
                'moderated_at' => now(),
                'visibility' => 'public', // Rendre la photo publique
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
     * Reject a photo
     */
    public function reject(Request $request, Photo $photo): JsonResponse
    {
        $request->validate([
            'rejection_reason' => 'nullable|string|max:500',
        ]);

        if ($photo->moderation_status === 'rejected') {
            return response()->json([
                'success' => false,
                'message' => 'Cette photo est déjà rejetée.',
            ], 400);
        }

        DB::transaction(function () use ($photo, $request) {
            $photo->update([
                'moderation_status' => 'rejected',
                'moderated_at' => now(),
                'rejection_reason' => $request->input('rejection_reason'),
                'visibility' => 'private',
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
     * Feature/unfeature a photo
     */
    public function toggleFeatured(Photo $photo): JsonResponse
    {
        if ($photo->moderation_status !== 'approved') {
            return response()->json([
                'success' => false,
                'message' => 'Seules les photos approuvées peuvent être mises en avant.',
            ], 400);
        }

        $photo->update([
            'is_featured' => !$photo->is_featured,
        ]);

        $message = $photo->is_featured
            ? 'Photo mise en avant avec succès.'
            : 'Photo retirée des photos en avant.';

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $photo,
        ]);
    }

    /**
     * Delete a photo (admin only)
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
     * Bulk approve photos
     */
    public function bulkApprove(Request $request): JsonResponse
    {
        $request->validate([
            'photo_ids' => 'required|array',
            'photo_ids.*' => 'required|uuid|exists:photos,id',
        ]);

        $photoIds = $request->input('photo_ids');

        $updated = Photo::whereIn('id', $photoIds)
            ->where('moderation_status', 'pending')
            ->update([
                'moderation_status' => 'approved',
                'moderated_at' => now(),
                'visibility' => 'public',
            ]);

        return response()->json([
            'success' => true,
            'message' => "$updated photo(s) approuvée(s) avec succès.",
        ]);
    }

    /**
     * Bulk reject photos
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
            ->where('moderation_status', 'pending')
            ->update([
                'moderation_status' => 'rejected',
                'moderated_at' => now(),
                'rejection_reason' => $reason,
                'visibility' => 'private',
            ]);

        return response()->json([
            'success' => true,
            'message' => "$updated photo(s) rejetée(s) avec succès.",
        ]);
    }
}
