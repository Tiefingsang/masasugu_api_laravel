<?php

namespace App\Services\Payments;

use App\Models\Order;
use App\Models\Payout;
use App\Models\SellerWallet;
use App\Models\Transaction;
use App\Models\User;
use App\Services\FcmService;
use Illuminate\Support\Facades\Log;

/**
 * PaymentNotificationService
 *
 * Service centralisé pour toutes les notifications de paiement.
 * Envoie FCM (push) + Reverb (temps réel).
 */
class PaymentNotificationService
{
    protected FcmService $fcm;

    public function __construct()
    {
        $this->fcm = new FcmService();
    }

    /**
     * 💳 Paiement initié
     */
    public function notifyPaymentInitiated(Order $order, string $gateway): void
    {
        try {
            $seller = $this->getSellerFromOrder($order);
            if ($seller) {
                $this->sendFcm($seller, '💳 Paiement en cours',
                    "Un client paie la commande #{$order->id} ({$order->total} FCFA)", [
                    'type' => 'payment_initiated',
                    'order_id' => (string) $order->id,
                    'gateway' => $gateway,
                    'amount' => (string) $order->total,
                    'click_action' => 'ORDER_DETAIL',
                ]);
            }

            $this->broadcast('payment.initiated', [
                'order_id' => $order->id,
                'gateway' => $gateway,
                'amount' => $order->total,
                'seller_id' => $seller?->id,
            ]);

            Log::info('✅ Notif payment_initiated', ['order_id' => $order->id]);
        } catch (\Exception $e) {
            Log::warning('⚠️ Erreur notif payment_initiated', ['error' => $e->getMessage()]);
        }
    }

    /**
     * ✅ Paiement réussi
     */
    public function notifyPaymentSucceeded(Order $order, Transaction $transaction): void
    {
        try {
            if ($order->user) {
                $this->sendFcm($order->user, '✅ Paiement réussi',
                    "Votre paiement de {$order->total} FCFA pour la commande #{$order->id} a été accepté.", [
                    'type' => 'payment_succeeded',
                    'order_id' => (string) $order->id,
                    'transaction_id' => $transaction->transaction_id,
                    'amount' => (string) $order->total,
                    'click_action' => 'ORDER_DETAIL',
                ]);
            }

            $seller = $this->getSellerFromOrder($order);
            if ($seller) {
                $this->sendFcm($seller, '💰 Nouvelle vente payée',
                    "Commande #{$order->id} payée : +" . $transaction->seller_amount . " FCFA", [
                    'type' => 'payment_received',
                    'order_id' => (string) $order->id,
                    'transaction_id' => $transaction->transaction_id,
                    'amount' => (string) $transaction->seller_amount,
                    'click_action' => 'ORDER_DETAIL',
                ]);
            }

            $this->broadcast('payment.succeeded', [
                'order_id' => $order->id,
                'transaction_id' => $transaction->transaction_id,
                'amount' => $order->total,
                'seller_amount' => $transaction->seller_amount,
            ]);

            Log::info('✅ Notif payment_succeeded', ['order_id' => $order->id]);
        } catch (\Exception $e) {
            Log::warning('⚠️ Erreur notif payment_succeeded', ['error' => $e->getMessage()]);
        }
    }

    /**
     * ❌ Paiement échoué
     */
    public function notifyPaymentFailed(Order $order, string $reason): void
    {
        try {
            if ($order->user) {
                $this->sendFcm($order->user, '❌ Paiement échoué',
                    "Le paiement de la commande #{$order->id} a échoué. Réessayez.", [
                    'type' => 'payment_failed',
                    'order_id' => (string) $order->id,
                    'reason' => $reason,
                    'click_action' => 'CART',
                ]);
            }

            $this->broadcast('payment.failed', [
                'order_id' => $order->id,
                'reason' => $reason,
            ]);

            Log::info('⚠️ Notif payment_failed', ['order_id' => $order->id]);
        } catch (\Exception $e) {
            Log::warning('⚠️ Erreur notif payment_failed', ['error' => $e->getMessage()]);
        }
    }

