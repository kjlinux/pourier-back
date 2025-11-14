<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Models\Category;

class CategoryController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/categories",
     *     tags={"Categories"},
     *     summary="Lister toutes les catégories",
     *     description="Récupérer toutes les catégories actives avec leurs sous-catégories",
     *     operationId="indexCategories",
     *     @OA\Response(
     *         response=200,
     *         description="Liste des catégories récupérée",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/Category")
     *             )
     *         )
     *     )
     * )
     */
    public function index()
    {
        $categories = Category::query()
            ->active()
            ->rootCategories()
            ->with('children')
            ->orderBy('display_order')
            ->get();

        return CategoryResource::collection($categories);
    }

    /**
     * @OA\Get(
     *     path="/api/categories/{slugOrId}",
     *     tags={"Categories"},
     *     summary="Afficher une catégorie spécifique",
     *     description="Récupérer une catégorie par son slug ou son ID",
     *     operationId="showCategory",
     *     @OA\Parameter(
     *         name="slugOrId",
     *         in="path",
     *         description="Slug ou ID (UUID) de la catégorie",
     *         required=true,
     *         @OA\Schema(type="string", example="paysages")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Catégorie trouvée",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", ref="#/components/schemas/Category")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Catégorie non trouvée",
     *         @OA\JsonContent(ref="#/components/schemas/NotFoundResponse")
     *     )
     * )
     */
    public function show($slugOrId)
    {
        $category = Category::query()
            ->active()
            ->where(function ($query) use ($slugOrId) {
                $query->where('slug', $slugOrId)
                    ->orWhere('id', $slugOrId);
            })
            ->with('children')
            ->firstOrFail();

        return new CategoryResource($category);
    }
}
