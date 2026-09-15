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
use App\Services\FcmService;

class OrderController extends Controller
{
    // ═══════════════════════════════════════════════════════════
    // 🛍️ CRÉER UNE COMMANDE (déjà en place)
    // ═══════════════════════════════════════════════════════════
    public function store(Request $request)
    {
        try {
            Log::info('📦 Création commande - Début');

            $request->validate([
                'items'              => 'required|array|min:1',
                'items.*.product_id' => 'required|integer|exists:products,id',
                'items.*.quantity'   => 'required|integer|min:1',
                'payment_method'     => 'nullable|string',
                'total'              => 'required|numeric|min:0',
            ]);

            $user = Auth::user();
            if (!$user) {
                return response()->json(['message' => 'Utilisateur non authentifié.'], 401);
            }

            $total = 0;
            $firstCompanyId = null;
            $itemsData = [];

            foreach ($request->items as $item) {
                $product = Product::find($item['product_id']);
                if (!$product) continue;

                if (!$firstCompanyId) {
                    $firstCompanyId = $product->company_id;
                }

                $unitPrice = $product->discount_price && (float) $product->discount_price > 0
                    ? (float) $product->discount_price
                    : (float) $product->price;

                $total += $unitPrice * $item['quantity'];

                $itemsData[] = [
                    'product'   => $product,
                    'unitPrice' => $unitPrice,
                    'quantity'  => $item['quantity'],
                ];
            }

            if (!$firstCompanyId) {
                return response()->json(['message' => 'Aucune boutique associée aux produits.'], 400);
            }

            // 3. Création de la commande (D'ABORD)
            $order = Order::create([
                'user_id'        => $user->id,
                'company_id'     => $firstCompanyId,
                'total'          => $total,
                'status'         => 'pending',
                'payment_method' => $request->payment_method ?? 'cash_on_delivery',
            ]);

            Log::info('✅ Commande créée ID: ' . $order->id);

            // 4. Enregistrement des items (APRÈS)
            foreach ($itemsData as $data) {
                OrderItem::create([
                    'order_id'   => $order->id,
                    'product_id' => $data['product']->id,
                    'quantity'   => $data['quantity'],
                    'price'      => $data['unitPrice'],
                ]);
            }

            // 5. Vider le panier
            Cart::where('user_id', $user->id)
                ->whereIn('product_id', collect($request->items)->pluck('product_id'))
                ->delete();

            // 6. Charger les relations
            $order->load('items.product', 'user');

            // 7. Trouver le vendeur (recherche robuste multi-fallback)
            $seller = null;

            // 7a. Par role dans la company
            $seller = \App\Models\User::where('company_id', $firstCompanyId)
                ->whereIn('role', ['seller', 'vendeur', 'admin', 'owner'])
                ->first();

            // 7b. Par user_id du premier produit
            if (!$seller) {
                $firstProduct = $itemsData[0]['product'] ?? null;
                if ($firstProduct && $firstProduct->user_id) {
                    $seller = \App\Models\User::find($firstProduct->user_id);
                }
            }

            // 7c. Par owner de la company
            if (!$seller) {
                $company = \App\Models\Company::find($firstCompanyId);
                if ($company && $company->user_id) {
                    $seller = \App\Models\User::find($company->user_id);
                }
            }

            // 7d. Dernier recours
            if (!$seller) {
                $seller = \App\Models\User::where('company_id', $firstCompanyId)->first();
            }

            Log::info('Vendeur trouve', [
                'seller_id' => $seller ? $seller->id : null,
                'has_fcm'   => $seller ? !empty($seller->fcm_token) : false,
            ]);

            // 8. Notifications au vendeur
            if ($seller) {
                try {
                    $fcm = new FcmService();
                    $fcm->notifyNewOrder($order, $seller, $user);
                    Log::info('✅ FCM envoyé au vendeur');
                } catch (\Exception $e) {
                    Log::warning('⚠️ Erreur FCM: ' . $e->getMessage());
                }

                try {
                    broadcast(new \App\Events\OrderPlaced($order));
                } catch (\Exception $e) {
                    Log::warning('⚠️ Erreur broadcast: ' . $e->getMessage());
                }
            }

            return response()->json([
                'message' => 'Commande créée avec succès 🎉',
                'order'   => $order->load('items.product'),
            ], 201);

        } catch (\Exception $e) {
            Log::error('❌ Erreur: ' . $e->getMessage());
            return response()->json(['message' => 'Erreur: ' . $e->getMessage()], 500);
        }
    }

