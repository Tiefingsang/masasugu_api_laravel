<?php

namespace App\Services\Payments;

use App\Models\Order;
use App\Models\SellerWallet;
use App\Models\Transaction;
use App\Models\WalletTransaction;
use App\Services\Payments\PaymentNotificationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * WalletManager — Gestion des portefeuilles vendeurs
 *
 * Permet de :
 * - Créditer un wallet (pending puis available)
 * - Libérer les fonds
 * - Débiter un wallet
 * - Traiter une vente complète (sale + commission + release)
 */
class WalletManager
{
    protected PaymentNotificationService $notifier;

    public function __construct()
    {
        $this->notifier = new PaymentNotificationService();
    }

    /**
     * Récupérer ou créer le wallet d'un vendeur
     */
    public function getOrCreateWallet(int $sellerId, string $currency = 'XOF'): SellerWallet
    {
        return SellerWallet::firstOrCreate(
            ['seller_id' => $sellerId],
            [
                'currency' => $currency,
                'available_balance' => 0,
                'pending_balance' => 0,
                'total_earned' => 0,
                'total_withdrawn' => 0,
                'total_commission_paid' => 0,
                'is_active' => true,
            ]
        );
    }

    /**
     * Créditer un wallet (en attente)
     *
     * Utilisé après un paiement réussi : l'argent est en attente
     * jusqu'à la livraison + délai de sécurité.
     *
     * @param SellerWallet $wallet
     * @param float $amount Montant à créditer
     * @param string $type SALE, ADJUSTMENT, etc.
     * @param array $meta ['order_id', 'transaction_id', 'description']
     */
    public function credit(SellerWallet $wallet, float $amount, string $type, array $meta = []): WalletTransaction
    {
        return DB::transaction(function () use ($wallet, $amount, $type, $meta) {
            $balanceBefore = (float) $wallet->pending_balance;

            // Mettre à jour le wallet
            $wallet->increment('pending_balance', $amount);
            $wallet->increment('total_earned', $amount);
            $wallet->update(['last_transaction_at' => now()]);

            $wallet->refresh();
            $balanceAfter = (float) $wallet->pending_balance;

            // Créer la transaction wallet
            $txn = WalletTransaction::create([
                'wallet_id' => $wallet->id,
                'seller_id' => $wallet->seller_id,
                'type' => $type,
                'direction' => 'credit',
                'amount' => $amount,
                'currency' => $wallet->currency,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'order_id' => $meta['order_id'] ?? null,
                'transaction_id' => $meta['transaction_id'] ?? null,
                'reference' => $meta['reference'] ?? null,
                'description' => $meta['description'] ?? null,
                'metadata' => $meta['metadata'] ?? null,
                'status' => 'completed',
            ]);

            Log::info('💰 Wallet crédité', [
                'wallet_id' => $wallet->id,
                'amount' => $amount,
                'type' => $type,
                'new_pending_balance' => $balanceAfter,
            ]);

            return $txn;
        });
    }

    /**
     * Libérer les fonds (pending → available)
     *
     * Appelé après le délai de sécurité (7 jours par défaut).
     */
    public function release(SellerWallet $wallet, float $amount, array $meta = []): WalletTransaction
    {
        return DB::transaction(function () use ($wallet, $amount, $meta) {
            if ($wallet->pending_balance < $amount) {
                throw new \Exception("Solde en attente insuffisant (disponible : {$wallet->pending_balance}, demandé : {$amount})");
            }

            $balanceBefore = (float) $wallet->available_balance;

            $wallet->decrement('pending_balance', $amount);
            $wallet->increment('available_balance', $amount);
            $wallet->update(['last_transaction_at' => now()]);

            $wallet->refresh();
            $balanceAfter = (float) $wallet->available_balance;

            $txn = WalletTransaction::create([
                'wallet_id' => $wallet->id,
                'seller_id' => $wallet->seller_id,
                'type' => 'RELEASE',
                'direction' => 'credit',
                'amount' => $amount,
                'currency' => $wallet->currency,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'order_id' => $meta['order_id'] ?? null,
                'transaction_id' => $meta['transaction_id'] ?? null,
                'reference' => $meta['reference'] ?? null,
                'description' => $meta['description'] ?? 'Fonds libérés',
                'status' => 'completed',
            ]);

            Log::info('🔓 Fonds libérés', [
                'wallet_id' => $wallet->id,
                'amount' => $amount,
                'new_available_balance' => $balanceAfter,
            ]);

            // 🔔 Notifier le vendeur
            $this->notifier->notifyWalletReleased($wallet, $amount);

            return $txn;
        });
    }

    /**
     * Débiter un wallet (available)
     *
     * Utilisé pour les retraits, remboursements, ajustements.
     */
    public function debit(SellerWallet $wallet, float $amount, string $type, array $meta = []): WalletTransaction
    {
        return DB::transaction(function () use ($wallet, $amount, $type, $meta) {
            if ($wallet->available_balance < $amount) {
                throw new \Exception("Solde disponible insuffisant (disponible : {$wallet->available_balance}, demandé : {$amount})");
            }

            $balanceBefore = (float) $wallet->available_balance;

            $wallet->decrement('available_balance', $amount);
            $wallet->update(['last_transaction_at' => now()]);

            $wallet->refresh();
            $balanceAfter = (float) $wallet->available_balance;

            $txn = WalletTransaction::create([
                'wallet_id' => $wallet->id,
                'seller_id' => $wallet->seller_id,
                'type' => $type,
                'direction' => 'debit',
                'amount' => -$amount, // Négatif pour débit
                'currency' => $wallet->currency,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'order_id' => $meta['order_id'] ?? null,
                'transaction_id' => $meta['transaction_id'] ?? null,
                'reference' => $meta['reference'] ?? null,
                'description' => $meta['description'] ?? null,
                'metadata' => $meta['metadata'] ?? null,
                'status' => 'completed',
            ]);

            Log::info('💸 Wallet débité', [
                'wallet_id' => $wallet->id,
                'amount' => $amount,
                'type' => $type,
                'new_available_balance' => $balanceAfter,
            ]);

            return $txn;
        });
    }

