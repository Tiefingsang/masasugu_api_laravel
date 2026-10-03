<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Company;
use App\Models\Setting;
use Illuminate\Console\Command;

class MigrateOrderData extends Command
{
    /**
     * Le nom et la signature de la commande
     */
    protected $signature = 'masasugu:migrate-order-data {--dry-run : Afficher sans modifier}';

    /**
     * La description de la commande
     */
    protected $description = 'Remplir les nouvelles colonnes des commandes et order_items existantes';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('🔍 MODE DRY-RUN — Aucune modification ne sera faite');
        }

        // Récupérer la commission configurable
        $platformFeePercent = (float) Setting::get('platform_fee_percent', 5);
        $gatewayFeePercent = (float) Setting::get('gateway_fee_percent', 2);

        $this->info("📊 Commission Masasugu : {$platformFeePercent}%");
        $this->info("📊 Frais prestataire : {$gatewayFeePercent}%");
        $this->newLine();

        // ═══════════════════════════════════════════════
        // 1. MIGRER LES ORDERS
        // ═══════════════════════════════════════════════
        $this->info('═══ 1. Migration des ORDERS ═══');

        $orders = Order::all();
        $this->info("Trouvé {$orders->count()} commandes à traiter.");

        $ordersUpdated = 0;

        foreach ($orders as $order) {
            $updates = [];

            // subtotal : si 0, utiliser le total
            if (!$order->subtotal || $order->subtotal == 0) {
                $updates['subtotal'] = $order->total;
            }

            // payment_status : si non défini, calculer
            if (!$order->payment_status || $order->payment_status === 'unpaid') {
                $updates['payment_status'] = match ($order->status) {
                    'delivered' => 'paid',
                    'cancelled' => 'cancelled',
                    'pending', 'confirmed' => 'unpaid',
                    default => 'unpaid',
                };
            }

            // currency : si vide
            if (!$order->currency || $order->currency === '') {
                $updates['currency'] = 'XOF';
            }

            // country : si vide
            if (!$order->country || $order->country === '') {
                $updates['country'] = 'ML';
            }

            // paid_at : si payé mais pas de date
            if (isset($updates['payment_status']) && $updates['payment_status'] === 'paid' && !$order->paid_at) {
                $updates['paid_at'] = $order->updated_at ?? now();
            }

            // platform_fee_total et gateway_fee_total (si 0 et payé)
            if (!$order->platform_fee_total || $order->platform_fee_total == 0) {
                if ($order->payment_status === 'paid') {
                    $updates['platform_fee_total'] = round($order->total * $platformFeePercent / 100, 2);
                }
            }

            if (!$order->gateway_fee_total || $order->gateway_fee_total == 0) {
                if ($order->payment_status === 'paid') {
                    $updates['gateway_fee_total'] = round($order->total * $gatewayFeePercent / 100, 2);
                }
            }

            if (!empty($updates)) {
                if ($dryRun) {
                    $this->line("  [DRY-RUN] Order #{$order->id} → " . json_encode($updates));
                } else {
                    $order->update($updates);
                    $this->line("  ✅ Order #{$order->id} mis à jour");
                }
                $ordersUpdated++;
            }
        }

        $this->newLine();
        $this->info("✅ {$ordersUpdated} commandes traitées.");
        $this->newLine();

        // ═══════════════════════════════════════════════
        // 2. MIGRER LES ORDER_ITEMS
        // ═══════════════════════════════════════════════
        $this->info('═══ 2. Migration des ORDER_ITEMS ═══');

        $items = OrderItem::with(['order', 'product'])->get();
        $this->info("Trouvé {$items->count()} items à traiter.");

        $itemsUpdated = 0;

        foreach ($items as $item) {
            $updates = [];

            // Récupérer le produit
            $product = $item->product;

            if (!$product) {
                $this->warn("  ⚠️ Item #{$item->id} : produit introuvable");
                continue;
            }

            // company_id : depuis le produit
            if (!$item->company_id) {
                $updates['company_id'] = $product->company_id;
            }

            // seller_id : depuis la company ou le produit
            if (!$item->seller_id) {
                $company = Company::find($product->company_id);
                if ($company && $company->user_id) {
                    $updates['seller_id'] = $company->user_id;
                } elseif ($product->user_id) {
                    $updates['seller_id'] = $product->user_id;
                }
            }

            // Calcul des montants
            $itemTotal = ($item->price ?? $product->price ?? 0) * ($item->quantity ?? 1);

            // platform_fee
            if (!$item->platform_fee || $item->platform_fee == 0) {
                $updates['platform_fee'] = round($itemTotal * $platformFeePercent / 100, 2);
            }

            // gateway_fee
            if (!$item->gateway_fee || $item->gateway_fee == 0) {
                $updates['gateway_fee'] = round($itemTotal * $gatewayFeePercent / 100, 2);
            }

            // seller_amount = total - platform_fee - gateway_fee
            if (!$item->seller_amount || $item->seller_amount == 0) {
                $platformFee = $updates['platform_fee'] ?? $item->platform_fee;
                $gatewayFee = $updates['gateway_fee'] ?? $item->gateway_fee;
                $updates['seller_amount'] = round($itemTotal - $platformFee - $gatewayFee, 2);
            }

            // status
            if (!$item->status || $item->status === 'pending') {
                $order = $item->order;
                if ($order) {
                    $updates['status'] = match ($order->status) {
                        'delivered' => 'delivered',
                        'cancelled' => 'cancelled',
                        default => 'pending',
                    };
                }
            }

            if (!empty($updates)) {
                if ($dryRun) {
                    $this->line("  [DRY-RUN] Item #{$item->id} → " . json_encode($updates));
                } else {
                    $item->update($updates);
                    $this->line("  ✅ Item #{$item->id} mis à jour (company: {$item->company_id}, seller: {$item->seller_id}, net: {$item->seller_amount})");
                }
                $itemsUpdated++;
            }
        }

        $this->newLine();
        $this->info("✅ {$itemsUpdated} items traités.");
        $this->newLine();

        // ═══════════════════════════════════════════════
        // 3. RÉSUMÉ
        // ═══════════════════════════════════════════════
        $this->info('═══════════════════════════════════════');
        $this->info('📊 RÉSUMÉ');
        $this->info('═══════════════════════════════════════');
        $this->line("  Orders traités     : {$ordersUpdated}");
        $this->line("  Items traités      : {$itemsUpdated}");

        if ($dryRun) {
            $this->newLine();
            $this->warn('⚠️ MODE DRY-RUN — Rien n\'a été modifié');
            $this->info('Pour appliquer : php artisan masasugu:migrate-order-data');
        } else {
            $this->newLine();
            $this->info('✅ Migration terminée avec succès !');
        }

        return self::SUCCESS;
    }
}
