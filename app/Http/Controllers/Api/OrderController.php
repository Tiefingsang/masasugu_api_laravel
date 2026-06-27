<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Cart;
use App\Notifications\OrderCreatedNotification;
use App\Events\OrderCreated;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{

    public function store(Request $request){
        // Log pour déboguer
        Log::info('📦 Création commande - Données reçues:', $request->all());

        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'payment_method' => 'nullable|string',
            'total' => 'required|numeric|min:0',
        ]);

        $user = Auth::user();

        if (!$user) {
            Log::error('❌ Utilisateur non authentifié');
            return response()->json(['message' => 'Utilisateur non authentifié.'], 401);
        }

        Log::info('👤 Utilisateur ID:', ['user_id' => $user->id]);

        DB::beginTransaction();

        try {
            // Calcul du total + récupération du company_id
            $total = 0;
            $firstCompanyId = null;

            foreach ($request->items as $item) {
                $product = Product::find($item['product_id']);
                if ($product) {
                    if (!$firstCompanyId) {
                        $firstCompanyId = $product->company_id;
                    }
                    $price = $product->discount_price ?? $product->price;
                    $total += (float)$price * $item['quantity'];
                }
            }

            if (!$firstCompanyId) {
                Log::error('❌ Aucun company_id trouvé');
                return response()->json(['message' => 'Aucune boutique associée aux produits.'], 400);
            }

            Log::info('🏪 Company ID:', ['company_id' => $firstCompanyId]);

            // Création de la commande
            $order = Order::create([
                'user_id' => $user->id,
                'company_id' => $firstCompanyId,
                'total' => $total,
                'status' => 'pending',
                'payment_method' => $request->payment_method ?? 'cash_on_delivery',
            ]);

            Log::info('✅ Commande créée', ['order_id' => $order->id]);

            // Enregistrement des items
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

            // Suppression du panier
            Cart::where('user_id', $user->id)
                ->whereIn('product_id', collect($request->items)->pluck('product_id'))
                ->delete();

            Log::info('🗑️ Panier vidé');

            // 🔹 Création de la notification avec Laravel Notifiable
            try {
                // Récupérer le vendeur (propriétaire de la boutique)
                $seller = \App\Models\User::where('company_id', $firstCompanyId)
                    ->where('role', 'seller')
                    ->first();

                if ($seller) {
                    // Envoyer la notification via le système Laravel
                    $seller->notify(new OrderCreatedNotification($order));
                    Log::info('📢 Notification envoyée au vendeur', ['seller_id' => $seller->id]);

                    // Déclencher l'événement pour broadcast
                    try {
                        broadcast(new OrderCreated($order))->toOthers();
                        Log::info('📡 Événement OrderCreated broadcasté');
                    } catch (\Exception $e) {
                        Log::warning('⚠️ Erreur broadcast: ' . $e->getMessage());
                    }
                } else {
                    Log::warning('⚠️ Aucun vendeur trouvé pour company_id: ' . $firstCompanyId);
                }
            } catch (\Exception $e) {
                Log::warning('⚠️ Erreur notification: ' . $e->getMessage());
                // On continue même si la notification échoue
            }

            DB::commit();

            return response()->json([
                'message' => 'Commande créée avec succès 🎉',
                'order' => $order->load('items.product'),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('❌ Erreur transaction: ' . $e->getMessage());
            return response()->json([
                'message' => 'Erreur lors de la création de la commande',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function index(Request $request){
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

    public function getShopOrders($companyId)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json([
                'message' => 'Utilisateur non authentifié.',
            ], 401);
        }

        $orders = Order::whereHas('items.product', function ($query) use ($companyId) {
            $query->where('company_id', $companyId);
        })
        ->with(['items.product'])
        ->orderBy('created_at', 'desc')
        ->get();

        return response()->json(['orders' => $orders]);
    }

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

    public function getSoldProducts($shopId){
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
