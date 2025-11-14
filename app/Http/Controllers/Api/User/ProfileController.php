<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        return response()->json(['success' => true, 'data' => $request->user()->load('photographerProfile')]);
    }

    public function update(Request $request): JsonResponse
    {
        $request->validate([
            'first_name' => 'sometimes|string|max:50',
            'last_name' => 'sometimes|string|max:50',
            'phone' => 'sometimes|nullable|string|max:20',
            'bio' => 'sometimes|nullable|string|max:500',
        ]);

        $request->user()->update($request->only(['first_name', 'last_name', 'phone', 'bio']));

        return response()->json(['success' => true, 'message' => 'Profil mis à jour.', 'data' => $request->user()]);
    }

    public function updateAvatar(Request $request): JsonResponse
    {
        $request->validate(['avatar' => 'required|image|max:2048']);

        // TODO: Upload avatar to S3 and update user
        // $path = $this->storageService->uploadAvatar($request->file('avatar'), $request->user());
        // $request->user()->update(['avatar_url' => $path]);

        return response()->json(['success' => true, 'message' => 'Avatar mis à jour.']);
    }

    public function updatePassword(Request $request): JsonResponse
    {
        $request->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if (!Hash::check($request->current_password, $request->user()->password)) {
            return response()->json(['success' => false, 'message' => 'Mot de passe actuel incorrect.'], 400);
        }

        $request->user()->update(['password' => Hash::make($request->password)]);

        return response()->json(['success' => true, 'message' => 'Mot de passe modifié.']);
    }
}
