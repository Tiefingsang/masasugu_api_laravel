<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            // Ajout de la colonne (nullable pour éviter les erreurs avec les données existantes)
            $table->unsignedBigInteger('shop_category_id')->nullable()->after('user_id');

            // Ajout de la clé étrangère
            $table->foreign('shop_category_id')
                  ->references('id')
                  ->on('shop_categories')
                  ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            // Suppression propre si rollback
            $table->dropForeign(['shop_category_id']);
            $table->dropColumn('shop_category_id');
        });
    }
};
