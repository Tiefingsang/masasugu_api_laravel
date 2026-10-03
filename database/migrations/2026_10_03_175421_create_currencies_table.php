<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Table currencies — Référentiel des devises supportées
     */
    public function up(): void
    {
        Schema::create('currencies', function (Blueprint $table) {
            $table->id();
            $table->string('code', 3)->unique()->comment('Code ISO 4217 : XOF, EUR');
            $table->string('name')->comment('Nom en français');
            $table->string('name_en')->comment('Nom en anglais');
            $table->string('symbol', 10)->comment('Symbole : FCFA, €, $');
            $table->integer('decimals')->default(2)->comment('Décimales : 0 pour XOF, 2 pour EUR');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('code');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('currencies');
    }
};