    /**
     * ↩️ Remboursement
     */
    public function notifyRefunded(Order $order, Transaction $transaction, float $amount): void
    {
        try {
            if ($order->user) {
                $this->sendFcm($order->user, '↩️ Remboursement',
                    "Remboursement de {$amount} FCFA pour la commande #{$order->id}", [
                    'type' => 'payment_refunded',
                    'order_id' => (string) $order->id,
                    'amount' => (string) $amount,
                    'click_action' => 'ORDER_DETAIL',
                ]);
            }

            $seller = $this->getSellerFromOrder($order);
            if ($seller) {
                $this->sendFcm($seller, '↩️ Remboursement',
                    "Un remboursement de {$amount} FCFA a été effectué sur la commande #{$order->id}", [
                    'type' => 'order_refunded',
                    'order_id' => (string) $order->id,
                    'amount' => (string) $amount,
                    'click_action' => 'ORDER_DETAIL',
                ]);
            }

            $this->broadcast('payment.refunded', [
                'order_id' => $order->id,
                'amount' => $amount,
            ]);

            Log::info('✅ Notif payment_refunded', ['order_id' => $order->id]);
        } catch (\Exception $e) {
            Log::warning('⚠️ Erreur notif payment_refunded', ['error' => $e->getMessage()]);
        }
    }

    /**
     * 💰 Wallet crédité
     */
    public function notifyWalletCredited(SellerWallet $wallet, float $amount, Order $order): void
    {
        try {
            $seller = $wallet->seller;
            if (!$seller) return;

            $this->sendFcm($seller, '💰 Argent en attente',
                "+{$amount} FCFA en attente (commande #{$order->id})", [
                'type' => 'wallet_credited',
                'wallet_id' => (string) $wallet->id,
                'order_id' => (string) $order->id,
                'amount' => (string) $amount,
                'new_pending_balance' => (string) $wallet->pending_balance,
                'click_action' => 'WALLET',
            ]);

            $this->broadcast('wallet.credited', [
                'seller_id' => $seller->id,
                'wallet_id' => $wallet->id,
                'amount' => $amount,
                'pending_balance' => $wallet->pending_balance,
            ]);

            Log::info('✅ Notif wallet_credited', ['seller_id' => $seller->id]);
        } catch (\Exception $e) {
            Log::warning('⚠️ Erreur notif wallet_credited', ['error' => $e->getMessage()]);
        }
    }

    /**
     * 🔓 Fonds libérés
     */
    public function notifyWalletReleased(SellerWallet $wallet, float $amount): void
    {
        try {
            $seller = $wallet->seller;
            if (!$seller) return;

            $this->sendFcm($seller, '🔓 Fonds disponibles',
                "+{$amount} FCFA sont maintenant disponibles pour retrait.", [
                'type' => 'wallet_released',
                'wallet_id' => (string) $wallet->id,
                'amount' => (string) $amount,
                'available_balance' => (string) $wallet->available_balance,
                'click_action' => 'WALLET',
            ]);

            $this->broadcast('wallet.released', [
                'seller_id' => $seller->id,
                'wallet_id' => $wallet->id,
                'amount' => $amount,
                'available_balance' => $wallet->available_balance,
            ]);

            Log::info('✅ Notif wallet_released', ['seller_id' => $seller->id]);
        } catch (\Exception $e) {
            Log::warning('⚠️ Erreur notif wallet_released', ['error' => $e->getMessage()]);
        }
    }

