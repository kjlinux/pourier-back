<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PhotoResource;
use App\Models\Photo;
use Illuminate\Http\Request;

class PhotoController extends Controller
{
    public function index(Request $request)
    {
        $photos = Photo::query()
            ->with(['photographer', 'category'])
            ->approved()
            ->public()
            ->latest()
            ->paginate($request->get('per_page', 20));

        return PhotoResource::collection($photos);
    }

    public function show(Photo $photo)
    {
        $this->authorize('view', $photo);

        $photo->load(['photographer', 'category']);

        return new PhotoResource($photo);
    }

    public function featured(Request $request)
    {
        $photos = Photo::query()
            ->with(['photographer', 'category'])
            ->approved()
            ->public()
            ->featured()
            ->latest()
            ->paginate($request->get('per_page', 10));

        return PhotoResource::collection($photos);
    }

    public function recent(Request $request)
    {
        $photos = Photo::query()
            ->with(['photographer', 'category'])
            ->approved()
            ->public()
            ->latest('created_at')
            ->limit($request->get('limit', 12))
            ->get();

        return PhotoResource::collection($photos);
    }

    public function popular(Request $request)
    {
        $photos = Photo::query()
            ->with(['photographer', 'category'])
            ->approved()
            ->public()
            ->orderByDesc('views_count')
            ->orderByDesc('sales_count')
            ->limit($request->get('limit', 12))
            ->get();

        return PhotoResource::collection($photos);
    }

    public function similar(Photo $photo, Request $request)
    {
        $similarPhotos = Photo::query()
            ->with(['photographer', 'category'])
            ->approved()
            ->public()
            ->where('id', '!=', $photo->id)
            ->where(function ($query) use ($photo) {
                $query->where('category_id', $photo->category_id)
                    ->orWhere('photographer_id', $photo->photographer_id);
            })
            ->inRandomOrder()
            ->limit($request->get('limit', 6))
            ->get();

        return PhotoResource::collection($similarPhotos);
    }
}
