<?php

namespace App\Http\Controllers\Api\Photographer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Photo\StorePhotoRequest;
use App\Http\Requests\Photo\UpdatePhotoRequest;
use App\Http\Resources\PhotoResource;
use App\Jobs\ProcessPhotoUpload;
use App\Models\Photo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use OpenApi\Annotations as OA;

class PhotoController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    /**
     * @OA\Get(
     *     path="/api/photographer/photos",
     *     operationId="getPhotographerPhotos",
     *     tags={"Photographer - Photos"},
     *     summary="List photographer's photos",
     *     description="Get all photos uploaded by the authenticated photographer with pagination and category details",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Number of photos per page",
     *         required=false,
     *         @OA\Schema(type="integer", default=20, example=20)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Photos retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Photo")),
     *             @OA\Property(property="meta", ref="#/components/schemas/PaginationMeta"),
     *             @OA\Property(property="links", ref="#/components/schemas/PaginationLinks")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         ref="#/components/responses/UnauthorizedResponse"
     *     )
     * )
     */
    public function index(Request $request)
    {
        $photos = Photo::query()
            ->where('photographer_id', $request->user()->id)
            ->with(['category'])
            ->latest()
            ->paginate($request->get('per_page', 20));

        return PhotoResource::collection($photos);
    }

    /**
     * @OA\Post(
     *     path="/api/photographer/photos",
     *     operationId="storePhotographerPhotos",
     *     tags={"Photographer - Photos"},
     *     summary="Upload new photos",
     *     description="Upload one or multiple photos with metadata. Photos are processed asynchronously. Platform takes 20% commission on all sales.",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"photos", "category_id", "title", "price_standard", "price_extended"},
     *                 @OA\Property(
     *                     property="photos[]",
     *                     type="array",
     *                     @OA\Items(type="string", format="binary"),
     *                     description="One or more photo files to upload"
     *                 ),
     *                 @OA\Property(property="category_id", type="string", format="uuid", example="9d445a1c-85c5-4b6d-9c38-99a4915d6dac", description="Category UUID"),
     *                 @OA\Property(property="title", type="string", example="Sunset over Ouagadougou", description="Photo title"),
     *                 @OA\Property(property="description", type="string", example="Beautiful sunset captured in the capital of Burkina Faso", description="Photo description (optional)"),
     *                 @OA\Property(property="tags", type="string", example="sunset, landscape, burkina faso", description="Comma-separated tags"),
     *                 @OA\Property(property="price_standard", type="number", format="float", example=5000, description="Standard license price in FCFA"),
     *                 @OA\Property(property="price_extended", type="number", format="float", example=10000, description="Extended license price in FCFA"),
     *                 @OA\Property(property="location", type="string", example="Ouagadougou, Burkina Faso", description="Photo location (optional)")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Photos uploaded successfully and processing started",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="3 photo(s) uploadée(s) avec succès. Traitement en cours..."),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Photo"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         ref="#/components/responses/UnauthorizedResponse"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         ref="#/components/responses/ValidationErrorResponse"
     *     )
     * )
     */
    public function store(StorePhotoRequest $request)
    {
        $uploadedPhotos = [];

        foreach ($request->file('photos') as $file) {
            // Stocker temporairement le fichier
            $tempPath = $file->store('temp', 'local');

            // Créer l'entrée Photo dans la base
            $photo = Photo::create([
                'photographer_id' => $request->user()->id,
                'category_id' => $request->category_id,
                'title' => $request->title,
                'description' => $request->description,
                'tags' => array_map('trim', explode(',', $request->tags)),
                'price_standard' => $request->price_standard,
                'price_extended' => $request->price_extended,
                'location' => $request->location,
                'status' => 'pending',
                'is_public' => false,
            ]);

            // Dispatcher le job de traitement
            ProcessPhotoUpload::dispatch(
                $tempPath,
                $request->user()->id,
                [
                    'photo_id' => $photo->id,
                    'title' => $request->title,
                ]
            );

            $uploadedPhotos[] = $photo;
        }

        return response()->json([
            'success' => true,
            'message' => count($uploadedPhotos) . ' photo(s) uploadée(s) avec succès. Traitement en cours...',
            'data' => PhotoResource::collection($uploadedPhotos),
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/photographer/photos/{photo}",
     *     operationId="getPhotographerPhoto",
     *     tags={"Photographer - Photos"},
     *     summary="Get photo details",
     *     description="Retrieve detailed information about a specific photo owned by the photographer",
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
     *         description="Photo details retrieved successfully",
     *         @OA\JsonContent(ref="#/components/schemas/Photo")
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - user is not the photo owner",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Unauthorized action")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Photo not found",
     *         ref="#/components/responses/NotFoundResponse"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         ref="#/components/responses/UnauthorizedResponse"
     *     )
     * )
     */
    public function show(Photo $photo, Request $request)
    {
        $this->authorize('view', $photo);

        $photo->load(['category']);

        return new PhotoResource($photo);
    }

    /**
     * @OA\Put(
     *     path="/api/photographer/photos/{photo}",
     *     operationId="updatePhotographerPhotos",
     *     tags={"Photographer - Photos"},
     *     summary="Update photo",
     *     description="Update photo metadata (title, description, tags, pricing, etc.). Only the photo owner can update.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="photo",
     *         in="path",
     *         description="Photo UUID",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid", example="9d445a1c-85c5-4b6d-9c38-99a4915d6dac")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="title", type="string", example="Updated Sunset Photo"),
     *             @OA\Property(property="description", type="string", example="Updated description with more details"),
     *             @OA\Property(property="tags", type="string", example="sunset, nature, updated"),
     *             @OA\Property(property="category_id", type="string", format="uuid", example="9d445a1c-85c5-4b6d-9c38-99a4915d6dac"),
     *             @OA\Property(property="price_standard", type="number", format="float", example=6000, description="Standard license price in FCFA"),
     *             @OA\Property(property="price_extended", type="number", format="float", example=12000, description="Extended license price in FCFA"),
     *             @OA\Property(property="location", type="string", example="Bobo-Dioulasso, Burkina Faso"),
     *             @OA\Property(property="is_public", type="boolean", example=true, description="Whether photo is public")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Photo updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Photo mise à jour avec succès"),
     *             @OA\Property(property="data", ref="#/components/schemas/Photo")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - user is not the photo owner",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Unauthorized action")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Photo not found",
     *         ref="#/components/responses/NotFoundResponse"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         ref="#/components/responses/UnauthorizedResponse"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         ref="#/components/responses/ValidationErrorResponse"
     *     )
     * )
     */
    public function update(UpdatePhotoRequest $request, Photo $photo)
    {
        $photo->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Photo mise à jour avec succès',
            'data' => new PhotoResource($photo->fresh(['category'])),
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/photographer/photos/{photo}",
     *     operationId="deletePhotographerPhotos",
     *     tags={"Photographer - Photos"},
     *     summary="Delete photo",
     *     description="Permanently delete a photo. Only the photo owner can delete. Uploaded files on S3 may be preserved based on policy.",
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
     *             @OA\Property(property="message", type="string", example="Photo supprimée avec succès")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - user is not the photo owner",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Unauthorized action")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Photo not found",
     *         ref="#/components/responses/NotFoundResponse"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         ref="#/components/responses/UnauthorizedResponse"
     *     )
     * )
     */
    public function destroy(Photo $photo, Request $request)
    {
        $this->authorize('delete', $photo);

        // Supprimer les fichiers S3 (optionnel, selon la politique)
        // $storageService = app(\App\Services\StorageService::class);
        // $storageService->deleteFile($photo->original_url);
        // $storageService->deleteFile($photo->preview_url);
        // $storageService->deleteFile($photo->thumbnail_url);

        $photo->delete();

        return response()->json([
            'success' => true,
            'message' => 'Photo supprimée avec succès',
        ]);
    }
}
