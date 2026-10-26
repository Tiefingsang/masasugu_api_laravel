<?php

namespace App\Services\Payments;

use App\Models\Payout;
use App\Models\SellerWallet;
use App\Models\PayoutMethod;
use App\Models\User;
use App\Services\FcmService;
use Illuminate\Support\Facades\Log;

class AutoPayoutService
{
    protected PayoutManager $payoutManager;

    public function __construct()
    {
        $this->payoutManager = new PayoutManager();
    }

    /**
     * Déclenche automatiquement les retraits pour tous les vendeurs
     * dont le solde dépasse le seuil minimum.
     */
    public function processAutoPayouts(): void
    {
        Log::info('🤖 Démarrage des retraits automatiques');

        // 1. Récupérer tous les wallets dont le solde disponible dépasse le minimum (ex: 5000)
        $wallets = SellerWallet::where('available_balance', '>=', 5000)->get();

        foreach ($wallets as $wallet) {
            $this->processWalletPayout($wallet);
        }

        Log::info('🤖 Fin des retraits automatiques');
    }

    /**
     * Traite le retrait pour un wallet spécifique.
     */
    private function processWalletPayout(SellerWallet $wallet): void
    {
        $seller = User::find($wallet->seller_id);
        if (!$seller) return;

        // 2. Récupérer le moyen de retrait par défaut du vendeur
        $method = PayoutMethod::where('seller_id', $seller->id)
            ->where('is_active', true)
            ->where('is_default', true)
            ->first();

        if (!$method) {
            Log::warning("⚠️ Pas de moyen de retrait par défaut pour le vendeur {$seller->id}");
            return;
        }

        // 3. Demander le retrait (le PayoutManager gère les vérifications)
        try {
            $amount = $wallet->available_balance; // On retire tout le solde

            $payout = $this->payoutManager->requestPayout($wallet, $method, $amount);

            // 4. Marquer comme "processing" (car c'est automatique)
            $this->payoutManager->process($payout, $seller); // On utilise le vendeur comme "admin" symbolique pour l'auto

            // 5. Envoyer l'argent immédiatement via PayDunya
            $this->payoutManager->complete($payout);

            Log::info("✅ Retrait automatique réussi pour le vendeur {$seller->id} (Montant: {$amount})");

            // 6. Notifier l'admin
            $this->notifyAdmin($seller, $amount, $payout->payout_id);

        } catch (\Exception $e) {
            Log::error("❌ Erreur retrait automatique pour le vendeur {$seller->id}: " . $e->getMessage());
        }
    }

    /**
     * Notifie l'admin qu'un retrait automatique a eu lieu.
     */
    private function notifyAdmin(User $seller, float $amount, string $payoutId): void
    {
        $admin = User::where('role', 'admin')->first();
        if (!$admin) return;

        try {
            $fcm = new FcmService();
            $fcm->sendToUser(
                $admin,
                "🤖 Retrait automatique",
                "Un retrait de " . number_format($amount, 0, ',', ' ') . " FCFA a été envoyé automatiquement au vendeur {$seller->name} (ID: {$payoutId}).",
                [
                    'type' => 'admin_auto_payout',
                    'payout_id' => $payoutId,
                    'seller_id' => (string)$seller->id,
                    'amount' => (string)$amount,
                ]
            );
        } catch (\Exception $e) {
            Log::warning("Erreur notification admin: " . $e->getMessage());
        }
    }
}
