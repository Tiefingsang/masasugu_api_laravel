<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\PaymentGateway;
use App\Models\Transaction;
use App\Services\Payments\PaymentManager;
use App\Services\Payments\PaymentNotificationService;
use App\Services\Payments\WalletManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * WebhookController — Reçoit les notifications des gateways
 *
 * Endpoints :
 * - POST /api/webhooks/{gateway}  → Notification de paiement
 * - GET  /api/payments/{gateway}/return → Redirection après paiement
 * - GET  /api/payments/{gateway}/cancel → Redirection si annulation
 */
class WebhookController extends Controller
{
    protected PaymentManager $manager;
    protected PaymentNotificationService $notifier;
    protected WalletManager $walletManager;

    public function __construct()
    {
        $this->manager = new PaymentManager();
        $this->notifier = new PaymentNotificationService();
        $this->walletManager = new WalletManager();
    }

    /**
     * 🔔 Webhook générique
     *
     * Reçoit POST de Orange Money / CinetPay / Flutterwave / Stripe
     */
    public function handle(Request $request, string $gateway)
    {
        Log::info("🔔 Webhook {$gateway}", [
            'payload' => $request->all(),
            'headers' => $request->headers->all(),
        ]);

        try {
            // 1. Traiter via le gateway
            $result = $this->manager->handleWebhook(
                $gateway,
                $request->all(),
                $request->headers->all()
            );

            if (!$result['success']) {
                return response()->json(['error' => 'Webhook processing failed'], 400);
            }

            // 2. Trouver la commande via le pay_token
            $payToken = $request->input('notif_token') ?? $request->input('pay_token');
            $txnId = $request->input('txnid');

            // Chercher la transaction en attente
            $transaction = Transaction::where('gateway_transaction_id', $payToken)
                ->orWhere('metadata->pay_token', $payToken)
                ->first();

            if (!$transaction) {
                Log::warning("⚠️ Webhook {$gateway} — Transaction introuvable", [
                    'pay_token' => $payToken,
                    'txn_id' => $txnId,
                ]);
                return response()->json(['message' => 'Transaction not found'], 200);
            }

            // 3. Idempotence : vérifier que la transaction n'est pas déjà traitée
            if ($transaction->status === 'success') {
                Log::info("ℹ️ Webhook {$gateway} — Déjà traité", ['transaction_id' => $transaction->id]);
                return response()->json(['message' => 'Already processed'], 200);
            }

            // 4. Traiter le paiement
            DB::transaction(function () use ($transaction, $result, $txnId, $payToken) {
                $status = $result['status'];

                // Mettre à jour la transaction
                $transaction->update([
                    'status' => $status,
                    'gateway_transaction_id' => $txnId ?? $transaction->gateway_transaction_id,
                    'metadata' => array_merge($transaction->metadata ?? [], [
                        'webhook_status' => $result['orange_status'] ?? null,
                        'txnid' => $txnId,
                        'notif_token' => $payToken,
                    ]),
                    'paid_at' => $status === 'success' ? now() : null,
                ]);

                $order = $transaction->order;
                if (!$order) return;

                if ($status === 'success') {
                    // ✅ Paiement réussi
                    $order->update([
                        'payment_status' => 'paid',
                        'status' => 'confirmed',
                        'paid_at' => now(),
                    ]);

                    // Créditer le wallet vendeur
                    $this->walletManager->processSale($order, $transaction);

                    // Notifier
                    $this->notifier->notifyPaymentSucceeded($order, $transaction);

                    Log::info("✅ Paiement réussi via {$transaction->gateway}", [
                        'order_id' => $order->id,
                        'transaction_id' => $transaction->id,
                    ]);
                } elseif (in_array($status, ['failed', 'expired'])) {
                    // ❌ Paiement échoué
                    $order->update(['payment_status' => 'failed']);

                    $this->notifier->notifyPaymentFailed($order, $result['message'] ?? 'Paiement échoué');

                    Log::warning("❌ Paiement échoué via {$transaction->gateway}", [
                        'order_id' => $order->id,
                        'status' => $status,
                    ]);
                }
            });

            return response()->json(['message' => 'Webhook processed'], 200);

        } catch (\Exception $e) {
            Log::error("❌ Erreur webhook {$gateway}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * ✅ Callback après paiement réussi (retour navigateur)
     */
    public function return(Request $request, string $gateway = 'orange')
    {
        Log::info("✅ Retour paiement {$gateway}", $request->all());

        return response()->json([
            'success' => true,
            'message' => 'Paiement traité. Vérifiez le statut dans l\'application.',
            'gateway' => $gateway,
        ]);
    }

    /**
     * ❌ Callback après annulation
     */
    public function cancel(Request $request, string $gateway = 'orange')
    {
        Log::info("❌ Annulation paiement {$gateway}", $request->all());

        return response()->json([
            'success' => false,
            'message' => 'Paiement annulé.',
            'gateway' => $gateway,
        ]);
    }
}
