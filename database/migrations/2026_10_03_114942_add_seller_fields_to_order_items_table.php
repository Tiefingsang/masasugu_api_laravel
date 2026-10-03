<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Enrichir order_items pour supporter le multi-vendeurs
     *
     * Chaque item d'une commande sait :
     * - Quel vendeur (company_id)
     * - Qui reçoit l'argent (seller_id)
     * - La commission Masasugu sur cet item
     * - Les frais du prestataire
     * - Le net vendeur
     * - Le statut par item (permet livraison partielle)
     */
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            // Relations vendeurs
            $table->foreignId('company_id')
                ->nullable()
                ->after('order_id')
                ->constrained('companies')
                ->onDelete('set null');

            $table->foreignId('seller_id')
                ->nullable()
                ->after('company_id')
                ->constrained('users')
                ->onDelete('set null');

            // Montants financiers par item
            $table->decimal('platform_fee', 15, 2)->default(0)->after('price');
            $table->decimal('gateway_fee', 15, 2)->default(0)->after('platform_fee');
            $table->decimal('seller_amount', 15, 2)->default(0)->after('gateway_fee');

            // Statut et dates
            $table->string('status')->default('pending')->after('seller_amount');
            $table->timestamp('delivered_at')->nullable()->after('status');

            // Index pour performance
            $table->index('company_id');
            $table->index('seller_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropForeign(['seller_id']);
            $table->dropIndex(['company_id']);
            $table->dropIndex(['seller_id']);
            $table->dropIndex(['status']);

            $table->dropColumn([
                'company_id',
                'seller_id',
                'platform_fee',
                'gateway_fee',
                'seller_amount',
                'status',
                'delivered_at',
            ]);
        });
    }
};
