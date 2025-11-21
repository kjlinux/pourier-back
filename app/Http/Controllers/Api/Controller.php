<?php

namespace App\Http\Controllers\Api;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

/**
 * @OA\Info(
 *     title="Pouire API",
 *     version="1.0.0",
 *     description="API REST pour la plateforme de vente de photos Pouire. Cette API permet aux utilisateurs d'acheter des photos, aux photographes de vendre leurs créations, et aux administrateurs de gérer la plateforme.",
 *     @OA\Contact(
 *         email="contact@pouire.com",
 *         name="Pouire Support"
 *     ),
 *     @OA\License(
 *         name="Proprietary",
 *         url=""
 *     )
 * )
 *
 * @OA\Server(
 *     url=L5_SWAGGER_CONST_HOST,
 *     description="Serveur API Pouire"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     description="Authentification JWT - Utilisez le token obtenu depuis /api/auth/login"
 * )
 *
 * @OA\Tag(
 *     name="Authentication",
 *     description="Endpoints d'authentification et de gestion de session"
 * )
 *
 * @OA\Tag(
 *     name="Photos",
 *     description="Endpoints publics pour parcourir et consulter les photos"
 * )
 *
 * @OA\Tag(
 *     name="Categories",
 *     description="Gestion des catégories de photos"
 * )
 *
 * @OA\Tag(
 *     name="Search",
 *     description="Recherche de photos par mots-clés, tags et filtres"
 * )
 *
 * @OA\Tag(
 *     name="Cart",
 *     description="Gestion du panier d'achat"
 * )
 *
 * @OA\Tag(
 *     name="Orders",
 *     description="Gestion des commandes et paiements"
 * )
 *
 * @OA\Tag(
 *     name="Downloads",
 *     description="Téléchargement de photos achetées et factures"
 * )
 *
 * @OA\Tag(
 *     name="User Profile",
 *     description="Gestion du profil utilisateur"
 * )
 *
 * @OA\Tag(
 *     name="Favorites",
 *     description="Gestion des photos favorites"
 * )
 *
 * @OA\Tag(
 *     name="Notifications",
 *     description="Gestion des notifications utilisateur"
 * )
 *
 * @OA\Tag(
 *     name="Photographer - Dashboard",
 *     description="Tableau de bord et statistiques photographe"
 * )
 *
 * @OA\Tag(
 *     name="Photographer - Photos",
 *     description="Gestion des photos par le photographe"
 * )
 *
 * @OA\Tag(
 *     name="Photographer - Revenue",
 *     description="Gestion des revenus et retraits"
 * )
 *
 * @OA\Tag(
 *     name="Photographer - Analytics",
 *     description="Analyses et statistiques détaillées"
 * )
 *
 * @OA\Tag(
 *     name="Admin - Dashboard",
 *     description="Tableau de bord administrateur"
 * )
 *
 * @OA\Tag(
 *     name="Admin - Moderation",
 *     description="Modération des photos"
 * )
 *
 * @OA\Tag(
 *     name="Admin - Photographers",
 *     description="Gestion des photographes"
 * )
 *
 * @OA\Tag(
 *     name="Admin - Users",
 *     description="Gestion des utilisateurs"
 * )
 *
 * @OA\Tag(
 *     name="Admin - Withdrawals",
 *     description="Gestion des demandes de retrait"
 * )
 *
 * @OA\Tag(
 *     name="Admin - Analytics",
 *     description="Analyses et rapports administrateur"
 * )
 *
 * @OA\Tag(
 *     name="Webhooks",
 *     description="Webhooks pour les intégrations tierces (CinetPay)"
 * )
 */
class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;
}
