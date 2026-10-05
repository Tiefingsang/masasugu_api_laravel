<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Company;
use App\Models\Cart;
use App\Models\User;
use App\Services\FcmService;
use App\Services\Payments\CommissionEngine;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    // ═══════════════════════════════════════════════════════════
    // 🛍️ CRÉER UNE COMMANDE
    // ═══════════════════════════════════════════════════════════
    public function store(Request $request)
    {
        try {
            Log::info('📦 Création commande - Début', [
                'user_id' => Auth::id(),
                'items_count' => count($request->items ?? []),
            ]);

            // ⚠️ IMPORTANT : On ne demande PLUS le total au client
            // → Le backend le calcule avec ses propres prix (sécurité)
            $request->validate([
                'items'              => 'required|array|min:1',
                'items.*.product_id' => 'required|integer|exists:products,id',
                'items.*.quantity'   => 'required|integer|min:1',
                'payment_method'     => 'nullable|string|max:50',
            ]);

            $user = Auth::user();
            if (!$user) {
                return response()->json(['message' => 'Utilisateur non authentifié.'], 401);
            }

            // ═══════════════════════════════════════════════
            // 1. Calculer les prix côté SERVEUR (sécurité)
            // ═══════════════════════════════════════════════
            $total = 0;
            $firstCompanyId = null;
            $itemsData = [];

            foreach ($request->items as $item) {
                $product = Product::find($item['product_id']);
                if (!$product) continue;

                if (!$firstCompanyId) {
                    $firstCompanyId = $product->company_id;
                }

                // ✅ Prix : discount_price > price (priorité au prix remisé)
                $unitPrice = ($product->discount_price && (float) $product->discount_price > 0)
                    ? (float) $product->discount_price
                    : (float) $product->price;

                $lineTotal = $unitPrice * $item['quantity'];
                $total += $lineTotal;

                // ✅ Trouver le seller_id pour cet item
                $sellerId = null;
                $company = Company::find($product->company_id);
                if ($company && $company->user_id) {
                    $sellerId = $company->user_id;
                } elseif ($product->user_id) {
                    $sellerId = $product->user_id;
                }

                $itemsData[] = [
                    'product'    => $product,
                    'unitPrice'  => $unitPrice,
                    'quantity'   => $item['quantity'],
                    'lineTotal'  => $lineTotal,
                    'company_id' => $product->company_id,
                    'seller_id'  => $sellerId,
                ];

                Log::info('📦 Item calculé', [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'quantity' => $item['quantity'],
                    'unit_price' => $unitPrice,
                    'line_total' => $lineTotal,
                    'has_discount' => ($product->discount_price && (float) $product->discount_price > 0),
                ]);
            }

            if (!$firstCompanyId || empty($itemsData)) {
                return response()->json(['message' => 'Aucune boutique associée aux produits.'], 400);
            }

            Log::info('✅ Total calculé côté serveur', ['total' => $total]);

            // ═══════════════════════════════════════════════
            // 2. Créer la commande dans une transaction
            // ═══════════════════════════════════════════════
            $order = DB::transaction(function () use ($user, $firstCompanyId, $total, $request, $itemsData) {

                // 2a. Commande principale
                $order = Order::create([
                    'user_id'        => $user->id,
                    'company_id'     => $firstCompanyId, // Rétrocompatibilité
                    'total'          => $total,
                    'subtotal'       => $total,
                    'status'         => 'pending',
                    'payment_status' => 'unpaid',
                    'payment_method' => $request->payment_method ?? 'pending',
                    'currency'       => 'XOF',
                    'country'        => $user->country ?? 'ML',
                ]);

                Log::info('✅ Commande créée', ['order_id' => $order->id]);

                // 2b. Items (chaque item avec son company_id + seller_id)
                foreach ($itemsData as $data) {
                    OrderItem::create([
                        'order_id'    => $order->id,
                        'product_id'  => $data['product']->id,
                        'company_id'  => $data['company_id'],
                        'seller_id'   => $data['seller_id'],
                        'quantity'    => $data['quantity'],
                        'price'       => $data['unitPrice'],
                        'status'      => 'pending',
                    ]);
                }

                Log::info('✅ Items créés', [
                    'order_id' => $order->id,
                    'items_count' => count($itemsData),
                ]);

                return $order;
            });

            // ═══════════════════════════════════════════════
            // 3. Calculer les commissions (via CommissionEngine)
            // ═══════════════════════════════════════════════
            try {
                $commissionEngine = new CommissionEngine();
                $commissionEngine->applyToOrder($order);

                Log::info('✅ Commissions calculées', [
                    'order_id' => $order->id,
                    'platform_fee' => $order->fresh()->platform_fee_total,
                    'gateway_fee' => $order->fresh()->gateway_fee_total,
                ]);
            } catch (\Exception $e) {
                Log::warning('⚠️ Erreur calcul commissions', [
                    'order_id' => $order->id,
                    'error' => $e->getMessage(),
                ]);
            }

            // ═══════════════════════════════════════════════
            // 4. Vider le panier
            // ═══════════════════════════════════════════════
            try {
                Cart::where('user_id', $user->id)
                    ->whereIn('product_id', collect($request->items)->pluck('product_id'))
                    ->delete();

                Log::info('✅ Panier vidé', ['user_id' => $user->id]);
            } catch (\Exception $e) {
                Log::warning('⚠️ Erreur vidage panier', ['error' => $e->getMessage()]);
            }

            // ═══════════════════════════════════════════════
            // 5. Recharger avec relations
            // ═══════════════════════════════════════════════
            $order->refresh();
            $order->load(['items.product', 'user']);

            // ═══════════════════════════════════════════════
            // 6. Notifier chaque vendeur concerné
            // ═══════════════════════════════════════════════
            $this->notifySellers($order);

            return response()->json([
                'message' => 'Commande créée avec succès 🎉',
                'order'   => $order->load('items.product'),
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('❌ Validation échouée', ['errors' => $e->errors()]);
            return response()->json([
                'message' => 'Données invalides',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('❌ Erreur création commande', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'message' => 'Erreur: ' . $e->getMessage(),
            ], 500);
        }
    }

    // ═══════════════════════════════════════════════════════════
    // 🔔 NOTIFIER LES VENDEURS
    // ═══════════════════════════════════════════════════════════
    protected function notifySellers(Order $order): void
    {
        try {
            // Grouper les items par seller_id
            $itemsBySeller = $order->items->groupBy('seller_id');

            Log::info('🔔 Notification vendeurs', [
                'order_id' => $order->id,
                'sellers_count' => $itemsBySeller->count(),
            ]);

            $fcm = new FcmService();

            foreach ($itemsBySeller as $sellerId => $items) {
                if (!$sellerId) continue;

                $seller = User::find($sellerId);
                if (!$seller) continue;

                // FCM notification
                try {
                    $fcm->notifyNewOrder($order, $seller, $order->user);
                    Log::info('✅ FCM envoyé', ['seller_id' => $sellerId]);
                } catch (\Exception $e) {
                    Log::warning('⚠️ Erreur FCM', [
                        'seller_id' => $sellerId,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // Broadcast global
            try {
                broadcast(new \App\Events\OrderPlaced($order));
            } catch (\Exception $e) {
                Log::warning('⚠️ Erreur broadcast', ['error' => $e->getMessage()]);
            }

        } catch (\Exception $e) {
            Log::warning('⚠️ Erreur notifications', ['error' => $e->getMessage()]);
        }
    }

    // ═══════════════════════════════════════════════════════════
    // 📋 LISTE DES COMMANDES (acheteur)
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

    // ═══════════════════════════════════════════════════════════
    // 📋 COMMANDES D'UNE BOUTIQUE (vendeur)
    // ═══════════════════════════════════════════════════════════
    public function getShopOrders($companyId)
    {
        $user = Auth::user();
        if (!$user) return response()->json(['message' => 'Non authentifié'], 401);

        $orders = Order::whereHas('items', function ($query) use ($companyId) {
                $query->where('company_id', $companyId);
            })
            ->with(['items.product', 'user'])
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

        // Mapping FR → EN
        $map = [
            'pending'   => 'pending',
            'confirmed' => 'confirmed',
            'delivered' => 'delivered',
            'cancelled' => 'cancelled',
            'en_attente' => 'pending',
            'confirmee'  => 'confirmed',
            'livree'     => 'delivered',
            'annulee'    => 'cancelled',
            'expediee'   => 'shipped',
        ];

        $newStatus = $map[$validated['status']] ?? $validated['status'];
        $oldStatus = $order->status;

        if ($oldStatus === $newStatus) {
            return response()->json([
                'message' => 'Statut inchangé',
                'order'   => $order,
            ]);
        }

        $order->status = $newStatus;
        $order->save();

        // 🔔 Notification FCM selon le nouveau statut
        try {
            $fcm = new FcmService();

            switch ($newStatus) {
                case 'confirmed':
                    $fcm->notifyOrderConfirmed($order);
                    break;
                case 'shipped':
                    $fcm->notifyOrderShipped($order);
                    break;
                case 'delivered':
                    $fcm->notifyOrderDelivered($order);
                    break;
                case 'cancelled':
                    $fcm->notifyOrderCancelled($order);
                    break;
            }

            Log::info('✅ Statut changé + FCM', [
                'order_id'   => $order->id,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
            ]);
        } catch (\Exception $e) {
            Log::warning('⚠️ Erreur FCM', ['error' => $e->getMessage()]);
        }

        return response()->json([
            'message' => 'Statut mis à jour',
            'order'   => $order->fresh(),
        ]);
    }

    // ═══════════════════════════════════════════════════════════
    // 📊 STATISTIQUES
    // ═══════════════════════════════════════════════════════════
    public function getSoldProducts($shopId)
    {
        $orders = Order::with('items.product')
            ->whereHas('items', function ($q) use ($shopId) {
                $q->where('company_id', $shopId);
            })
            ->whereIn('status', ['delivered', 'confirmed', 'livree', 'confirmee'])
            ->get();

        $products = [];
        foreach ($orders as $order) {
            foreach ($order->items as $item) {
                if ($item->company_id != $shopId) continue;

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
                $products[$product->id]['total'] += $item->quantity * $item->price;
            }
        }

        return response()->json(array_values($products));
    }

    public function getShopClients($shopId)
    {
        $orders = Order::with('user')
            ->whereHas('items', function ($q) use ($shopId) {
                $q->where('company_id', $shopId);
            })
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
            return response()->json([
                'message' => 'Seules les commandes annulées peuvent être supprimées',
            ], 400);
        }

        try {
            $fcm = new FcmService();
            $fcm->notifyOrderDeleted($order);
        } catch (\Exception $e) {
            Log::warning('⚠️ Erreur FCM suppression', ['error' => $e->getMessage()]);
        }

        $order->delete();

        return response()->json(['message' => 'Commande supprimée avec succès']);
    }
}
