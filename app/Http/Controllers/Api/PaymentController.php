<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Exceptions\PaymentException;
use App\Models\Order;
use App\Models\PaymentGateway;
use App\Models\Transaction;
use App\Services\Payments\PaymentManager;
use App\Services\Payments\PaymentNotificationService;
use App\Services\Payments\CommissionEngine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * PaymentController — Gestion des paiements côté client
 *
 * Endpoints :
 * - GET  /api/payment-methods      → Moyens de paiement par pays
 * - POST /api/payments/initiate    → Initier un paiement
 * - GET  /api/payments/{id}/status → Vérifier le statut
 */
class PaymentController extends Controller
{
    protected PaymentManager $manager;
    protected PaymentNotificationService $notifier;
    protected CommissionEngine $commission;

    public function __construct()
    {
        $this->manager = new PaymentManager();
        $this->notifier = new PaymentNotificationService();
        $this->commission = new CommissionEngine();
    }

    /**
     * 💳 GET /api/payment-methods?country=ML
     *
     * Retourne les moyens de paiement disponibles pour un pays.
     * Utilisé par Flutter pour afficher la liste au checkout.
     */
    public function methods(Request $request)
    {
        $countryCode = $request->get('country', 'ML');

        $methods = $this->manager->getAvailableMethods($countryCode);

        return response()->json([
            'country' => $countryCode,
            'methods' => $methods->map(function ($gateway) {
                return [
                    'id' => $gateway->id,
                    'gateway' => $gateway->gateway,
                    'display_name' => $gateway->display_name,
                    'payment_method' => $gateway->payment_method,
                    'provider' => $gateway->provider,
                    'currencies' => $gateway->currencies,
                    'logo_url' => $gateway->logo_url,
                    'description' => $gateway->description,
                    'priority' => $gateway->priority,
                ];
            }),
            'total' => $methods->count(),
        ]);
    }

    /**
     * 🚀 POST /api/payments/initiate
     *
     * Initie un paiement pour une commande.
     *
     * Body:
     * {
     *     "order_id": 123,
     *     "gateway": "orange",
     *     "phone": "7701901164"  (optionnel)
     * }
     */
    public function initiate(Request $request)
    {
        $request->validate([
            'order_id' => 'required|exists:orders,id',
            'gateway' => 'required|string|max:50',
            'phone' => 'nullable|string|max:20',
        ]);

        $user = Auth::user();
        if (!$user) {
            return response()->json(['error' => 'Non authentifié'], 401);
        }

        // 1. Récupérer la commande
        $order = Order::with('items')->findOrFail($request->order_id);

        // 2. Vérifier que la commande appartient à l'utilisateur
        if ($order->user_id !== $user->id) {
            return response()->json(['error' => 'Non autorisé'], 403);
        }

        // 3. Vérifier que la commande n'est pas déjà payée
        if ($order->payment_status === 'paid') {
            return response()->json(['error' => 'Cette commande est déjà payée'], 400);
        }

        // 4. Calculer les commissions
        try {
            $this->commission->applyToOrder($order);
            $order->refresh();
        } catch (\Exception $e) {
            Log::warning('⚠️ Erreur calcul commissions', ['error' => $e->getMessage()]);
        }

        // 5. Créer la transaction AVANT d'appeler le gateway
        $transaction = Transaction::create([
            'order_id' => $order->id,
            'buyer_id' => $user->id,
            'seller_id' => $order->items->first()->seller_id ?? null,
            'company_id' => $order->company_id,
            'gateway' => $request->gateway,
            'payment_method' => $request->gateway === 'orange' ? 'mobile_money' : 'card',
            'provider' => $request->gateway,
            'country_code' => $order->country ?? 'ML',
            'currency' => $order->currency ?? 'XOF',
            'amount' => $order->total,
            'platform_fee' => $order->platform_fee_total ?? 0,
            'gateway_fee' => $order->gateway_fee_total ?? 0,
            'seller_amount' => $order->total - ($order->platform_fee_total ?? 0) - ($order->gateway_fee_total ?? 0),
            'status' => 'pending',
            'type' => 'payment',
        ]);

        Log::info('🚀 Initiation paiement', [
            'order_id' => $order->id,
            'transaction_id' => $transaction->id,
            'gateway' => $request->gateway,
        ]);

        // 6. Notifier le vendeur qu'un paiement est en cours
        $this->notifier->notifyPaymentInitiated($order, $request->gateway);

        // 7. Appeler le gateway
        try {
            $result = $this->manager->initiate($order, $request->gateway, [
                'phone' => $request->phone,
                'user_id' => $user->id,
            ]);

            // 8. Mettre à jour la transaction avec le pay_token
            $transaction->update([
                'gateway_transaction_id' => $result['transaction_id'] ?? null,
                'metadata' => array_merge($transaction->metadata ?? [], [
                    'pay_token' => $result['pay_token'] ?? null,
                    'notif_token' => $result['notif_token'] ?? null,
                    'gateway_response' => $result['raw'] ?? null,
                ]),
                'status' => 'processing',
            ]);

            return response()->json([
                'success' => true,
                'transaction_id' => $transaction->transaction_id,
                'payment_url' => $result['payment_url'] ?? null,
                'pay_token' => $result['pay_token'] ?? null,
                'message' => 'Paiement initié. Redirigez le client vers payment_url.',
            ], 200);

        } catch (PaymentException $e) {
            // Marquer la transaction comme échouée
            $transaction->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            // Notifier l'acheteur
            $this->notifier->notifyPaymentFailed($order, $e->getMessage());

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'context' => $e->getContext(),
            ], 400);
        } catch (\Exception $e) {
            Log::error('❌ Erreur paiement', ['error' => $e->getMessage()]);

            $transaction->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Erreur lors du paiement : ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 🔍 GET /api/payments/{transaction_id}/status
     *
     * Vérifie le statut d'un paiement.
     * Flutter appelle cet endpoint en polling après redirection.
     */
    public function status($transactionId)
    {
        $transaction = Transaction::where('transaction_id', $transactionId)->first();

        if (!$transaction) {
            return response()->json(['error' => 'Transaction introuvable'], 404);
        }

        $order = $transaction->order;

        return response()->json([
            'transaction' => [
                'transaction_id' => $transaction->transaction_id,
                'status' => $transaction->status,
                'amount' => $transaction->amount,
                'currency' => $transaction->currency,
                'gateway' => $transaction->gateway,
                'gateway_transaction_id' => $transaction->gateway_transaction_id,
                'paid_at' => $transaction->paid_at,
                'created_at' => $transaction->created_at,
            ],
            'order' => $order ? [
                'id' => $order->id,
                'status' => $order->status,
                'payment_status' => $order->payment_status,
                'total' => $order->total,
            ] : null,
        ]);
    }
}
