<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Table countries — Référentiel des pays supportés par Masasugu
     */
    public function up(): void
    {
        Schema::create('countries', function (Blueprint $table) {
            $table->id();
            $table->string('code', 2)->unique()->comment('Code ISO 3166-1 alpha-2');
            $table->string('name')->comment('Nom en français');
            $table->string('name_en')->comment('Nom en anglais');
            $table->string('currency_code', 3)->comment('Code devise : XOF, EUR');
            $table->string('phone_code', 10)->nullable()->comment('Indicatif : +223');
            $table->string('flag', 10)->nullable()->comment('Emoji drapeau');
            $table->string('region')->nullable()->comment('Région');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('code');
            $table->index('is_active');
            $table->index('region');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('countries');
    }
};
