<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;

class AuthController extends Controller
{
    protected AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * Register a new user.
     *
     * @param RegisterRequest $request
     * @return JsonResponse
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        try {
            $result = $this->authService->register($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Inscription réussie ! Bienvenue sur Pourier.',
                'data' => [
                    'user' => new UserResource($result['user']),
                    'access_token' => $result['token'],
                    'token_type' => $result['token_type'],
                    'expires_in' => $result['expires_in'],
                ],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors de l\'inscription.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Login user and return JWT token.
     *
     * @param LoginRequest $request
     * @return JsonResponse
     */
    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $result = $this->authService->login(
                $request->email,
                $request->password,
                $request->boolean('remember_me', false)
            );

            return response()->json([
                'success' => true,
                'message' => 'Connexion réussie !',
                'data' => [
                    'user' => new UserResource($result['user']),
                    'access_token' => $result['token'],
                    'token_type' => $result['token_type'],
                    'expires_in' => $result['expires_in'],
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 401);
        }
    }

    /**
     * Logout user (invalidate token).
     *
     * @return JsonResponse
     */
    public function logout(): JsonResponse
    {
        try {
            $this->authService->logout();

            return response()->json([
                'success' => true,
                'message' => 'Déconnexion réussie !',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors de la déconnexion.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Refresh JWT token.
     *
     * @return JsonResponse
     */
    public function refresh(): JsonResponse
    {
        try {
            $token = $this->authService->refresh();

            return response()->json([
                'success' => true,
                'message' => 'Token rafraîchi avec succès !',
                'data' => [
                    'access_token' => $token,
                    'token_type' => 'bearer',
                    'expires_in' => config('jwt.ttl') * 60,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Impossible de rafraîchir le token.',
                'error' => $e->getMessage(),
            ], 401);
        }
    }

    /**
     * Get authenticated user.
     *
     * @return JsonResponse
     */
    public function me(): JsonResponse
    {
        try {
            $user = $this->authService->me();

            return response()->json([
                'success' => true,
                'data' => new UserResource($user),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Impossible de récupérer les informations utilisateur.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
