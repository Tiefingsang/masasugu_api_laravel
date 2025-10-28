<?php
namespace App\Http\Controllers\Api;

use App\Models\Cart;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CartController extends Controller
{
    // Ajouter un produit au panier
    public function addToCart(Request $request){
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity'   => 'nullable|integer|min:1',
        ]);

        $user = Auth::user();

        // Vérifier si le produit est déjà dans le panier
        $cartItem = Cart::where('user_id', $user->id)
                        ->where('product_id', $validated['product_id'])
                        ->first();

        if ($cartItem) {
            // Si déjà présent, on met à jour la quantité
            $cartItem->quantity += $validated['quantity'] ?? 1;
            $cartItem->save();
        } else {
            // Sinon, on crée une nouvelle entrée
            $cartItem = Cart::create([
                'user_id' => $user->id,
                'product_id' => $validated['product_id'],
                'quantity' => $validated['quantity'] ?? 1,
            ]);
        }

        return response()->json([
            'message' => 'Produit ajouté au panier avec succès.',
            'data' => $cartItem->load('product')
        ], 201);
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
