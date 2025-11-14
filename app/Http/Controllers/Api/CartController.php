<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Photo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use OpenApi\Annotations as OA;

class CartController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    /**
     * @OA\Get(
     *     path="/api/cart",
     *     tags={"Cart"},
     *     summary="Get user's shopping cart",
     *     description="Retrieve all items in the current user's shopping cart with totals",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Cart retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="items",
     *                     type="array",
     *                     @OA\Items(
     *                         @OA\Property(property="photo_id", type="string", format="uuid", example="9d445a1c-85c5-4b6d-9c38-99a4915d6dac"),
     *                         @OA\Property(property="photo_title", type="string", example="Sunset over Ouagadougou"),
     *                         @OA\Property(property="photo_thumbnail", type="string", example="https://example.com/photos/thumbnail.jpg"),
     *                         @OA\Property(property="photographer_id", type="string", format="uuid"),
     *                         @OA\Property(property="photographer_name", type="string", example="Jean Dupont"),
     *                         @OA\Property(property="license_type", type="string", enum={"standard", "extended"}, example="standard"),
     *                         @OA\Property(property="price", type="number", format="float", example=5000)
     *                     )
     *                 ),
     *                 @OA\Property(property="subtotal", type="number", format="float", example=15000),
     *                 @OA\Property(property="total", type="number", format="float", example=15000),
     *                 @OA\Property(property="items_count", type="integer", example=3)
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
    public function index(Request $request)
    {
        $cart = $this->getCart();

        return response()->json([
            'success' => true,
            'data' => [
                'items' => $cart['items'],
                'subtotal' => $cart['subtotal'],
                'total' => $cart['total'],
                'items_count' => count($cart['items']),
            ],
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/cart/items",
     *     tags={"Cart"},
     *     summary="Add item to cart",
     *     description="Add a photo with specified license type to the shopping cart",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"photo_id", "license_type"},
     *             @OA\Property(property="photo_id", type="string", format="uuid", example="9d445a1c-85c5-4b6d-9c38-99a4915d6dac", description="UUID of the photo"),
     *             @OA\Property(property="license_type", type="string", enum={"standard", "extended"}, example="standard", description="License type for the photo")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Item added to cart successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Article ajouté au panier"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="items", type="array", @OA\Items(type="object")),
     *                 @OA\Property(property="subtotal", type="number", format="float", example=15000),
     *                 @OA\Property(property="total", type="number", format="float", example=15000),
     *                 @OA\Property(property="items_count", type="integer", example=3)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Photo not available or already in cart",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Cette photo n'est pas disponible à l'achat")
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
    public function addItem(Request $request)
    {
        $request->validate([
            'photo_id' => ['required', 'exists:photos,id'],
            'license_type' => ['required', 'in:standard,extended'],
        ]);

        $photo = Photo::findOrFail($request->photo_id);

        // Vérifier que la photo est disponible à l'achat
        if (!$photo->is_public || $photo->status !== 'approved') {
            return response()->json([
                'success' => false,
                'message' => 'Cette photo n\'est pas disponible à l\'achat',
            ], 400);
        }

        $cart = $this->getCart();

        // Vérifier si l'item existe déjà
        $existingKey = null;
        foreach ($cart['items'] as $key => $item) {
            if ($item['photo_id'] === $request->photo_id && $item['license_type'] === $request->license_type) {
                $existingKey = $key;
                break;
            }
        }

        if ($existingKey !== null) {
            return response()->json([
                'success' => false,
                'message' => 'Cet article est déjà dans votre panier',
            ], 400);
        }

        // Ajouter l'item
        $price = $request->license_type === 'standard' ? $photo->price_standard : $photo->price_extended;

        $cart['items'][] = [
            'photo_id' => $photo->id,
            'photo_title' => $photo->title,
            'photo_thumbnail' => $photo->thumbnail_url,
            'photographer_id' => $photo->photographer_id,
            'photographer_name' => $photo->photographer->first_name . ' ' . $photo->photographer->last_name,
            'license_type' => $request->license_type,
            'price' => $price,
        ];

        $this->updateCartTotals($cart);
        $this->saveCart($cart);

        return response()->json([
            'success' => true,
            'message' => 'Article ajouté au panier',
            'data' => [
                'items' => $cart['items'],
                'subtotal' => $cart['subtotal'],
                'total' => $cart['total'],
                'items_count' => count($cart['items']),
            ],
        ]);
    }

    /**
     * @OA\Put(
     *     path="/api/cart/items/{index}",
     *     tags={"Cart"},
     *     summary="Update cart item",
     *     description="Update the license type of a cart item by its index",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="index",
     *         in="path",
     *         description="Index of the cart item (0-based)",
     *         required=true,
     *         @OA\Schema(type="integer", example=0)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"license_type"},
     *             @OA\Property(property="license_type", type="string", enum={"standard", "extended"}, example="extended", description="New license type for the photo")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Cart item updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Article mis à jour"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="items", type="array", @OA\Items(type="object")),
     *                 @OA\Property(property="subtotal", type="number", format="float", example=20000),
     *                 @OA\Property(property="total", type="number", format="float", example=20000)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Cart item not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Article non trouvé")
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
    public function updateItem(Request $request, $index)
    {
        $request->validate([
            'license_type' => ['required', 'in:standard,extended'],
        ]);

        $cart = $this->getCart();

        if (!isset($cart['items'][$index])) {
            return response()->json([
                'success' => false,
                'message' => 'Article non trouvé',
            ], 404);
        }

        $photo = Photo::findOrFail($cart['items'][$index]['photo_id']);
        $price = $request->license_type === 'standard' ? $photo->price_standard : $photo->price_extended;

        $cart['items'][$index]['license_type'] = $request->license_type;
        $cart['items'][$index]['price'] = $price;

        $this->updateCartTotals($cart);
        $this->saveCart($cart);

        return response()->json([
            'success' => true,
            'message' => 'Article mis à jour',
            'data' => [
                'items' => $cart['items'],
                'subtotal' => $cart['subtotal'],
                'total' => $cart['total'],
            ],
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/cart/items/{index}",
     *     tags={"Cart"},
     *     summary="Remove item from cart",
     *     description="Remove a cart item by its index",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="index",
     *         in="path",
     *         description="Index of the cart item to remove (0-based)",
     *         required=true,
     *         @OA\Schema(type="integer", example=0)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Item removed from cart successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Article retiré du panier"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="items", type="array", @OA\Items(type="object")),
     *                 @OA\Property(property="subtotal", type="number", format="float", example=10000),
     *                 @OA\Property(property="total", type="number", format="float", example=10000)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Cart item not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Article non trouvé")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         ref="#/components/responses/UnauthorizedResponse"
     *     )
     * )
     */
    public function removeItem($index)
    {
        $cart = $this->getCart();

        if (!isset($cart['items'][$index])) {
            return response()->json([
                'success' => false,
                'message' => 'Article non trouvé',
            ], 404);
        }

        array_splice($cart['items'], $index, 1);
        $cart['items'] = array_values($cart['items']); // Réindexer

        $this->updateCartTotals($cart);
        $this->saveCart($cart);

        return response()->json([
            'success' => true,
            'message' => 'Article retiré du panier',
            'data' => [
                'items' => $cart['items'],
                'subtotal' => $cart['subtotal'],
                'total' => $cart['total'],
            ],
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/cart",
     *     tags={"Cart"},
     *     summary="Clear entire cart",
     *     description="Remove all items from the shopping cart",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Cart cleared successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Panier vidé")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         ref="#/components/responses/UnauthorizedResponse"
     *     )
     * )
     */
    public function clear()
    {
        Session::forget('cart');

        return response()->json([
            'success' => true,
            'message' => 'Panier vidé',
        ]);
    }

    private function getCart(): array
    {
        return Session::get('cart', [
            'items' => [],
            'subtotal' => 0,
            'total' => 0,
        ]);
    }

    private function saveCart(array $cart): void
    {
        Session::put('cart', $cart);
    }

    private function updateCartTotals(array &$cart): void
    {
        $subtotal = array_sum(array_column($cart['items'], 'price'));
        $cart['subtotal'] = $subtotal;
        $cart['total'] = $subtotal; // Pas de taxes pour l'instant
    }
}
