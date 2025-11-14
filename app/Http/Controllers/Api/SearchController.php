<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Photo\SearchPhotoRequest;
use App\Http\Resources\PhotoResource;
use App\Models\Photo;

class SearchController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/search/photos",
     *     tags={"Search"},
     *     summary="Rechercher des photos",
     *     description="Recherche avancée de photos avec filtres multiples (mots-clés, catégorie, prix, orientation, etc.)",
     *     operationId="searchPhotos",
     *     @OA\Parameter(
     *         name="query",
     *         in="query",
     *         description="Terme de recherche (recherche dans titre, description et tags)",
     *         required=false,
     *         @OA\Schema(type="string", example="coucher de soleil")
     *     ),
     *     @OA\Parameter(
     *         name="categories",
     *         in="query",
     *         description="IDs des catégories (séparés par virgule)",
     *         required=false,
     *         @OA\Schema(type="array", @OA\Items(type="string", format="uuid"))
     *     ),
     *     @OA\Parameter(
     *         name="photographer_id",
     *         in="query",
     *         description="ID du photographe",
     *         required=false,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Parameter(
     *         name="min_price",
     *         in="query",
     *         description="Prix minimum en FCFA",
     *         required=false,
     *         @OA\Schema(type="number", example=1000)
     *     ),
     *     @OA\Parameter(
     *         name="max_price",
     *         in="query",
     *         description="Prix maximum en FCFA",
     *         required=false,
     *         @OA\Schema(type="number", example=10000)
     *     ),
     *     @OA\Parameter(
     *         name="orientation",
     *         in="query",
     *         description="Orientation de la photo",
     *         required=false,
     *         @OA\Schema(type="string", enum={"landscape", "portrait", "square"})
     *     ),
     *     @OA\Parameter(
     *         name="sort_by",
     *         in="query",
     *         description="Critère de tri",
     *         required=false,
     *         @OA\Schema(type="string", enum={"date", "popularity", "price_asc", "price_desc"}, default="date")
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Nombre de résultats par page",
     *         required=false,
     *         @OA\Schema(type="integer", default=20, minimum=1, maximum=100)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Résultats de recherche",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/Photo")
     *             ),
     *             @OA\Property(property="meta", ref="#/components/schemas/PaginationMeta"),
     *             @OA\Property(property="links", ref="#/components/schemas/PaginationLinks")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Erreur de validation",
     *         @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse")
     *     )
     * )
     */
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
