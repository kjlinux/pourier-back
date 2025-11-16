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
     * @OA\Post(
     *     path="/api/auth/register",
     *     tags={"Authentication"},
     *     summary="Créer un nouveau compte utilisateur",
     *     description="Permet à un nouvel utilisateur de s'inscrire sur la plateforme en tant qu'acheteur ou photographe",
     *     operationId="register",
     *     @OA\RequestBody(
     *         required=true,
     *         description="Données d'inscription",
     *         @OA\JsonContent(
     *             required={"first_name", "last_name", "email", "password", "password_confirmation", "account_type"},
     *             @OA\Property(property="first_name", type="string", minLength=2, maxLength=50, example="Jean", description="Prénom de l'utilisateur"),
     *             @OA\Property(property="last_name", type="string", minLength=2, maxLength=50, example="Dupont", description="Nom de l'utilisateur"),
     *             @OA\Property(property="email", type="string", format="email", maxLength=255, example="jean.dupont@example.com", description="Adresse email (doit être unique)"),
     *             @OA\Property(property="password", type="string", format="password", minLength=8, example="SecurePass123!", description="Mot de passe (minimum 8 caractères, doit contenir lettres, chiffres et symboles)"),
     *             @OA\Property(property="password_confirmation", type="string", format="password", example="SecurePass123!", description="Confirmation du mot de passe"),
     *             @OA\Property(property="account_type", type="string", enum={"buyer", "photographer"}, example="buyer", description="Type de compte (buyer=acheteur, photographer=photographe)"),
     *             @OA\Property(property="phone", type="string", nullable=true, example="+226 70 12 34 56", description="Numéro de téléphone (format burkinabè optionnel)")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Inscription réussie",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Inscription réussie ! Bienvenue sur Pourier."),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="user", ref="#/components/schemas/User"),
     *                 @OA\Property(property="access_token", type="string", example="eyJ0eXAiOiJKV1QiLCJhbGc..."),
     *                 @OA\Property(property="token_type", type="string", example="bearer"),
     *                 @OA\Property(property="expires_in", type="integer", example=3600)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Erreur de validation",
     *         @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Erreur serveur",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
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
     * @OA\Post(
     *     path="/api/auth/login",
     *     tags={"Authentication"},
     *     summary="Se connecter à un compte existant",
     *     description="Authentifier un utilisateur et recevoir un token JWT pour les requêtes suivantes",
     *     operationId="login",
     *     @OA\RequestBody(
     *         required=true,
     *         description="Identifiants de connexion",
     *         @OA\JsonContent(
     *             required={"email", "password"},
     *             @OA\Property(property="email", type="string", format="email", example="jean.dupont@example.com", description="Adresse email du compte"),
     *             @OA\Property(property="password", type="string", format="password", example="password123", description="Mot de passe du compte"),
     *             @OA\Property(property="remember_me", type="boolean", example=false, description="Maintenir la session active plus longtemps")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Connexion réussie",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Connexion réussie !"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="user", ref="#/components/schemas/User"),
     *                 @OA\Property(property="access_token", type="string", example="eyJ0eXAiOiJKV1QiLCJhbGc..."),
     *                 @OA\Property(property="token_type", type="string", example="bearer"),
     *                 @OA\Property(property="expires_in", type="integer", example=3600)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Identifiants invalides",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Les identifiants fournis sont incorrects")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Erreur de validation",
     *         @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse")
     *     )
     * )
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
     * @OA\Post(
     *     path="/api/auth/logout",
     *     tags={"Authentication"},
     *     summary="Se déconnecter",
     *     description="Invalider le token JWT actuel de l'utilisateur",
     *     operationId="logout",
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Déconnexion réussie",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Déconnexion réussie !")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Non authentifié",
     *         @OA\JsonContent(ref="#/components/schemas/UnauthorizedResponse")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Erreur serveur",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
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
     * @OA\Post(
     *     path="/api/auth/refresh",
     *     tags={"Authentication"},
     *     summary="Rafraîchir le token JWT",
     *     description="Obtenir un nouveau token JWT en utilisant le token actuel (avant qu'il n'expire)",
     *     operationId="refreshToken",
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Token rafraîchi avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Token rafraîchi avec succès !"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="access_token", type="string", example="eyJ0eXAiOiJKV1QiLCJhbGc..."),
     *                 @OA\Property(property="token_type", type="string", example="bearer"),
     *                 @OA\Property(property="expires_in", type="integer", example=3600)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Token invalide ou expiré",
     *         @OA\JsonContent(ref="#/components/schemas/UnauthorizedResponse")
     *     )
     * )
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
     * @OA\Get(
     *     path="/api/auth/me",
     *     tags={"Authentication"},
     *     summary="Obtenir les informations de l'utilisateur connecté",
     *     description="Récupérer le profil complet de l'utilisateur actuellement authentifié",
     *     operationId="me",
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Informations utilisateur récupérées",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 ref="#/components/schemas/User"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Non authentifié",
     *         @OA\JsonContent(ref="#/components/schemas/UnauthorizedResponse")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Erreur serveur",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
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

    /**
     * Get user abilities (roles, permissions, and capability flags).
     *
     * @OA\Get(
     *     path="/api/auth/abilities",
     *     tags={"Authentication"},
     *     summary="Obtenir les capacités de l'utilisateur connecté",
     *     description="Récupérer les rôles, permissions et drapeaux de capacité pour l'utilisateur authentifié (utile pour le frontend)",
     *     operationId="abilities",
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Capacités utilisateur récupérées",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="account_type", type="string", example="photographer", description="Type de compte"),
     *                 @OA\Property(
     *                     property="roles",
     *                     type="array",
     *                     @OA\Items(type="string", example="photographer"),
     *                     description="Liste des rôles assignés"
     *                 ),
     *                 @OA\Property(
     *                     property="permissions",
     *                     type="array",
     *                     @OA\Items(type="string", example="upload-photos"),
     *                     description="Liste des permissions assignées"
     *                 ),
     *                 @OA\Property(property="photographer_status", type="string", nullable=true, example="approved", description="Statut du profil photographe (pending, approved, rejected, suspended)"),
     *                 @OA\Property(property="can_upload_photos", type="boolean", example=true, description="Peut uploader des photos"),
     *                 @OA\Property(property="can_moderate", type="boolean", example=false, description="Peut modérer du contenu"),
     *                 @OA\Property(property="can_manage_users", type="boolean", example=false, description="Peut gérer les utilisateurs"),
     *                 @OA\Property(property="is_verified", type="boolean", example=true, description="Email vérifié"),
     *                 @OA\Property(property="is_active", type="boolean", example=true, description="Compte actif")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Non authentifié",
     *         @OA\JsonContent(ref="#/components/schemas/UnauthorizedResponse")
     *     )
     * )
     *
     * @return JsonResponse
     */
    public function abilities(): JsonResponse
    {
        try {
            $user = auth()->user();

            // Load relationships if not already loaded
            if (!$user->relationLoaded('roles')) {
                $user->load(['roles', 'permissions', 'photographerProfile']);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'account_type' => $user->account_type,
                    'roles' => $user->getRoleNames(),
                    'permissions' => $user->getAllPermissions()->pluck('name'),
                    'photographer_status' => $user->getPhotographerStatus(),

                    // Capability flags for frontend convenience
                    'can_upload_photos' => $user->can('upload-photos') && $user->isApprovedPhotographer(),
                    'can_moderate' => $user->can('moderate-photos'),
                    'can_manage_users' => $user->can('view-users'),
                    'can_approve_withdrawals' => $user->can('approve-withdrawals'),
                    'can_view_platform_analytics' => $user->can('view-platform-analytics'),

                    // Account status
                    'is_verified' => $user->is_verified,
                    'is_active' => $user->is_active,
                    'is_approved_photographer' => $user->isApprovedPhotographer(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Impossible de récupérer les capacités utilisateur.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
