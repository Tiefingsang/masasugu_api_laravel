<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Cart;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;
//use App\Events\ProductAddedToCart;
use App\Notifications\ProductAddedToCartNotification;
//use Illuminate\Notifications\Messages\BroadcastMessage;
use App\Models\User;
use Illuminate\Support\Facades\Log; 




class CartController extends Controller
{


        public function addToCart(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity'   => 'nullable|integer|min:1',
        ]);

        $user = Auth::user();
        $product = Product::findOrFail($validated['product_id']);

        // Récupérer la boutique du produit
        $companyId = $product->company_id;

        // Calcul du prix
        $unitPrice = $product->discount_price ?? $product->price;
        $quantity = $validated['quantity'] ?? 1;
        $totalPrice = $unitPrice * $quantity;

        // Vérifier si le produit est déjà dans le panier
        $cartItem = Cart::where('user_id', $user->id)
                        ->where('product_id', $product->id)
                        ->first();

        if ($cartItem) {
            $cartItem->quantity += $quantity;
            $cartItem->total_price = $cartItem->quantity * $unitPrice;
            $cartItem->save();
        } else {
            $cartItem = Cart::create([
                'user_id' => $user->id,
                'product_id' => $product->id,
                'company_id' => $companyId,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'total_price' => $totalPrice,
                'status' => 'pending',
            ]);
        }

        // ═══════════════════════════════════════════════
        // 🔔 NOTIFICATIONS AU VENDEUR
        // ═══════════════════════════════════════════════
        $seller = \App\Models\User::where('company_id', $companyId)
            ->whereIn('role', ['seller', 'vendeur', 'admin'])
            ->first();

        // Fallback : user_id du produit
        if (!$seller && $product->user_id) {
            $seller = \App\Models\User::find($product->user_id);
        }

        if ($seller) {
            // ─── 1. Laravel Notification (Reverb, app ouverte)
            try {
                $seller->notify(
                    new ProductAddedToCartNotification([
                        'product_id'   => $product->id,
                        'product_name' => $product->name,
                        'quantity'     => $quantity,
                    ])
                );
                Log::info('Notification Laravel envoyée au vendeur ID: ' . $seller->id);
            } catch (\Exception $e) {
                Log::warning('⚠️ Erreur Laravel Notif: ' . $e->getMessage());
            }

            // ─── 2. FCM (app fermée) ⭐ NOUVEAU
            try {
                $fcm = new \App\Services\FcmService();
                $fcm->sendToUser(
                    $seller,
                    '🛒 Nouveau panier',
                    $user->name . ' a ajouté : ' . $product->name,
                    [
                        'type'          => 'cart',
                        'product_id'    => (string) $product->id,
                        'product_name'  => (string) $product->name,
                        'product_image' => (string) ($product->main_image ?? ''),
                        'quantity'      => (string) $quantity,
                        'customer_name' => (string) $user->name,
                        'price'         => (string) $unitPrice,
                        'currency'      => (string) ($product->currency ?? 'XOF'),
                    ]
                );
                Log::info('✅ FCM panier envoyé au vendeur ID: ' . $seller->id);
            } catch (\Exception $e) {
                Log::warning('⚠️ Erreur FCM panier: ' . $e->getMessage());
            }
        }

        return response()->json([
            'message' => '✅ Produit ajouté au panier avec succès.',
            'data' => $cartItem->load('product')
        ], 201);
    }

    public function count(){
        $user = auth()->user();

        // si panier lié à l'utilisateur
        $count = Cart::where('user_id', $user->id)->count();

        return response()->json(['count' => $count]);
    }




    // Mettre à jour la quantité d’un article dans le panier

    public function updateQuantity(Request $request, $id){
        $validated = $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);

        $cartItem = Cart::where('id', $id)
                        ->where('user_id', Auth::id())
                        ->first();

        if (!$cartItem) {
            return response()->json(['error' => 'Article non trouvé dans le panier.'], 404);
        }

        $cartItem->update(['quantity' => $validated['quantity']]);

        return response()->json([
            'message' => 'Quantité mise à jour avec succès.',
            'data' => $cartItem->load('product'),
        ]);
    }

    // Supprimer un article du panier

    public function removeItem($id){
        $cartItem = Cart::where('id', $id)
                        ->where('user_id', Auth::id())
                        ->first();

        if (!$cartItem) {
            return response()->json(['error' => 'Article non trouvé.'], 404);
        }

        $cartItem->delete();

        return response()->json(['message' => 'Article supprimé du panier.']);
    }

    // Récupérer le panier de l’utilisateur

    public function getUserCart(){
        $cartItems = Cart::where('user_id', Auth::id())
                        ->with('product')
                        ->get();

        $total = $cartItems->sum(fn($item) => $item->product->price * $item->quantity);

        return response()->json([
            'cart' => $cartItems,
            'total' => $total,
        ]);
    }
}
