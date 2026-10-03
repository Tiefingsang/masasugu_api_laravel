<?php

namespace App\Services\Payments;

use App\Exceptions\PaymentException;
use App\Models\Payout;
use App\Models\PayoutMethod;
use App\Models\SellerWallet;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * PayoutManager — Gestion des retraits vendeurs
 *
 * Workflow complet :
 * 1. Vendeur demande un retrait
 * 2. Admin reçoit notification
 * 3. Admin valide → Processing
 * 4. Provider envoie l'argent → Completed
 * 5. Vendeur reçoit notification
 */
class PayoutManager
{
    protected PaymentNotificationService $notifier;
    protected WalletManager $walletManager;

    public function __construct()
    {
        $this->notifier = new PaymentNotificationService();
        $this->walletManager = new WalletManager();
    }

    /**
     * 💸 Demander un retrait
     *
     * @throws PaymentException
     */
    public function requestPayout(SellerWallet $wallet, PayoutMethod $method, float $amount): Payout
    {
        // 1. Vérifications
        $minAmount = (float) Setting::get('payout_min_amount', 5000);

        if ($amount < $minAmount) {
            throw new PaymentException(
                "Le montant minimum de retrait est de {$minAmount} FCFA",
                400
            );
        }

        if ($amount > $wallet->available_balance) {
            throw new PaymentException(
                "Solde insuffisant. Disponible : {$wallet->available_balance} FCFA",
                400
            );
        }

        if (!$method->is_active) {
            throw new PaymentException("Ce moyen de retrait n'est pas actif", 400);
        }

        // 2. Créer le payout
        return DB::transaction(function () use ($wallet, $method, $amount) {
            $payout = Payout::create([
                'seller_id' => $wallet->seller_id,
                'wallet_id' => $wallet->id,
                'payout_method_id' => $method->id,
                'amount' => $amount,
                'fee' => 0,
                'net_amount' => $amount,
                'currency' => $wallet->currency,
                'method' => $method->type,
                'destination' => $method->destination,
                'holder_name' => $method->holder_name,
                'bank_name' => $method->bank_name,
                'status' => 'pending',
                'requested_at' => now(),
            ]);

            // Débiter immédiatement le wallet (pour éviter double-dépense)
            $this->walletManager->debit($wallet, $amount, 'HOLD', [
                'reference' => $payout->payout_id,
                'description' => "Retrait demandé ({$payout->payout_id})",
            ]);

            Log::info('💸 Payout demandé', [
                'payout_id' => $payout->id,
                'seller_id' => $wallet->seller_id,
                'amount' => $amount,
            ]);

            // 3. Notifier les admins
            $this->notifier->notifyPayoutRequested($payout);

            return $payout;
        });
    }

    /**
     * 🔄 Traiter un payout (admin valide)
     */
    public function process(Payout $payout, User $admin): void
    {
        if ($payout->status !== 'pending') {
            throw new PaymentException("Ce payout n'est plus en attente", 400);
        }

        $payout->update([
            'status' => 'processing',
            'processed_at' => now(),
            'processed_by' => $admin->id,
        ]);

        Log::info('🔄 Payout en traitement', [
            'payout_id' => $payout->id,
            'admin_id' => $admin->id,
        ]);
    }

    /**
     * ✅ Marquer comme complété
     */
    public function complete(Payout $payout, string $providerReference = null): void
    {
        if (!in_array($payout->status, ['pending', 'processing'])) {
            throw new PaymentException("Ce payout ne peut pas être complété", 400);
        }

        DB::transaction(function () use ($payout, $providerReference) {
            $payout->update([
                'status' => 'completed',
                'provider_reference' => $providerReference ?? $payout->provider_reference,
                'processed_at' => $payout->processed_at ?? now(),
                'completed_at' => now(),
            ]);

            // Mettre à jour le wallet
            $wallet = $payout->wallet;
            if ($wallet) {
                $wallet->increment('total_withdrawn', $payout->amount);

                // Enregistrer la transaction wallet
                \App\Models\WalletTransaction::create([
                    'wallet_id' => $wallet->id,
                    'seller_id' => $payout->seller_id,
                    'type' => 'WITHDRAWAL',
                    'direction' => 'debit',
                    'amount' => -$payout->amount,
                    'currency' => $payout->currency,
                    'balance_before' => $wallet->available_balance + $payout->amount,
                    'balance_after' => $wallet->available_balance,
                    'reference' => $payout->payout_id,
                    'description' => "Retrait vers {$payout->destination}",
                    'status' => 'completed',
                ]);
            }

            Log::info('✅ Payout complété', [
                'payout_id' => $payout->id,
                'amount' => $payout->amount,
                'reference' => $providerReference,
            ]);

            // Notifier le vendeur
            $this->notifier->notifyPayoutCompleted($payout);
        });
    }

