<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Table seller_wallets — Portefeuille financier de chaque vendeur
     *
     * 1 wallet par vendeur. Contient :
     * - available_balance : argent disponible immédiatement
     * - pending_balance   : argent en attente (délai de sécurité)
     * - total_earned      : total gagné depuis le début
     * - total_withdrawn   : total retiré depuis le début
     */
    public function up(): void
    {
        Schema::create('seller_wallets', function (Blueprint $table) {
            $table->id();

            // Vendeur (unique)
            $table->foreignId('seller_id')
                ->unique()
                ->constrained('users')
                ->onDelete('cascade')
                ->comment('Un wallet par vendeur');

            // Devise
            $table->string('currency', 3)->default('XOF')
                ->comment('Devise du wallet : XOF, EUR, USD');

            // Soldes
            $table->decimal('available_balance', 15, 2)->default(0)
                ->comment('Solde disponible immédiatement');

            $table->decimal('pending_balance', 15, 2)->default(0)
                ->comment('Solde en attente (délai de sécurité)');

            // Statistiques cumulées
            $table->decimal('total_earned', 15, 2)->default(0)
                ->comment('Total gagné depuis le début');

            $table->decimal('total_withdrawn', 15, 2)->default(0)
                ->comment('Total retiré depuis le début');

            $table->decimal('total_commission_paid', 15, 2)->default(0)
                ->comment('Total commission Masasugu payée');

            // Statut
            $table->boolean('is_active')->default(true)
                ->comment('Wallet actif ?');

            // Dernier mouvement
            $table->timestamp('last_transaction_at')->nullable()
                ->comment('Date du dernier mouvement');

            $table->timestamps();

            // Index
            $table->index('seller_id');
            $table->index('currency');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seller_wallets');
    }
};
