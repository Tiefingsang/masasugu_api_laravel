<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Cart;

use Illuminate\Support\Facades\Auth;


class OrderController extends Controller
{
    /* public function store(Request $request)
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

        
       $total = 0;
        foreach ($request->items as $item) {
            $product = Product::find($item['product_id']);
            if ($product) {
                $price = $product->discount_price ?? $product->price;
                $total += (float)$price * $item['quantity'];
            }
        }

        // 🔹 Création de la commande avec le total calculé
        $order = Order::create([
            'user_id' => $user->id,
            'company_id' => $product->company_id, // ou $shop->id
            'total' => $total,
            'status' => 'pending',
            'payment_method' => $request->payment_method,
            
        ]);

        //  Ajout des produits à la commande
        foreach ($request->items as $item) {
            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
                'price' => $item['price'] ?? 0,
                
                
            ]);
        }

        Cart::where('user_id', $user->id)
            ->whereIn('product_id', collect($request->items)->pluck('product_id'))
            ->delete();

    

        return response()->json([
            'message' => 'Commande créée avec succès 🎉',
            'order' => $order->load('items.product'),
        ], 201);
    } */
   public function store(Request $request){
        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'payment_method' => 'nullable|string',
            'total' => 'required|numeric|min:0',
        ]);

        $user = Auth::user();

        if (!$user) {
            return response()->json(['message' => 'Utilisateur non authentifié.'], 401);
        }

        // 🔹 Calcul du total + récupération du company_id
        $total = 0;
        $firstCompanyId = null;

        foreach ($request->items as $item) {
            $product = Product::find($item['product_id']);
            if ($product) {
                if (!$firstCompanyId) {
                    $firstCompanyId = $product->company_id; // ✅ on le prend depuis le premier produit
                }

                $price = $product->discount_price ?? $product->price;
                $total += (float)$price * $item['quantity'];
            }
        }

        if (!$firstCompanyId) {
            return response()->json(['message' => 'Aucune boutique associée aux produits.'], 400);
        }

        // 🔹 Création de la commande
        $order = Order::create([
            'user_id' => $user->id,
            'company_id' => $firstCompanyId,
            'total' => $total,
            'status' => 'pending',
            'payment_method' => $request->payment_method,
        ]);

        $notif = Notification::create([
        'vendor_id' => $request->vendor_id,
        'type' => 'order',
        'message' => 'Nouvelle commande reçue',
        'data' => ['order_id' => $order->id],
        ]);

        broadcast(new OrderCreated($notif))->toOthers();

        // 🔹 Enregistrement des items
        foreach ($request->items as $item) {
            $product = Product::find($item['product_id']);
            if ($product) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'quantity' => $item['quantity'],
                    'price' => $product->discount_price ?? $product->price,
                ]);
            }
        }

        // 🔹 Suppression du panier
        Cart::where('user_id', $user->id)
            ->whereIn('product_id', collect($request->items)->pluck('product_id'))
            ->delete();

        return response()->json([
            'message' => 'Commande créée avec succès 🎉',
            'order' => $order->load('items.product'),
        ], 201);
    }



    public function index(Request $request){
        
        //  Récupération de l'utilisateur connecté
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'message' => 'Utilisateur non authentifié.',
            ], 401);
        }

        $orders = Order::with(['items.product'])
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'orders' => $orders,
        ]);
    }

    // Récupérer les commandes pour un magasin spécifique

     public function getShopOrders($companyId)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json([
                'message' => 'Utilisateur non authentifié.',
            ], 401);
        }

        // Récupère toutes les commandes contenant des produits appartenant à cette entreprise (company)
        $orders = Order::whereHas('items.product', function ($query) use ($companyId) {
            $query->where('company_id', $companyId);
        })
        ->with(['items.product'])
        ->orderBy('created_at', 'desc')
        ->get();

        return response()->json(['orders' => $orders]);
    }

    // Mettre à jour le statut d'une commande

    public function updateStatus(Request $request, $id){
        $order = Order::findOrFail($id);

        $validated = $request->validate([
        'status' => 'required|in:pending,confirmed,delivered,cancelled,en_attente,confirmee,livree,annulee',
     ]);

        $map = [
        'pending' => 'en_attente',
        'confirmed' => 'confirmee',
        'delivered' => 'livree',
        'cancelled' => 'annulee',
        ];

        $order->status = $map[$validated['status']] ?? $validated['status'];
        $order->save();


        return response()->json([
            'message' => 'Statut mis à jour avec succès',
            'order' => $order,
        ]);
    }

    // Récupérer les produits vendus pour un magasin spécifique 


    public function getSoldProducts($shopId)    {

        $user = Auth::user();
        if (!$user) {
            return response()->json([
                'message' => 'Utilisateur non authentifié.',
            ], 401);
        }

        $orders = Order::with('items.product')
            ->where('shop_id', $shopId)
            ->whereIn('status', ['livree', 'confirmee'])
            ->get();

        $products = [];

        foreach ($orders as $order) {
            foreach ($order->items as $item) {
                $product = $item->product;
                if (!$product) continue;

                if (!isset($products[$product->id])) {
                    $products[$product->id] = [
                        'product' => $product,
                        'quantity' => 0,
                        'total' => 0,
                    ];
                }

                $products[$product->id]['quantity'] += $item->quantity;
                $products[$product->id]['total'] += $item->quantity * $product->price;
            }
        }

        return response()->json(array_values($products));
    }

// Récupérer les clients pour un magasin spécifique

    public function getShopClients($shopId)
    {
        $orders = Order::with('user')
            ->where('shop_id', $shopId)
            ->get();

        $clients = [];

        foreach ($orders as $order) {
            $user = $order->user;
            if (!$user) continue;

            if (!isset($clients[$user->id])) {
                $clients[$user->id] = [
                    'user' => $user,
                    'totalOrders' => 0,
                    'totalSpent' => 0,
                ];
            }

            $clients[$user->id]['totalOrders']++;
            $clients[$user->id]['totalSpent'] += $order->total;
        }

        return response()->json(array_values($clients));
    }





    


}
