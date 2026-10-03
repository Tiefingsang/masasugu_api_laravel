<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Table payouts — Demandes de retrait des vendeurs
     *
     * Chaque demande de retrait est enregistrée ici.
     * L'admin peut valider, refuser ou marquer comme payée.
     */
    public function up(): void
    {
        Schema::create('payouts', function (Blueprint $table) {
            $table->id();

            // Identifiant unique
            $table->string('payout_id', 50)->unique()
                ->comment('ID unique : PAY-ABC123XYZ');

            // Relations
            $table->foreignId('seller_id')
                ->constrained('users')
                ->onDelete('cascade');

            $table->foreignId('wallet_id')
                ->constrained('seller_wallets')
                ->onDelete('cascade');

            $table->foreignId('payout_method_id')
                ->nullable()
                ->constrained('payout_methods')
                ->onDelete('set null');

            // Montant
            $table->decimal('amount', 15, 2)
                ->comment('Montant demandé');

            $table->decimal('fee', 15, 2)->default(0)
                ->comment('Frais de retrait (si applicable)');

            $table->decimal('net_amount', 15, 2)
                ->comment('Montant net reçu (amount - fee)');

            $table->string('currency', 3)->default('XOF');

            // Méthode (dénormalisée pour historique)
            $table->string('method', 30)
                ->comment('orange_money, wave, bank_transfer');

            $table->string('destination')
                ->comment('Destination : numéro, IBAN');

            $table->string('holder_name')->nullable();
            $table->string('bank_name')->nullable();

            // Statut
            $table->string('status', 30)->default('pending')
                ->comment('pending, processing, completed, failed, cancelled');

            // Traitement
            $table->text('rejection_reason')->nullable()
                ->comment('Raison du refus (si refusé)');

            $table->text('admin_note')->nullable()
                ->comment('Note interne admin');

            // Provider externe (si payout via API)
            $table->string('provider', 50)->nullable()
                ->comment('orange, cinetpay, etc.');

            $table->string('provider_reference')->nullable()
                ->comment('ID de transaction chez le provider');

            $table->text('provider_response')->nullable()
                ->comment('Réponse du provider (JSON ou texte)');

            // Dates de traitement
            $table->timestamp('requested_at')->useCurrent()
                ->comment('Date de la demande');

            $table->timestamp('processed_at')->nullable()
                ->comment('Date de traitement');

            $table->timestamp('completed_at')->nullable()
                ->comment('Date de complétion');

            $table->foreignId('processed_by')
                ->nullable()
                ->constrained('users')
                ->onDelete('set null')
                ->comment('Admin qui a traité');

            $table->timestamps();

            // Index
            $table->index('payout_id');
            $table->index('seller_id');
            $table->index('wallet_id');
            $table->index('status');
            $table->index('method');
            $table->index(['seller_id', 'status']);
            $table->index('requested_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payouts');
    }
};
