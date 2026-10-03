<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Table settings — Configuration dynamique de Masasugu
     *
     * Permet de modifier les paramètres sans redéployer le code.
     * Exemples : commission, frais, TVA, paramètres généraux.
     */
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();           // Ex: platform_fee_percent
            $table->text('value')->nullable();         // Ex: 5
            $table->string('type')->default('string'); // string, integer, decimal, boolean, json
            $table->string('group')->default('general'); // general, payment, commission, etc.
            $table->string('label')->nullable();       // Ex: "Commission Masasugu (%)"
            $table->text('description')->nullable();   // Explication
            $table->boolean('is_public')->default(false); // Visible côté client ?
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
