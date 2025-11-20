<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PhotoResource;
use App\Models\Photo;
use Illuminate\Http\Request;

class PhotoController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/photos",
     *     tags={"Photos"},
     *     summary="Lister toutes les photos",
     *     description="Récupérer une liste paginée de toutes les photos approuvées et publiques",
     *     operationId="indexPhotos",
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Nombre de photos par page",
     *         required=false,
     *         @OA\Schema(type="integer", default=20, minimum=1, maximum=100)
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Numéro de page",
     *         required=false,
     *         @OA\Schema(type="integer", default=1, minimum=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Liste des photos récupérée avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/Photo")
     *             ),
     *             @OA\Property(property="meta", ref="#/components/schemas/PaginationMeta"),
     *             @OA\Property(property="links", ref="#/components/schemas/PaginationLinks")
     *         )
     *     )
     * )
     */
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

    /**
     * @OA\Get(
     *     path="/api/photos/{photo}",
     *     tags={"Photos"},
     *     summary="Afficher une photo spécifique",
     *     description="Récupérer les détails complets d'une photo par son ID",
     *     operationId="showPhoto",
     *     @OA\Parameter(
     *         name="photo",
     *         in="path",
     *         description="ID de la photo (UUID)",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Photo trouvée",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", ref="#/components/schemas/Photo")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Photo non trouvée",
     *         @OA\JsonContent(ref="#/components/schemas/NotFoundResponse")
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Accès non autorisé (photo non publique)",
     *         @OA\JsonContent(ref="#/components/schemas/ForbiddenResponse")
     *     )
     * )
     */
    public function show(Photo $photo)
    {
        $this->authorize('view', $photo);

        $photo->load(['photographer', 'category']);

        return new PhotoResource($photo);
    }

    /**
     * @OA\Get(
     *     path="/api/photos/featured",
     *     tags={"Photos"},
     *     summary="Lister les photos mises en avant",
     *     description="Récupérer les photos sélectionnées comme 'featured' par les administrateurs",
     *     operationId="featuredPhotos",
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Nombre de photos par page",
     *         required=false,
     *         @OA\Schema(type="integer", default=10, minimum=1, maximum=100)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Photos featured récupérées",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/Photo")
     *             ),
     *             @OA\Property(property="meta", ref="#/components/schemas/PaginationMeta"),
     *             @OA\Property(property="links", ref="#/components/schemas/PaginationLinks")
     *         )
     *     )
     * )
     */
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

    /**
     * @OA\Get(
     *     path="/api/photos/recent",
     *     tags={"Photos"},
     *     summary="Lister les photos récentes",
     *     description="Récupérer les photos les plus récemment ajoutées",
     *     operationId="recentPhotos",
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         description="Nombre maximum de photos à retourner",
     *         required=false,
     *         @OA\Schema(type="integer", default=12, minimum=1, maximum=50)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Photos récentes récupérées",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/Photo")
     *             )
     *         )
     *     )
     * )
     */
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

    /**
     * @OA\Get(
     *     path="/api/photos/popular",
     *     tags={"Photos"},
     *     summary="Lister les photos populaires",
     *     description="Récupérer les photos les plus populaires (par vues et ventes)",
     *     operationId="popularPhotos",
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         description="Nombre maximum de photos à retourner",
     *         required=false,
     *         @OA\Schema(type="integer", default=12, minimum=1, maximum=50)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Photos populaires récupérées",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/Photo")
     *             )
     *         )
     *     )
     * )
     */
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

    /**
     * @OA\Get(
     *     path="/api/photos/{photo}/similar",
     *     tags={"Photos"},
     *     summary="Trouver des photos similaires",
     *     description="Récupérer des photos similaires basées sur la catégorie et le photographe",
     *     operationId="similarPhotos",
     *     @OA\Parameter(
     *         name="photo",
     *         in="path",
     *         description="ID de la photo de référence (UUID)",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         description="Nombre maximum de photos similaires à retourner",
     *         required=false,
     *         @OA\Schema(type="integer", default=6, minimum=1, maximum=20)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Photos similaires trouvées",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/Photo")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Photo de référence non trouvée",
     *         @OA\JsonContent(ref="#/components/schemas/NotFoundResponse")
     *     )
     * )
     */
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

    /**
     * @OA\Post(
     *     path="/api/photos/{photo}/view",
     *     tags={"Photos"},
     *     summary="Enregistrer une vue de photo",
     *     description="Incrémenter le compteur de vues d'une photo lorsqu'un utilisateur la consulte",
     *     operationId="trackPhotoView",
     *     @OA\Parameter(
     *         name="photo",
     *         in="path",
     *         description="ID de la photo (UUID)",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Vue enregistrée avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="photo_id", type="string", format="uuid"),
     *                 @OA\Property(property="views_count", type="integer", example=124)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Photo non trouvée",
     *         @OA\JsonContent(ref="#/components/schemas/NotFoundResponse")
     *     )
     * )
     */
    public function trackView(Photo $photo)
    {
        $photo->incrementViews();

        return response()->json([
            'success' => true,
            'data' => [
                'photo_id' => $photo->id,
                'views_count' => $photo->views_count,
            ],
        ]);
    }
}
