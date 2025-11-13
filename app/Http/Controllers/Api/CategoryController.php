<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Models\Category;

class CategoryController extends Controller
{
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
