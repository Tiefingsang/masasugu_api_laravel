<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Table transactions — Journal financier centralisé
     *
     * Enregistre TOUTES les transactions financières :
     * - Paiements clients (multi-vendeurs)
     * - Commissions Masasugu
     * - Frais prestataires
     * - Remboursements
     * - Payouts vendeurs
     *
     * Chaque transaction est traçable, auditable et liée à une commande.
     */
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();

            // Identifiant unique
            $table->string('transaction_id', 50)->unique()
                ->comment('ID unique généré : TXN-ABC123XYZ');

            // Relations
            $table->foreignId('order_id')
                ->nullable()
                ->constrained('orders')
                ->onDelete('set null');

            $table->foreignId('order_item_id')
                ->nullable()
                ->constrained('order_items')
                ->onDelete('set null')
                ->comment('Pour le multi-vendeurs : 1 transaction par item');

            $table->foreignId('buyer_id')
                ->nullable()
                ->constrained('users')
                ->onDelete('set null');

            $table->foreignId('seller_id')
                ->nullable()
                ->constrained('users')
                ->onDelete('set null')
                ->comment('Qui reçoit l\'argent');

            $table->foreignId('company_id')
                ->nullable()
                ->constrained('companies')
                ->onDelete('set null');

            // Gateway et méthode
            $table->string('gateway', 50)->nullable()
                ->comment('orange, cinetpay, stripe, cash_on_delivery');

            $table->string('gateway_transaction_id')->nullable()
                ->comment('ID de transaction chez le gateway');

            $table->string('payment_method', 50)->nullable()
                ->comment('mobile_money, card, cash, bank_transfer');

            $table->string('provider', 50)->nullable()
                ->comment('orange, wave, mtn, moov, visa, stripe');

            // Localisation
            $table->string('country_code', 2)->nullable()
                ->comment('ML, CI, FR');

            $table->string('currency', 3)->default('XOF')
                ->comment('Devise de la transaction');

            // Montants financiers
            $table->decimal('amount', 15, 2)->default(0)
                ->comment('Montant total payé par le client');

            $table->decimal('platform_fee', 15, 2)->default(0)
                ->comment('Commission Masasugu');

            $table->decimal('gateway_fee', 15, 2)->default(0)
                ->comment('Frais du prestataire');

            $table->decimal('seller_amount', 15, 2)->default(0)
                ->comment('Montant net pour le vendeur');

            // Statut et type
            $table->string('status', 30)->default('pending')
                ->comment('pending, processing, success, failed, cancelled, refunded, disputed');

            $table->string('type', 30)->default('payment')
                ->comment('payment, refund, payout, commission, adjustment');

            // Détails techniques
            $table->text('error_message')->nullable()
                ->comment('Message d\'erreur si échec');

            $table->json('metadata')->nullable()
                ->comment('Données additionnelles du gateway');

            // Dates
            $table->timestamp('paid_at')->nullable()
                ->comment('Date du paiement réussi');

            $table->timestamp('refunded_at')->nullable()
                ->comment('Date du remboursement');

            $table->timestamps();

            // Index pour recherches fréquentes
            $table->index('transaction_id');
            $table->index('order_id');
            $table->index('buyer_id');
            $table->index('seller_id');
            $table->index('gateway');
            $table->index('status');
            $table->index('type');
            $table->index('currency');
            $table->index(['seller_id', 'status']);
            $table->index(['order_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
