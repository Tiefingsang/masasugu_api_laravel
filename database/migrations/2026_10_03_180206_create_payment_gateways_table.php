<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Table payment_gateways — Configuration des gateways de paiement par pays
     *
     * Permet de :
     * - Afficher les moyens de paiement disponibles selon le pays
     * - Stocker les clés API de chaque gateway (chiffrées)
     * - Activer/désactiver un gateway sans redéployer
     * - Passer du sandbox à la production
     * - Ajouter un nouveau gateway en 1 ligne
     */
    public function up(): void
    {
        Schema::create('payment_gateways', function (Blueprint $table) {
            $table->id();

            // Localisation
            $table->string('country_code', 2)->comment('Code pays : ML, CI, FR');
            $table->json('currencies')->comment('Devises supportées : ["XOF"]');

            // Identification gateway
            $table->string('gateway')->comment('Code gateway : orange, cinetpay, stripe');
            $table->string('display_name')->comment('Nom affiché : "Orange Money Mali"');
            $table->string('payment_method')->comment('Type : mobile_money, card, bank_transfer, cash, wallet');
            $table->string('provider')->nullable()->comment('Provider : orange, wave, mtn, moov');

            // Configuration (clés API, URLs, etc.)
            $table->json('config')->nullable()->comment('Clés API et config spécifique');

            // Affichage
            $table->integer('priority')->default(0)->comment('Ordre d\'affichage (1 = premier)');
            $table->string('logo_url')->nullable()->comment('URL du logo (optionnel)');
            $table->text('description')->nullable()->comment('Description pour l\'utilisateur');

            // Statut
            $table->boolean('is_active')->default(true)->comment('Actif sur la plateforme');
            $table->boolean('is_sandbox')->default(true)->comment('true = test, false = production');

            $table->timestamps();

            // Contraintes
            $table->foreign('country_code')
                ->references('code')
                ->on('countries')
                ->onDelete('cascade');

            // Index
            $table->index('country_code');
            $table->index('gateway');
            $table->index('payment_method');
            $table->index('is_active');
            $table->index(['country_code', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_gateways');
    }
};
