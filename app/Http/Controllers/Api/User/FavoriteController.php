<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\Photo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Annotations as OA;

class FavoriteController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/user/favorites",
     *     operationId="getUserFavorites",
     *     tags={"Favorites"},
     *     summary="Get user's favorite photos",
     *     description="Retrieve all photos marked as favorite by the authenticated user with pagination (20 per page)",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number for pagination",
     *         required=false,
     *         @OA\Schema(type="integer", default=1, example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Favorites retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="data",
     *                     type="array",
     *                     @OA\Items(
     *                         @OA\Property(property="id", type="string", format="uuid", example="9d445a1c-85c5-4b6d-9c38-99a4915d6dac"),
     *                         @OA\Property(property="title", type="string", example="Sunset over Ouagadougou"),
     *                         @OA\Property(property="description", type="string", nullable=true, example="Beautiful sunset captured in downtown"),
     *                         @OA\Property(property="thumbnail_url", type="string", example="https://example.com/photos/thumbnail.jpg"),
     *                         @OA\Property(property="watermark_url", type="string", example="https://example.com/photos/watermark.jpg"),
     *                         @OA\Property(property="price_standard", type="number", format="float", example=5000),
     *                         @OA\Property(property="price_extended", type="number", format="float", example=10000),
     *                         @OA\Property(
     *                             property="photographer",
     *                             type="object",
     *                             @OA\Property(property="id", type="string", format="uuid"),
     *                             @OA\Property(property="first_name", type="string", example="Jean"),
     *                             @OA\Property(property="last_name", type="string", example="Dupont")
     *                         )
     *                     )
     *                 ),
     *                 @OA\Property(property="current_page", type="integer", example=1),
     *                 @OA\Property(property="per_page", type="integer", example=20),
     *                 @OA\Property(property="total", type="integer", example=45),
     *                 @OA\Property(property="last_page", type="integer", example=3)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         ref="#/components/responses/UnauthorizedResponse"
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $favorites = $request->user()->favorites()
            ->with('photographer:id,first_name,last_name')
            ->latest('favorites.created_at')
            ->paginate(20);

        return response()->json(['success' => true, 'data' => $favorites]);
    }

    /**
     * @OA\Post(
     *     path="/api/user/favorites/{photo}",
     *     operationId="storeUserFavorites",
     *     tags={"Favorites"},
     *     summary="Add photo to favorites",
     *     description="Mark a photo as favorite for the authenticated user",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="photo",
     *         in="path",
     *         description="Photo UUID to add to favorites",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid", example="9d445a1c-85c5-4b6d-9c38-99a4915d6dac")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Photo added to favorites successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Photo ajoutée aux favoris.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Photo already in favorites",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Photo déjà dans les favoris.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         ref="#/components/responses/UnauthorizedResponse"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Photo not found",
     *         ref="#/components/responses/NotFoundResponse"
     *     )
     * )
     */
    public function store(Request $request, Photo $photo): JsonResponse
    {
        if ($request->user()->favorites()->where('photo_id', $photo->id)->exists()) {
            return response()->json(['success' => false, 'message' => 'Photo déjà dans les favoris.'], 400);
        }

        $request->user()->favorites()->attach($photo->id);

        return response()->json(['success' => true, 'message' => 'Photo ajoutée aux favoris.']);
    }

    /**
     * @OA\Delete(
     *     path="/api/user/favorites/{photo}",
     *     operationId="deleteUserFavorites",
     *     tags={"Favorites"},
     *     summary="Remove photo from favorites",
     *     description="Unmark a photo as favorite for the authenticated user",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="photo",
     *         in="path",
     *         description="Photo UUID to remove from favorites",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid", example="9d445a1c-85c5-4b6d-9c38-99a4915d6dac")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Photo removed from favorites successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Photo retirée des favoris.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         ref="#/components/responses/UnauthorizedResponse"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Photo not found",
     *         ref="#/components/responses/NotFoundResponse"
     *     )
     * )
     */
    public function destroy(Request $request, Photo $photo): JsonResponse
    {
        $request->user()->favorites()->detach($photo->id);

        return response()->json(['success' => true, 'message' => 'Photo retirée des favoris.']);
    }
}
