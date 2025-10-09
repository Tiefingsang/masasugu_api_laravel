<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();

            // Relation avec l'utilisateur propriétaire
            //$table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable();


            // Informations de base de l'entreprise
            $table->string('name');
            $table->string('logo')->nullable();
            $table->string('country')->nullable();
            $table->string('address')->nullable();
            $table->string('license_number')->nullable();
            $table->string('website')->nullable();

            // Statut de vérification
            $table->boolean('is_verified')->default(false);

            // Informations de contact
            $table->string('contact_email')->nullable();
            $table->string('contact_phone')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
