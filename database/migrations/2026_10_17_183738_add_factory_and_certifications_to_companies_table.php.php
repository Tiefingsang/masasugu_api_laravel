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
            // Usine de production ?
            $table->boolean('is_factory')->default(false)->after('is_verified');

            // Bannière de la boutique
            $table->string('banner')->nullable()->after('logo');

            // Documents de certification (JSON array de chemins)
            $table->json('certification_documents')->nullable()->after('license_number');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn([
                'is_factory',
                'banner',
                'certification_documents',
            ]);
        });
    }
};
