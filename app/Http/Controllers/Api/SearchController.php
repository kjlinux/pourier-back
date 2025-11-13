<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Photo\SearchPhotoRequest;
use App\Http\Resources\PhotoResource;
use App\Models\Photo;

class SearchController extends Controller
{
    public function searchPhotos(SearchPhotoRequest $request)
    {
        $query = Photo::query()
            ->with(['photographer', 'category'])
            ->approved()
            ->public();

        // Recherche par mots-clés (title, description, tags)
        if ($request->filled('query')) {
            $searchTerm = $request->query;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('title', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('description', 'LIKE', "%{$searchTerm}%")
                    ->orWhereJsonContains('tags', $searchTerm);
            });
        }

        // Filtre par catégories
        if ($request->filled('categories')) {
            $query->whereIn('category_id', $request->categories);
        }

        // Filtre par photographe
        if ($request->filled('photographer_id')) {
            $query->where('photographer_id', $request->photographer_id);
        }

        // Filtre par prix
        if ($request->filled('min_price')) {
            $query->where('price_standard', '>=', $request->min_price);
        }

        if ($request->filled('max_price')) {
            $query->where('price_standard', '<=', $request->max_price);
        }

        // Filtre par orientation
        if ($request->filled('orientation')) {
            $orientation = $request->orientation;
            $query->where(function ($q) use ($orientation) {
                if ($orientation === 'landscape') {
                    $q->whereRaw('width > height');
                } elseif ($orientation === 'portrait') {
                    $q->whereRaw('height > width');
                } elseif ($orientation === 'square') {
                    $q->whereRaw('width = height');
                }
            });
        }

        // Tri
        $sortBy = $request->get('sort_by', 'date');
        switch ($sortBy) {
            case 'popularity':
                $query->orderByDesc('views_count')->orderByDesc('sales_count');
                break;
            case 'price_asc':
                $query->orderBy('price_standard');
                break;
            case 'price_desc':
                $query->orderByDesc('price_standard');
                break;
            case 'date':
            default:
                $query->latest('created_at');
                break;
        }

        $photos = $query->paginate($request->get('per_page', 20));

        return PhotoResource::collection($photos);
    }
}
