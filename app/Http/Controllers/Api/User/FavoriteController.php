<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\Photo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FavoriteController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $favorites = $request->user()->favorites()
            ->with('photographer:id,first_name,last_name')
            ->latest('favorites.created_at')
            ->paginate(20);

        return response()->json(['success' => true, 'data' => $favorites]);
    }

    public function store(Request $request, Photo $photo): JsonResponse
    {
        if ($request->user()->favorites()->where('photo_id', $photo->id)->exists()) {
            return response()->json(['success' => false, 'message' => 'Photo déjà dans les favoris.'], 400);
        }

        $request->user()->favorites()->attach($photo->id);

        return response()->json(['success' => true, 'message' => 'Photo ajoutée aux favoris.']);
    }

    public function destroy(Request $request, Photo $photo): JsonResponse
    {
        $request->user()->favorites()->detach($photo->id);

        return response()->json(['success' => true, 'message' => 'Photo retirée des favoris.']);
    }
}
