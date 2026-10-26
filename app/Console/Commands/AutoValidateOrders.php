<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Order;
use App\Models\SellerWallet;
use App\Models\WalletTransaction;
use App\Services\FcmService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AutoValidateOrders extends Command
{
    protected $signature = 'orders:auto-validate';
    protected $description = 'Valide automatiquement les commandes livrées après 7 jours (Escrow)';

    public function handle()
    {
        $this->info('🤖 Démarrage de la validation automatique des commandes...');

        // Récupérer les commandes expédiées depuis plus de 7 jours
        $orders = Order::with('items')
            ->where('status', 'shipped')
            ->where('updated_at', '<=', now()->subDays(7))
            ->get();

        if ($orders->isEmpty()) {
            $this->info('Aucune commande à valider automatiquement.');
            return Command::SUCCESS;
        }

        $fcm = new FcmService();
        $count = 0;

        foreach ($orders as $order) {
            DB::transaction(function () use ($order, $fcm, &$count) {
                // 1. Mettre à jour le statut
                $order->update(['status' => 'delivered', 'delivered_at' => now()]);

                // 2. Débloquer l'argent pour les vendeurs
                $itemsBySeller = $order->items->groupBy('seller_id');

                foreach ($itemsBySeller as $sellerId => $items) {
                    if (!$sellerId) continue;

                    $sellerTotal = $items->sum(fn($item) => $item->price * $item->quantity);
                    $netAmount = $sellerTotal * 0.90; // 10% commission

                    $wallet = SellerWallet::firstOrCreate(['seller_id' => $sellerId]);
                    $wallet->decrement('pending_balance', $netAmount);
                    $wallet->increment('available_balance', $netAmount);

                    WalletTransaction::where('order_id', $order->id)
                        ->where('seller_id', $sellerId)
                        ->where('status', 'pending')
                        ->update([
                            'status' => 'completed',
                            'description' => 'Vente validée automatiquement après 7 jours'
                        ]);

                    // 3. Notifier le vendeur
                    $seller = \App\Models\User::find($sellerId);
                    if ($seller) {
                        $fcm->sendToUser(
                            $seller,
                            "Validation automatique ⏱️",
                            "Délai dépassé pour la commande #{$order->id}. Votre argent est disponible.",
                            ['type' => 'auto_funds_released', 'order_id' => (string)$order->id]
                        );
                    }
                }

                $count++;
                $this->info("Commande #{$order->id} validée automatiquement.");
                Log::info("Commande #{$order->id} validée automatiquement (7 jours dépassés).");
            });
        }

        $this->info("✅ {$count} commande(s) validée(s) automatiquement.");
        return Command::SUCCESS;
    }
}
