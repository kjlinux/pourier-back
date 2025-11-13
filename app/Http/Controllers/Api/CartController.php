<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Photo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class CartController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }

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
