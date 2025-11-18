<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CartItemResource;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Photo;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use OpenApi\Annotations as OA;

class CartController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/cart",
     *     operationId="getCart",
     *     tags={"Cart"},
     *     summary="Get user's shopping cart",
     *     description="Retrieve all items in the current user's shopping cart with totals. Works for both authenticated and guest users.",
     *     @OA\Parameter(
     *         name="cart_session_id",
     *         in="query",
     *         description="Session ID for guest cart (optional, generated automatically if not provided)",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
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
     *                         @OA\Property(property="id", type="string", format="uuid"),
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
     *                 @OA\Property(property="items_count", type="integer", example=3),
     *                 @OA\Property(property="cart_session_id", type="string", description="Session ID for guest cart")
     *             )
     *         )
     *     )
     * )
     */
    public function index(Request $request)
    {
        $cart = $this->getOrCreateCart($request);

        return response()->json([
            'success' => true,
            'data' => [
                'items' => CartItemResource::collection($cart->items()->with(['photo.photographer'])->get()),
                'subtotal' => $cart->subtotal,
                'total' => $cart->total,
                'items_count' => $cart->items_count,
                'cart_session_id' => $cart->session_id,
            ],
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/cart/items",
     *     operationId="addCartItem",
     *     tags={"Cart"},
     *     summary="Add item to cart",
     *     description="Add a photo with specified license type to the shopping cart. Works for both authenticated and guest users.",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"photo_id", "license_type"},
     *             @OA\Property(property="photo_id", type="string", format="uuid", example="9d445a1c-85c5-4b6d-9c38-99a4915d6dac", description="UUID of the photo"),
     *             @OA\Property(property="license_type", type="string", enum={"standard", "extended"}, example="standard", description="License type for the photo"),
     *             @OA\Property(property="cart_session_id", type="string", description="Session ID for guest cart (optional)")
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
     *                 @OA\Property(property="items_count", type="integer", example=3),
     *                 @OA\Property(property="cart_session_id", type="string", description="Session ID for guest cart")
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
            'cart_session_id' => ['nullable', 'string'],
        ]);

        $photo = Photo::findOrFail($request->photo_id);

        // Vérifier que la photo est disponible à l'achat
        if (!$photo->is_public || $photo->status !== 'approved') {
            return response()->json([
                'success' => false,
                'message' => 'Cette photo n\'est pas disponible à l\'achat',
            ], 400);
        }

        $cart = $this->getOrCreateCart($request);

        // Vérifier si l'item existe déjà (la contrainte unique dans la DB empêchera les doublons)
        $existingItem = $cart->items()
            ->where('photo_id', $request->photo_id)
            ->where('license_type', $request->license_type)
            ->first();

        if ($existingItem) {
            return response()->json([
                'success' => false,
                'message' => 'Cet article est déjà dans votre panier',
            ], 400);
        }

        // Ajouter l'item
        $price = $request->license_type === 'standard' ? $photo->price_standard : $photo->price_extended;

        $cart->items()->create([
            'photo_id' => $photo->id,
            'license_type' => $request->license_type,
            'price' => $price,
        ]);

        // Recharger le cart avec les items
        $cart->load(['items.photo.photographer']);

        return response()->json([
            'success' => true,
            'message' => 'Article ajouté au panier',
            'data' => [
                'items' => CartItemResource::collection($cart->items),
                'subtotal' => $cart->subtotal,
                'total' => $cart->total,
                'items_count' => $cart->items_count,
                'cart_session_id' => $cart->session_id,
            ],
        ]);
    }

    /**
     * @OA\Put(
     *     path="/api/cart/items/{item}",
     *     operationId="updateCartItem",
     *     tags={"Cart"},
     *     summary="Update cart item",
     *     description="Update the license type of a cart item by its ID",
     *     @OA\Parameter(
     *         name="item",
     *         in="path",
     *         description="UUID of the cart item",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"license_type"},
     *             @OA\Property(property="license_type", type="string", enum={"standard", "extended"}, example="extended", description="New license type for the photo"),
     *             @OA\Property(property="cart_session_id", type="string", description="Session ID for guest cart (optional)")
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
     *         response=422,
     *         description="Validation error",
     *         ref="#/components/responses/ValidationErrorResponse"
     *     )
     * )
     */
    public function updateItem(Request $request, string $itemId)
    {
        $request->validate([
            'license_type' => ['required', 'in:standard,extended'],
            'cart_session_id' => ['nullable', 'string'],
        ]);

        $cart = $this->getOrCreateCart($request);

        $item = $cart->items()->with('photo')->findOrFail($itemId);

        $price = $request->license_type === 'standard'
            ? $item->photo->price_standard
            : $item->photo->price_extended;

        $item->update([
            'license_type' => $request->license_type,
            'price' => $price,
        ]);

        // Recharger le cart avec les items
        $cart->load(['items.photo.photographer']);

        return response()->json([
            'success' => true,
            'message' => 'Article mis à jour',
            'data' => [
                'items' => CartItemResource::collection($cart->items),
                'subtotal' => $cart->subtotal,
                'total' => $cart->total,
                'items_count' => $cart->items_count,
            ],
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/cart/items/{item}",
     *     operationId="removeCartItem",
     *     tags={"Cart"},
     *     summary="Remove item from cart",
     *     description="Remove a cart item by its ID",
     *     @OA\Parameter(
     *         name="item",
     *         in="path",
     *         description="UUID of the cart item to remove",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Parameter(
     *         name="cart_session_id",
     *         in="query",
     *         description="Session ID for guest cart (optional)",
     *         required=false,
     *         @OA\Schema(type="string")
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
     *     )
     * )
     */
    public function removeItem(Request $request, string $itemId)
    {
        $cart = $this->getOrCreateCart($request);

        $item = $cart->items()->findOrFail($itemId);
        $item->delete();

        // Recharger le cart avec les items
        $cart->load(['items.photo.photographer']);

        return response()->json([
            'success' => true,
            'message' => 'Article retiré du panier',
            'data' => [
                'items' => CartItemResource::collection($cart->items),
                'subtotal' => $cart->subtotal,
                'total' => $cart->total,
                'items_count' => $cart->items_count,
            ],
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/cart",
     *     operationId="clearCart",
     *     tags={"Cart"},
     *     summary="Clear entire cart",
     *     description="Remove all items from the shopping cart",
     *     @OA\Parameter(
     *         name="cart_session_id",
     *         in="query",
     *         description="Session ID for guest cart (optional)",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Cart cleared successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Panier vidé")
     *         )
     *     )
     * )
     */
    public function clear(Request $request)
    {
        $cart = $this->getOrCreateCart($request);
        $cart->items()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Panier vidé',
        ]);
    }

    /**
     * Get or create cart for the current user or session
     */
    private function getOrCreateCart(Request $request): Cart
    {
        $user = auth('api')->user();

        if ($user) {
            // Authenticated user - find or create cart by user_id
            $cart = Cart::firstOrCreate(
                ['user_id' => $user->id],
                ['session_id' => null]
            );
        } else {
            // Guest user - find or create cart by session_id
            $sessionId = $request->input('cart_session_id') ?? $request->query('cart_session_id');

            if (!$sessionId) {
                $sessionId = Str::uuid()->toString();
            }

            $cart = Cart::firstOrCreate(
                ['session_id' => $sessionId],
                ['user_id' => null]
            );
        }

        return $cart;
    }
}