    // ═══════════════════════════════════════════════════════════
    // 📋 LISTE DES COMMANDES
    // ═══════════════════════════════════════════════════════════
    public function index(Request $request)
    {
        $user = Auth::user();
        if (!$user) return response()->json(['message' => 'Non authentifié'], 401);

        $orders = Order::with(['items.product'])
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(['orders' => $orders]);
    }

    public function getShopOrders($companyId)
    {
        $user = Auth::user();
        if (!$user) return response()->json(['message' => 'Non authentifié'], 401);

        $orders = Order::whereHas('items.product', function ($query) use ($companyId) {
                $query->where('company_id', $companyId);
            })
            ->with(['items.product'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(['orders' => $orders]);
    }

    // ═══════════════════════════════════════════════════════════
    // 🔄 CHANGEMENT DE STATUT + NOTIFICATION
    // ═══════════════════════════════════════════════════════════
    public function updateStatus(Request $request, $id)
    {
        $order = Order::with(['user', 'items.product'])->findOrFail($id);

        $validated = $request->validate([
            'status' => 'required|in:pending,confirmed,delivered,cancelled,en_attente,confirmee,livree,annulee,expediee',
        ]);

        $map = [
            'pending'   => 'en_attente',
            'confirmed' => 'confirmee',
            'delivered' => 'livree',
            'cancelled' => 'annulee',
        ];

        $newStatus = $map[$validated['status']] ?? $validated['status'];
        $oldStatus = $order->status;

        // Ne rien faire si le statut n'a pas changé
        if ($oldStatus === $newStatus) {
            return response()->json([
                'message' => 'Statut inchangé',
                'order'   => $order,
            ]);
        }

        $order->status = $newStatus;
        $order->save();

        // 🔔 Notification à l'acheteur selon le nouveau statut
        try {
            $fcm = new FcmService();

            switch ($newStatus) {
                case 'confirmee':
                    $fcm->notifyOrderConfirmed($order);
                    break;
                case 'expediee':
                    $fcm->notifyOrderShipped($order);
                    break;
                case 'livree':
                    $fcm->notifyOrderDelivered($order);
                    break;
                case 'annulee':
                    $fcm->notifyOrderCancelled($order);
                    break;
            }

            Log::info('Statut commande change + FCM', [
                'order_id'   => $order->id,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
            ]);
        } catch (\Exception $e) {
            Log::warning('⚠️ Erreur FCM statut: ' . $e->getMessage());
        }

        return response()->json([
            'message' => 'Statut mis à jour',
            'order'   => $order,
        ]);
    }

    // ═══════════════════════════════════════════════════════════
    // 📊 STATISTIQUES
    // ═══════════════════════════════════════════════════════════
    public function getSoldProducts($shopId)
    {
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
                    $products[$product->id] = ['product' => $product, 'quantity' => 0, 'total' => 0];
                }
                $products[$product->id]['quantity'] += $item->quantity;
                $products[$product->id]['total'] += $item->quantity * $product->price;
            }
        }

        return response()->json(array_values($products));
    }

    public function getShopClients($shopId)
    {
        $orders = Order::with('user')->where('shop_id', $shopId)->get();

        $clients = [];
        foreach ($orders as $order) {
            $user = $order->user;
            if (!$user) continue;

            if (!isset($clients[$user->id])) {
                $clients[$user->id] = ['user' => $user, 'totalOrders' => 0, 'totalSpent' => 0];
            }
            $clients[$user->id]['totalOrders']++;
            $clients[$user->id]['totalSpent'] += $order->total;
        }

        return response()->json(array_values($clients));
    }

    // ═══════════════════════════════════════════════════════════
    // 🗑️ SUPPRESSION + NOTIFICATION
    // ═══════════════════════════════════════════════════════════
    public function destroy($id)
    {
        $user = Auth::user();
        if (!$user) return response()->json(['message' => 'Non authentifié'], 401);

        $order = Order::with(['user'])->findOrFail($id);

        if ($order->user_id !== $user->id) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        if (!in_array($order->status, ['cancelled', 'annulee'])) {
            return response()->json(['message' => 'Seules les commandes annulées peuvent être supprimées'], 400);
        }

        // 🔔 Notification à l'acheteur avant suppression
        try {
            $fcm = new FcmService();
            $fcm->notifyOrderDeleted($order);
            Log::info('FCM suppression commande', ['order_id' => $order->id]);
        } catch (\Exception $e) {
            Log::warning('⚠️ Erreur FCM suppression: ' . $e->getMessage());
        }

        $order->delete();

        return response()->json(['message' => 'Commande supprimée avec succès']);
    }
}
