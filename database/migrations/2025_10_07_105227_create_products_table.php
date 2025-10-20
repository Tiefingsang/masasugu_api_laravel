<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void{
        Schema::create('products', function (Blueprint $table) {
            $table->id();

            
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('company_id')->nullable()->constrained()->onDelete('cascade');

            // Informations produit
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('sku')->nullable(); 
            $table->text('description')->nullable();
            $table->text('specifications')->nullable();

            // Tarification
            $table->decimal('price', 15, 2)->default(0);
            $table->decimal('discount_price', 15, 2)->nullable();
            $table->string('currency', 10)->default('XOF');

            // Inventaire
            $table->integer('stock')->default(0);
            $table->boolean('is_available')->default(true);

            // Catégorisation
            //$table->foreignId('category_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();

            $table->string('brand')->nullable();

            // Médias
            $table->string('main_image')->nullable();
            $table->string('video')->nullable();

            // Statut
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');

            // SEO
            $table->string('meta_title')->nullable();
            $table->string('meta_keywords')->nullable();
            $table->text('meta_description')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
