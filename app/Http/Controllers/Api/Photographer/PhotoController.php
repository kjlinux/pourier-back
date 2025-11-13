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

class PhotoController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    public function index(Request $request)
    {
        $photos = Photo::query()
            ->where('photographer_id', $request->user()->id)
            ->with(['category'])
            ->latest()
            ->paginate($request->get('per_page', 20));

        return PhotoResource::collection($photos);
    }

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

    public function show(Photo $photo, Request $request)
    {
        $this->authorize('view', $photo);

        $photo->load(['category']);

        return new PhotoResource($photo);
    }

    public function update(UpdatePhotoRequest $request, Photo $photo)
    {
        $photo->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Photo mise à jour avec succès',
            'data' => new PhotoResource($photo->fresh(['category'])),
        ]);
    }

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
