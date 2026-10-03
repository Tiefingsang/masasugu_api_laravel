<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Table wallet_transactions — Historique de TOUS les mouvements du wallet
     *
     * Enregistre chaque crédit, débit, libération, retrait.
     * Permet au vendeur de voir l'évolution de son argent.
     */
    public function up(): void
    {
        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();

            // Wallet associé
            $table->foreignId('wallet_id')
                ->constrained('seller_wallets')
                ->onDelete('cascade');

            $table->foreignId('seller_id')
                ->constrained('users')
                ->onDelete('cascade')
                ->comment('Dénormalisé pour recherche rapide');

            // Type de mouvement
            $table->string('type', 30)
                ->comment('SALE, COMMISSION, REFUND, WITHDRAWAL, ADJUSTMENT, RELEASE, HOLD, GATEWAY_FEE');

            // Sens
            $table->string('direction', 10)->default('credit')
                ->comment('credit ou debit');

            // Montant (positif pour crédit, négatif pour débit)
            $table->decimal('amount', 15, 2)
                ->comment('Montant du mouvement (positif ou négatif)');

            // Devise
            $table->string('currency', 3)->default('XOF');

            // Soldes avant/après
            $table->decimal('balance_before', 15, 2)->default(0)
                ->comment('Solde avant le mouvement');

            $table->decimal('balance_after', 15, 2)->default(0)
                ->comment('Solde après le mouvement');

            // Relation avec les commandes/transactions
            $table->foreignId('order_id')
                ->nullable()
                ->constrained('orders')
                ->onDelete('set null');

            $table->foreignId('transaction_id')
                ->nullable()
                ->constrained('transactions')
                ->onDelete('set null');

            // Description
            $table->string('reference')->nullable()
                ->comment('Référence externe : payout ID, txn ID');

            $table->text('description')->nullable()
                ->comment('Description lisible pour le vendeur');

            $table->json('metadata')->nullable()
                ->comment('Données additionnelles');

            // Statut (pour les holds/releases)
            $table->string('status', 30)->default('completed')
                ->comment('pending, completed, cancelled, failed');

            $table->timestamp('available_at')->nullable()
                ->comment('Date à partir de laquelle le montant devient disponible');

            $table->timestamps();

            // Index
            $table->index('wallet_id');
            $table->index('seller_id');
            $table->index('type');
            $table->index('direction');
            $table->index('status');
            $table->index(['wallet_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallet_transactions');
    }
};