    /**
     * ❌ Marquer comme échoué
     */
    public function fail(Payout $payout, string $reason): void
    {
        DB::transaction(function () use ($payout, $reason) {
            $payout->update([
                'status' => 'failed',
                'rejection_reason' => $reason,
                'processed_at' => $payout->processed_at ?? now(),
            ]);

            // Rembourser le wallet (le HOLD est annulé)
            $wallet = $payout->wallet;
            if ($wallet) {
                // Re-créditer le montant (reverse du HOLD)
                $wallet->increment('available_balance', $payout->amount);
                $wallet->update(['last_transaction_at' => now()]);

                \App\Models\WalletTransaction::create([
                    'wallet_id' => $wallet->id,
                    'seller_id' => $payout->seller_id,
                    'type' => 'ADJUSTMENT',
                    'direction' => 'credit',
                    'amount' => $payout->amount,
                    'currency' => $payout->currency,
                    'balance_before' => $wallet->available_balance - $payout->amount,
                    'balance_after' => $wallet->available_balance,
                    'reference' => $payout->payout_id,
                    'description' => "Annulation du retrait : {$reason}",
                    'status' => 'completed',
                ]);
            }

            Log::warning('❌ Payout échoué', [
                'payout_id' => $payout->id,
                'reason' => $reason,
            ]);

            // Notifier le vendeur
            $this->notifier->notifyPayoutFailed($payout, $reason);
        });
    }

    /**
     * 🚫 Annuler une demande (par le vendeur)
     */
    public function cancel(Payout $payout, string $reason = 'Annulé par le vendeur'): void
    {
        if ($payout->status !== 'pending') {
            throw new PaymentException("Seuls les payouts en attente peuvent être annulés", 400);
        }

        DB::transaction(function () use ($payout, $reason) {
            $payout->update([
                'status' => 'cancelled',
                'rejection_reason' => $reason,
            ]);

            // Rembourser le wallet
            $wallet = $payout->wallet;
            if ($wallet) {
                $wallet->increment('available_balance', $payout->amount);
                $wallet->update(['last_transaction_at' => now()]);

                \App\Models\WalletTransaction::create([
                    'wallet_id' => $wallet->id,
                    'seller_id' => $payout->seller_id,
                    'type' => 'ADJUSTMENT',
                    'direction' => 'credit',
                    'amount' => $payout->amount,
                    'currency' => $payout->currency,
                    'balance_before' => $wallet->available_balance - $payout->amount,
                    'balance_after' => $wallet->available_balance,
                    'reference' => $payout->payout_id,
                    'description' => "Retrait annulé : {$reason}",
                    'status' => 'completed',
                ]);
            }

            Log::info('🚫 Payout annulé', [
                'payout_id' => $payout->id,
                'reason' => $reason,
            ]);
        });
    }

    /**
     * 📋 Payouts en attente (pour admin)
     */
    public function getPendingPayouts()
    {
        return Payout::pending()
            ->with(['seller', 'method', 'wallet'])
            ->orderBy('requested_at')
            ->get();
    }

    /**
     * 📊 Statistiques
     */
    public function getStats(int $sellerId = null): array
    {
        $query = Payout::query();

        if ($sellerId) {
            $query->forSeller($sellerId);
        }

        return [
            'total' => $query->count(),
            'pending' => (clone $query)->pending()->count(),
            'completed' => (clone $query)->completed()->count(),
            'failed' => (clone $query)->failed()->count(),
            'total_amount' => (clone $query)->completed()->sum('amount'),
        ];
    }
}
