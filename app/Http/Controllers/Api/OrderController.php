<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Cart;
use Illuminate\Support\Facades\Auth;


class OrderController extends Controller
{
    public function store(Request $request)
    {
        //  Validation des données reçues
        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'payment_method' => 'nullable|string',
            'total' => 'required|numeric|min:0',
        ]);

        //  Récupération de l'utilisateur connecté
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'message' => 'Utilisateur non authentifié.',
            ], 401);
        }

        //  Création de la commande
        $order = Order::create([
            'user_id' => $user->id,
            'total' => $request->total,
            'status' => 'pending',
            'payment_method' => $request->payment_method,
        ]);

        //  Ajout des produits à la commande
        foreach ($request->items as $item) {
            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
            ]);
        }

        Cart::where('user_id', $user->id)
            ->whereIn('product_id', collect($request->items)->pluck('product_id'))
            ->delete();

    

        return response()->json([
            'message' => 'Commande créée avec succès 🎉',
            'order' => $order->load('items.product'),
        ], 201);
    }
}
