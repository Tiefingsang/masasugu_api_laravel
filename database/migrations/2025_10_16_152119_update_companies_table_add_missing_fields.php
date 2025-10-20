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
        Schema::table('companies', function (Blueprint $table) {
            // 🔹 Nouveau champs ajoutés
            if (!Schema::hasColumn('companies', 'slug')) {
                $table->string('slug')->unique()->after('name');
            }

            if (!Schema::hasColumn('companies', 'description')) {
                $table->text('description')->nullable()->after('logo');
            }

            if (!Schema::hasColumn('companies', 'city')) {
                $table->string('city')->nullable()->after('country');
            }

            if (!Schema::hasColumn('companies', 'postal_code')) {
                $table->string('postal_code')->nullable()->after('city');
            }

            if (!Schema::hasColumn('companies', 'facebook')) {
                $table->string('facebook')->nullable()->after('website');
            }

            if (!Schema::hasColumn('companies', 'instagram')) {
                $table->string('instagram')->nullable()->after('facebook');
            }

            if (!Schema::hasColumn('companies', 'tiktok')) {
                $table->string('tiktok')->nullable()->after('instagram');
            }

            if (!Schema::hasColumn('companies', 'category_id')) {
                $table->unsignedBigInteger('category_id')->nullable()->after('tiktok');
            }

            if (!Schema::hasColumn('companies', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('is_verified');
            }

            if (!Schema::hasColumn('companies', 'status')) {
                $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending')->after('is_active');
            }

            // 🔹 Ajout de la clé étrangère si la table "categories" existe
            if (Schema::hasTable('categories')) {
                $table->foreign('category_id')->references('id')->on('categories')->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn([
                'slug',
                'description',
                'city',
                'postal_code',
                'facebook',
                'instagram',
                'tiktok',
                'category_id',
                'is_active',
                'status',
            ]);
        });
    }
};
