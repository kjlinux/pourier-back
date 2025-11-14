<?php

namespace App\Http\Controllers\Api\Schemas;

use OpenApi\Annotations as OA;

/**
 * @OA\Response(
 *     response="UnauthorizedResponse",
 *     description="Unauthorized - Authentication required",
 *     @OA\JsonContent(ref="#/components/schemas/UnauthorizedResponse")
 * )
 *
 * @OA\Response(
 *     response="ForbiddenResponse",
 *     description="Forbidden - Insufficient permissions",
 *     @OA\JsonContent(ref="#/components/schemas/ForbiddenResponse")
 * )
 *
 * @OA\Response(
 *     response="NotFoundResponse",
 *     description="Resource not found",
 *     @OA\JsonContent(ref="#/components/schemas/NotFoundResponse")
 * )
 *
 * @OA\Response(
 *     response="ValidationErrorResponse",
 *     description="Validation error",
 *     @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse")
 * )
 *
 * @OA\Schema(
 *     schema="SuccessResponse",
 *     title="Success Response",
 *     description="Réponse standard pour une opération réussie",
 *     @OA\Property(
 *         property="success",
 *         type="boolean",
 *         example=true,
 *         description="Indicateur de succès"
 *     ),
 *     @OA\Property(
 *         property="message",
 *         type="string",
 *         example="Opération réussie",
 *         description="Message de succès"
 *     ),
 *     @OA\Property(
 *         property="data",
 *         type="object",
 *         description="Données retournées"
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="ErrorResponse",
 *     title="Error Response",
 *     description="Réponse standard pour une erreur",
 *     @OA\Property(
 *         property="success",
 *         type="boolean",
 *         example=false,
 *         description="Indicateur d'échec"
 *     ),
 *     @OA\Property(
 *         property="message",
 *         type="string",
 *         example="Une erreur s'est produite",
 *         description="Message d'erreur"
 *     ),
 *     @OA\Property(
 *         property="error",
 *         type="string",
 *         example="Détails de l'erreur",
 *         description="Détails supplémentaires de l'erreur (optionnel)"
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="ValidationErrorResponse",
 *     title="Validation Error Response",
 *     description="Réponse pour une erreur de validation",
 *     @OA\Property(
 *         property="message",
 *         type="string",
 *         example="Les données fournies sont invalides"
 *     ),
 *     @OA\Property(
 *         property="errors",
 *         type="object",
 *         example={"email": {"L'email est invalide"}, "password": {"Le mot de passe est requis"}},
 *         description="Détails des erreurs de validation par champ"
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="UnauthorizedResponse",
 *     title="Unauthorized Response",
 *     description="Réponse pour une authentification requise",
 *     @OA\Property(
 *         property="message",
 *         type="string",
 *         example="Non authentifié"
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="ForbiddenResponse",
 *     title="Forbidden Response",
 *     description="Réponse pour un accès non autorisé",
 *     @OA\Property(
 *         property="success",
 *         type="boolean",
 *         example=false
 *     ),
 *     @OA\Property(
 *         property="message",
 *         type="string",
 *         example="Accès non autorisé"
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="NotFoundResponse",
 *     title="Not Found Response",
 *     description="Réponse pour une ressource non trouvée",
 *     @OA\Property(
 *         property="success",
 *         type="boolean",
 *         example=false
 *     ),
 *     @OA\Property(
 *         property="message",
 *         type="string",
 *         example="Ressource non trouvée"
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="PaginationMeta",
 *     title="Pagination Metadata",
 *     description="Métadonnées de pagination Laravel",
 *     @OA\Property(property="current_page", type="integer", example=1),
 *     @OA\Property(property="from", type="integer", example=1),
 *     @OA\Property(property="last_page", type="integer", example=10),
 *     @OA\Property(property="per_page", type="integer", example=15),
 *     @OA\Property(property="to", type="integer", example=15),
 *     @OA\Property(property="total", type="integer", example=150)
 * )
 *
 * @OA\Schema(
 *     schema="PaginationLinks",
 *     title="Pagination Links",
 *     description="Liens de pagination Laravel",
 *     @OA\Property(property="first", type="string", example="http://api.pourier.com/api/photos?page=1"),
 *     @OA\Property(property="last", type="string", example="http://api.pourier.com/api/photos?page=10"),
 *     @OA\Property(property="prev", type="string", nullable=true, example=null),
 *     @OA\Property(property="next", type="string", example="http://api.pourier.com/api/photos?page=2")
 * )
 *
 * @OA\Schema(
 *     schema="User",
 *     title="User",
 *     description="Modèle utilisateur",
 *     @OA\Property(property="id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000"),
 *     @OA\Property(property="name", type="string", example="Jean Dupont"),
 *     @OA\Property(property="email", type="string", format="email", example="jean.dupont@example.com"),
 *     @OA\Property(property="account_type", type="string", enum={"user", "photographer", "admin"}, example="user"),
 *     @OA\Property(property="profile_picture", type="string", nullable=true, example="https://storage.pourier.com/profiles/profile.jpg"),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-15T10:30:00.000000Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2024-01-15T10:30:00.000000Z")
 * )
 *
 * @OA\Schema(
 *     schema="Category",
 *     title="Category",
 *     description="Catégorie de photos",
 *     @OA\Property(property="id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000"),
 *     @OA\Property(property="name", type="string", example="Paysages"),
 *     @OA\Property(property="slug", type="string", example="paysages"),
 *     @OA\Property(property="description", type="string", nullable=true, example="Photos de paysages magnifiques"),
 *     @OA\Property(property="icon", type="string", nullable=true, example="landscape"),
 *     @OA\Property(property="photos_count", type="integer", example=1250),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 *
 * @OA\Schema(
 *     schema="Photo",
 *     title="Photo",
 *     description="Modèle photo complet",
 *     @OA\Property(property="id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000"),
 *     @OA\Property(property="title", type="string", example="Coucher de soleil sur la plage"),
 *     @OA\Property(property="description", type="string", nullable=true, example="Un magnifique coucher de soleil capturé à Assinie"),
 *     @OA\Property(property="slug", type="string", example="coucher-de-soleil-sur-la-plage"),
 *     @OA\Property(
 *         property="tags",
 *         type="array",
 *         @OA\Items(type="string"),
 *         example={"plage", "coucher de soleil", "océan", "nature"}
 *     ),
 *     @OA\Property(property="preview_url", type="string", example="https://storage.pourier.com/photos/preview.jpg"),
 *     @OA\Property(property="thumbnail_url", type="string", example="https://storage.pourier.com/photos/thumbnail.jpg"),
 *     @OA\Property(property="watermarked_url", type="string", example="https://storage.pourier.com/photos/watermarked.jpg"),
 *     @OA\Property(property="width", type="integer", example=4000),
 *     @OA\Property(property="height", type="integer", example=3000),
 *     @OA\Property(property="file_size", type="integer", example=5242880, description="Taille en octets"),
 *     @OA\Property(property="format", type="string", example="jpg"),
 *     @OA\Property(
 *         property="exif_data",
 *         type="object",
 *         nullable=true,
 *         example={"camera": "Canon EOS 5D", "aperture": "f/2.8", "iso": "100", "shutter_speed": "1/500"}
 *     ),
 *     @OA\Property(
 *         property="color_palette",
 *         type="array",
 *         @OA\Items(type="string"),
 *         example={"#FF6B35", "#004E89", "#1A659E"}
 *     ),
 *     @OA\Property(property="location", type="string", nullable=true, example="Assinie, Côte d'Ivoire"),
 *     @OA\Property(property="price_standard", type="number", format="float", example=5000, description="Prix en FCFA"),
 *     @OA\Property(property="price_extended", type="number", format="float", example=15000, description="Prix en FCFA"),
 *     @OA\Property(property="status", type="string", enum={"pending", "approved", "rejected"}, example="approved"),
 *     @OA\Property(property="views_count", type="integer", example=1250),
 *     @OA\Property(property="downloads_count", type="integer", example=45),
 *     @OA\Property(property="favorites_count", type="integer", example=89),
 *     @OA\Property(property="is_featured", type="boolean", example=false),
 *     @OA\Property(property="category_id", type="string", format="uuid"),
 *     @OA\Property(property="photographer_id", type="string", format="uuid"),
 *     @OA\Property(
 *         property="photographer",
 *         ref="#/components/schemas/PhotographerProfile"
 *     ),
 *     @OA\Property(
 *         property="category",
 *         ref="#/components/schemas/Category"
 *     ),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 *
 * @OA\Schema(
 *     schema="PhotographerProfile",
 *     title="Photographer Profile",
 *     description="Profil public d'un photographe",
 *     @OA\Property(property="id", type="string", format="uuid"),
 *     @OA\Property(property="name", type="string", example="Marie Kouassi"),
 *     @OA\Property(property="profile_picture", type="string", nullable=true),
 *     @OA\Property(property="bio", type="string", nullable=true, example="Photographe professionnelle basée à Abidjan"),
 *     @OA\Property(property="photos_count", type="integer", example=125),
 *     @OA\Property(property="total_sales", type="integer", example=450)
 * )
 *
 * @OA\Schema(
 *     schema="OrderItem",
 *     title="Order Item",
 *     description="Article d'une commande",
 *     @OA\Property(property="id", type="string", format="uuid"),
 *     @OA\Property(property="photo_id", type="string", format="uuid"),
 *     @OA\Property(property="license_type", type="string", enum={"standard", "extended"}, example="standard"),
 *     @OA\Property(property="price", type="number", format="float", example=5000),
 *     @OA\Property(
 *         property="photo",
 *         ref="#/components/schemas/Photo"
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="Order",
 *     title="Order",
 *     description="Commande complète",
 *     @OA\Property(property="id", type="string", format="uuid"),
 *     @OA\Property(property="order_number", type="string", example="ORD-20240115-001"),
 *     @OA\Property(property="user_id", type="string", format="uuid"),
 *     @OA\Property(property="subtotal", type="number", format="float", example=15000),
 *     @OA\Property(property="commission", type="number", format="float", example=3000, description="20% de commission"),
 *     @OA\Property(property="total", type="number", format="float", example=15000),
 *     @OA\Property(property="payment_status", type="string", enum={"pending", "completed", "failed", "refunded"}, example="pending"),
 *     @OA\Property(property="payment_method", type="string", nullable=true, example="mobile_money"),
 *     @OA\Property(property="transaction_id", type="string", nullable=true, example="CPAY-123456789"),
 *     @OA\Property(property="invoice_number", type="string", nullable=true, example="INV-20240115-001"),
 *     @OA\Property(property="invoice_date", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="invoice_path", type="string", nullable=true),
 *     @OA\Property(
 *         property="items",
 *         type="array",
 *         @OA\Items(ref="#/components/schemas/OrderItem")
 *     ),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */
class Schemas
{
    // This class only contains OpenAPI schema annotations
}
