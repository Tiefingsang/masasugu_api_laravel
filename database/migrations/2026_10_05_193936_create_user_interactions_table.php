<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_interactions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->onDelete('cascade');

            $table->foreignId('product_id')
                ->nullable()
                ->constrained()
                ->onDelete('set null');

            $table->foreignId('category_id')
                ->nullable()
                ->constrained('categories')
                ->onDelete('set null');

            $table->enum('type', [
                'view',        // 👁️ consultation produit
                'like',        // ❤️ like
                'unlike',      // 💔 unlike
                'add_to_cart', // 🛒 ajout panier
                'purchase',    // 💰 achat
                'search',      // 🔍 recherche
                'share',       // 📤 partage
            ]);

            $table->integer('weight')->default(1);
            $table->string('keyword')->nullable(); // 🔍 pour les recherches

            $table->timestamps();

            // 📊 Index pour les requêtes de recommandation
            $table->index(['user_id', 'type', 'created_at']);
            $table->index(['user_id', 'category_id']);
            $table->index(['user_id', 'created_at']);
            $table->index(['product_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_interactions');
    }
};
