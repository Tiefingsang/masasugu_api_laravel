<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Cart;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    public function store(Request $request){
        try {
            Log::info('📦 Création commande - Début');

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

            Log::info('👤 Utilisateur ID: ' . $user->id);

            $total = 0;
            $firstCompanyId = null;
            $itemsData = [];

            foreach ($request->items as $item) {
                $product = Product::find($item['product_id']);
                if ($product) {
                    if (!$firstCompanyId) {
                        $firstCompanyId = $product->company_id;
                    }

                    // Calcul du prix unitaire (avec remise si applicable)
                    $unitPrice = $product->discount_price && (float)$product->discount_price > 0
                        ? (float)$product->discount_price
                        : (float)$product->price;

                    $total += $unitPrice * $item['quantity'];

                    $itemsData[] = [
                        'product' => $product,
                        'unitPrice' => $unitPrice,
                        'quantity' => $item['quantity']
                    ];

                    Log::info('📦 Produit ajouté:', [
                        'product_id' => $product->id,
                        'name' => $product->name,
                        'unitPrice' => $unitPrice,
                        'quantity' => $item['quantity']
                    ]);
                }
            }

            if (!$firstCompanyId) {
                Log::error('❌ Aucun company_id trouvé');
                return response()->json(['message' => 'Aucune boutique associée aux produits.'], 400);
            }

            Log::info('🏪 Company ID: ' . $firstCompanyId);
            Log::info('💰 Total calculé: ' . $total);

            // Création de la commande
            $order = Order::create([
                'user_id' => $user->id,
                'company_id' => $firstCompanyId,
                'total' => $total,
                'status' => 'pending',
                'payment_method' => $request->payment_method ?? 'cash_on_delivery',
            ]);

            Log::info('✅ Commande créée ID: ' . $order->id);

            // Enregistrement des items
            foreach ($itemsData as $data) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $data['product']->id,
                    'quantity' => $data['quantity'],
                    'price' => $data['unitPrice'],
                ]);
            }

            Log::info('📦 Items enregistrés: ' . count($itemsData));

            // Suppression du panier
            Cart::where('user_id', $user->id)
                ->whereIn('product_id', collect($request->items)->pluck('product_id'))
                ->delete();

            Log::info('🗑️ Panier vidé');

            return response()->json([
                'message' => 'Commande créée avec succès 🎉',
                'order' => $order->load('items.product'),
            ], 201);

        } catch (\Exception $e) {
            Log::error('❌ Erreur: ' . $e->getMessage());
            Log::error('❌ Trace: ' . $e->getTraceAsString());

            return response()->json([
                'message' => 'Erreur: ' . $e->getMessage()
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

    public function destroy($id)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'Non authentifié'], 401);
        }

        $order = Order::findOrFail($id);

        if ($order->user_id !== $user->id) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        if (!in_array($order->status, ['cancelled', 'annulee'])) {
            return response()->json(['message' => 'Seules les commandes annulées peuvent être supprimées'], 400);
        }

        $order->delete();

        return response()->json(['message' => 'Commande supprimée avec succès']);
    }
}