    /**
     * 💸 Demande de retrait
     */
    public function notifyPayoutRequested(Payout $payout): void
    {
        try {
            $admins = User::where('role', 'admin')->get();

            foreach ($admins as $admin) {
                $this->sendFcm($admin, '💸 Nouvelle demande de retrait',
                    "{$payout->seller->name} demande {$payout->amount} FCFA", [
                    'type' => 'payout_requested',
                    'payout_id' => (string) $payout->id,
                    'seller_id' => (string) $payout->seller_id,
                    'amount' => (string) $payout->amount,
                    'click_action' => 'ADMIN_PAYOUTS',
                ]);
            }

            $this->broadcast('payout.requested', [
                'payout_id' => $payout->id,
                'seller_id' => $payout->seller_id,
                'amount' => $payout->amount,
            ]);

            Log::info('✅ Notif payout_requested', ['payout_id' => $payout->id]);
        } catch (\Exception $e) {
            Log::warning('⚠️ Erreur notif payout_requested', ['error' => $e->getMessage()]);
        }
    }

    /**
     * ✅ Retrait complété
     */
    public function notifyPayoutCompleted(Payout $payout): void
    {
        try {
            $seller = $payout->seller;
            if (!$seller) return;

            $this->sendFcm($seller, '✅ Retrait complété',
                "Votre retrait de {$payout->amount} FCFA a été envoyé", [
                'type' => 'payout_completed',
                'payout_id' => (string) $payout->id,
                'amount' => (string) $payout->amount,
                'destination' => $payout->destination,
                'click_action' => 'WALLET',
            ]);

            $this->broadcast('payout.completed', [
                'seller_id' => $seller->id,
                'payout_id' => $payout->id,
                'amount' => $payout->amount,
            ]);

            Log::info('✅ Notif payout_completed', ['payout_id' => $payout->id]);
        } catch (\Exception $e) {
            Log::warning('⚠️ Erreur notif payout_completed', ['error' => $e->getMessage()]);
        }
    }

    /**
     * ❌ Retrait échoué
     */
    public function notifyPayoutFailed(Payout $payout, string $reason): void
    {
        try {
            $seller = $payout->seller;
            if (!$seller) return;

            $this->sendFcm($seller, '❌ Retrait échoué',
                "Retrait de {$payout->amount} FCFA : {$reason}", [
                'type' => 'payout_failed',
                'payout_id' => (string) $payout->id,
                'amount' => (string) $payout->amount,
                'reason' => $reason,
                'click_action' => 'WALLET',
            ]);

            $this->broadcast('payout.failed', [
                'seller_id' => $seller->id,
                'payout_id' => $payout->id,
                'reason' => $reason,
            ]);

            Log::info('⚠️ Notif payout_failed', ['payout_id' => $payout->id]);
        } catch (\Exception $e) {
            Log::warning('⚠️ Erreur notif payout_failed', ['error' => $e->getMessage()]);
        }
    }

    // ═══════════════════════════════════════════════
    // HELPERS
    // ═══════════════════════════════════════════════

    protected function sendFcm(User $user, string $title, string $body, array $data = []): void
    {
        if (!$user->fcm_token) {
            Log::info("ℹ️ Pas de FCM token pour user #{$user->id}");
            return;
        }

        try {
            $this->fcm->sendToUser($user, $title, $body, $data);
        } catch (\Exception $e) {
            Log::warning("⚠️ Erreur FCM user #{$user->id}: " . $e->getMessage());
        }
    }

    protected function broadcast(string $event, array $data): void
    {
        try {
            broadcast(new \App\Events\PaymentEvent($event, $data));
        } catch (\Exception $e) {
            Log::warning("⚠️ Erreur broadcast {$event}: " . $e->getMessage());
        }
    }

    protected function getSellerFromOrder(Order $order): ?User
    {
        $firstItem = $order->items()->first();
        if ($firstItem && $firstItem->seller_id) {
            return User::find($firstItem->seller_id);
        }

        if ($order->company_id) {
            $company = \App\Models\Company::find($order->company_id);
            if ($company && $company->user_id) {
                return User::find($company->user_id);
            }
        }

        return null;
    }
}
