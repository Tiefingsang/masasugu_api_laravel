<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Couleurs : tableau JSON ex. ["rouge", "bleu"]
            $table->json('colors')->nullable()->after('brand');

            // Tailles : tableau JSON ex. ["S", "M", "L", "XL"]
            $table->json('sizes')->nullable()->after('colors');

            // État : nouveau, comme_neuf, bon_etat, usage, reconditionne
            $table->string('condition')->default('nouveau')->after('sizes');

            // Quantité minimum par défaut 1
            $table->integer('min_quantity')->default(1)->after('condition');

            // Audience : public, homme, femme, enfant
            $table->string('audience')->default('public')->after('min_quantity');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'colors',
                'sizes',
                'condition',
                'min_quantity',
                'audience',
            ]);
        });
    }
};
