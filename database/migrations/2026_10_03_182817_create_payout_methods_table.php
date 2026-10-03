<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Table payout_methods — Moyens de retrait enregistrés par les vendeurs
     *
     * Chaque vendeur peut enregistrer plusieurs moyens de retrait :
     * - Orange Money
     * - Wave
     * - MTN MoMo
     * - Moov Money
     * - Compte bancaire
     *
     * Il choisit un moyen par défaut pour ses retraits.
     */
    public function up(): void
    {
        Schema::create('payout_methods', function (Blueprint $table) {
            $table->id();

            // Vendeur
            $table->foreignId('seller_id')
                ->constrained('users')
                ->onDelete('cascade');

            // Type de méthode
            $table->string('type', 30)
                ->comment('orange_money, wave, mtn_momo, moov_money, bank_transfer');

            // Destinataire
            $table->string('destination')
                ->comment('Numéro mobile money, IBAN, ou numéro de compte');

            // Infos complémentaires
            $table->string('label')->nullable()
                ->comment('Nom personnalisé : "Mon Orange Money"');

            $table->string('holder_name')->nullable()
                ->comment('Nom du titulaire du compte');

            $table->string('bank_name')->nullable()
                ->comment('Nom de la banque (pour bank_transfer)');

            $table->string('country_code', 2)->nullable()
                ->comment('Pays de la méthode : ML, CI, SN');

            $table->string('currency', 3)->default('XOF')
                ->comment('Devise de la méthode');

            // Statut
            $table->boolean('is_default')->default(false)
                ->comment('Méthode par défaut du vendeur');

            $table->boolean('is_verified')->default(false)
                ->comment('Méthode vérifiée par Masasugu');

            $table->boolean('is_active')->default(true)
                ->comment('Méthode active');

            // Vérification
            $table->timestamp('verified_at')->nullable()
                ->comment('Date de vérification');

            $table->text('verification_note')->nullable()
                ->comment('Notes de vérification');

            $table->timestamps();

            // Index
            $table->index('seller_id');
            $table->index('type');
            $table->index(['seller_id', 'is_default']);
            $table->index('is_active');
            $table->index('is_verified');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payout_methods');
    }
};
