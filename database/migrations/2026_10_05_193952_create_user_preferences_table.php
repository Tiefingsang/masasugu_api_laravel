<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_preferences', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->unique()
                ->constrained()
                ->onDelete('cascade');

            $table->json('top_categories')->nullable();  // [7, 3, 1, 8, 5]
            $table->json('top_brands')->nullable();      // ["Dove", "Nike"]
            $table->json('top_keywords')->nullable();    // ["montre", "parfum"]
            $table->json('price_range')->nullable();     // {"min": 2000, "max": 15000}

            $table->boolean('tracking_enabled')->default(true);
            $table->timestamp('last_computed_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_preferences');
    }
};
