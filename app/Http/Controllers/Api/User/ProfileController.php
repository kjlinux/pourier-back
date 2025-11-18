<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use OpenApi\Annotations as OA;

class ProfileController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/user/profile",
     *     operationId="getUserProfile",
     *     tags={"User Profile"},
     *     summary="Get current user profile",
     *     description="Retrieve the authenticated user's profile information including photographer profile if available",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Profile retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="string", format="uuid", example="9d445a1c-85c5-4b6d-9c38-99a4915d6dac"),
     *                 @OA\Property(property="first_name", type="string", example="Jean"),
     *                 @OA\Property(property="last_name", type="string", example="Dupont"),
     *                 @OA\Property(property="email", type="string", format="email", example="jean.dupont@example.com"),
     *                 @OA\Property(property="phone", type="string", nullable=true, example="+226 70 12 34 56"),
     *                 @OA\Property(property="bio", type="string", nullable=true, example="Passionate photographer from Burkina Faso"),
     *                 @OA\Property(property="avatar_url", type="string", nullable=true, example="https://example.com/avatars/user.jpg"),
     *                 @OA\Property(property="role", type="string", enum={"user", "photographer", "admin"}, example="user"),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-15T10:30:00.000000Z"),
     *                 @OA\Property(
     *                     property="photographer_profile",
     *                     type="object",
     *                     nullable=true,
     *                     @OA\Property(property="id", type="string", format="uuid"),
     *                     @OA\Property(property="status", type="string", enum={"pending", "approved", "rejected"}, example="approved"),
     *                     @OA\Property(property="portfolio_url", type="string", nullable=true),
     *                     @OA\Property(property="total_revenue", type="number", format="float", example=250000)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         ref="#/components/responses/UnauthorizedResponse"
     *     )
     * )
     */
    public function show(Request $request): JsonResponse
    {
        return response()->json(['success' => true, 'data' => $request->user()->load('photographerProfile')]);
    }

    /**
     * @OA\Put(
     *     path="/api/user/profile",
     *     operationId="updateUserProfile",
     *     tags={"User Profile"},
     *     summary="Update user profile",
     *     description="Update the authenticated user's profile information (first name, last name, phone, bio)",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="first_name", type="string", maxLength=50, example="Jean", description="User's first name"),
     *             @OA\Property(property="last_name", type="string", maxLength=50, example="Dupont", description="User's last name"),
     *             @OA\Property(property="phone", type="string", maxLength=20, nullable=true, example="+226 70 12 34 56", description="User's phone number"),
     *             @OA\Property(property="bio", type="string", maxLength=500, nullable=true, example="Passionate photographer from Burkina Faso", description="User biography")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Profile updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Profil mis à jour."),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="string", format="uuid"),
     *                 @OA\Property(property="first_name", type="string", example="Jean"),
     *                 @OA\Property(property="last_name", type="string", example="Dupont"),
     *                 @OA\Property(property="email", type="string", format="email", example="jean.dupont@example.com"),
     *                 @OA\Property(property="phone", type="string", nullable=true, example="+226 70 12 34 56"),
     *                 @OA\Property(property="bio", type="string", nullable=true, example="Passionate photographer from Burkina Faso")
     *             )
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

    /**
     * @OA\Post(
     *     path="/api/user/profile/avatar",
     *     operationId="avatarUserProfile",
     *     tags={"User Profile"},
     *     summary="Update user avatar",
     *     description="Upload and update the authenticated user's avatar image (max 2MB)",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"avatar"},
     *                 @OA\Property(
     *                     property="avatar",
     *                     type="string",
     *                     format="binary",
     *                     description="Avatar image file (JPEG, PNG, GIF, max 2MB)"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Avatar updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Avatar mis à jour.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         ref="#/components/responses/UnauthorizedResponse"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error - invalid image or file too large",
     *         ref="#/components/responses/ValidationErrorResponse"
     *     )
     * )
     */
    public function updateAvatar(Request $request): JsonResponse
    {
        $request->validate(['avatar' => 'required|image|max:2048']);

        // TODO: Upload avatar to S3 and update user
        // $path = $this->storageService->uploadAvatar($request->file('avatar'), $request->user());
        // $request->user()->update(['avatar_url' => $path]);

        return response()->json(['success' => true, 'message' => 'Avatar mis à jour.']);
    }

    /**
     * @OA\Put(
     *     path="/api/user/profile/password",
     *     operationId="passwordUserProfile",
     *     tags={"User Profile"},
     *     summary="Update user password",
     *     description="Change the authenticated user's password (requires current password verification)",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"current_password", "password", "password_confirmation"},
     *             @OA\Property(property="current_password", type="string", format="password", example="OldPassword123!", description="Current password for verification"),
     *             @OA\Property(property="password", type="string", format="password", minLength=8, example="NewPassword123!", description="New password (min 8 characters)"),
     *             @OA\Property(property="password_confirmation", type="string", format="password", example="NewPassword123!", description="Password confirmation (must match password)")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Password updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Mot de passe modifié.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Current password incorrect",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Mot de passe actuel incorrect.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         ref="#/components/responses/UnauthorizedResponse"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error - password too short or confirmation doesn't match",
     *         ref="#/components/responses/ValidationErrorResponse"
     *     )
     * )
     */
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