    /**
     * 🎯 Traiter une vente complète
     *
     * Appelé après un paiement réussi :
     * 1. Créditer le wallet vendeur (pending)
     * 2. Enregistrer la commission Masasugu
     * 3. Notifier le vendeur
     */
    public function processSale(Order $order, Transaction $transaction): void
    {
        if (!$transaction->seller_id) {
            Log::warning('⚠️ Transaction sans seller_id', ['transaction_id' => $transaction->id]);
            return;
        }

        $wallet = $this->getOrCreateWallet($transaction->seller_id, $order->currency ?? 'XOF');

        // 1. Créditer le wallet avec le net vendeur
        $this->credit($wallet, (float) $transaction->seller_amount, 'SALE', [
            'order_id' => $order->id,
            'transaction_id' => $transaction->id,
            'description' => "Vente commande #{$order->id}",
            'metadata' => [
                'platform_fee' => $transaction->platform_fee,
                'gateway_fee' => $transaction->gateway_fee,
                'order_total' => $transaction->amount,
            ],
        ]);

        // 2. Enregistrer la commission Masasugu
        if ($transaction->platform_fee > 0) {
            $this->credit($wallet, 0, 'COMMISSION', [
                'order_id' => $order->id,
                'transaction_id' => $transaction->id,
                'description' => "Commission Masasugu ({$transaction->platform_fee} FCFA)",
                'metadata' => [
                    'commission_amount' => $transaction->platform_fee,
                ],
            ]);

            // Mettre à jour total_commission_paid
            $wallet->increment('total_commission_paid', $transaction->platform_fee);
        }

        // 3. Enregistrer les frais gateway
        if ($transaction->gateway_fee > 0) {
            WalletTransaction::create([
                'wallet_id' => $wallet->id,
                'seller_id' => $wallet->seller_id,
                'type' => 'GATEWAY_FEE',
                'direction' => 'debit',
                'amount' => -$transaction->gateway_fee,
                'currency' => $wallet->currency,
                'balance_before' => $wallet->pending_balance,
                'balance_after' => $wallet->pending_balance,
                'order_id' => $order->id,
                'transaction_id' => $transaction->id,
                'description' => "Frais de paiement ({$transaction->gateway_fee} FCFA)",
                'status' => 'completed',
            ]);
        }

        // 4. Notifier le vendeur
        $this->notifier->notifyWalletCredited($wallet, (float) $transaction->seller_amount, $order);

        Log::info('✅ Vente traitée', [
            'order_id' => $order->id,
            'seller_id' => $transaction->seller_id,
            'seller_amount' => $transaction->seller_amount,
            'platform_fee' => $transaction->platform_fee,
        ]);
    }

    /**
     * 🔄 Rembourser un wallet (débit)
     */
    public function refund(SellerWallet $wallet, float $amount, array $meta = []): WalletTransaction
    {
        // On essaie de débiter du pending d'abord, sinon du available
        return DB::transaction(function () use ($wallet, $amount, $meta) {
            $pendingDebit = min($wallet->pending_balance, $amount);
            $availableDebit = $amount - $pendingDebit;

            if ($pendingDebit > 0) {
                $wallet->decrement('pending_balance', $pendingDebit);
            }
            if ($availableDebit > 0) {
                $wallet->decrement('available_balance', $availableDebit);
            }
            $wallet->decrement('total_earned', $amount);
            $wallet->update(['last_transaction_at' => now()]);

            $txn = WalletTransaction::create([
                'wallet_id' => $wallet->id,
                'seller_id' => $wallet->seller_id,
                'type' => 'REFUND',
                'direction' => 'debit',
                'amount' => -$amount,
                'currency' => $wallet->currency,
                'balance_before' => $wallet->pending_balance + $pendingDebit,
                'balance_after' => $wallet->pending_balance,
                'order_id' => $meta['order_id'] ?? null,
                'transaction_id' => $meta['transaction_id'] ?? null,
                'description' => $meta['description'] ?? 'Remboursement',
                'status' => 'completed',
            ]);

            Log::warning('↩️ Wallet remboursé', [
                'wallet_id' => $wallet->id,
                'amount' => $amount,
            ]);

            return $txn;
        });
    }

    /**
     * 📊 Statistiques du wallet
     */
    public function getStats(SellerWallet $wallet): array
    {
        return [
            'available_balance' => (float) $wallet->available_balance,
            'pending_balance' => (float) $wallet->pending_balance,
            'total_balance' => (float) ($wallet->available_balance + $wallet->pending_balance),
            'total_earned' => (float) $wallet->total_earned,
            'total_withdrawn' => (float) $wallet->total_withdrawn,
            'total_commission_paid' => (float) $wallet->total_commission_paid,
        ];
    }
}
