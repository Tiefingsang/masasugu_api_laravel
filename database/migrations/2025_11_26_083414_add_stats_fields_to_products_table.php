<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('rating', 3, 2)->default(0);   // ex: 4.50
            $table->integer('views')->default(0);          // nombre de vues
            $table->integer('sales_count')->default(0);    // nombre de ventes
            $table->integer('likes')->default(0);          // nombre de likes
        });
    }

    public function down()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['rating', 'views', 'sales_count', 'likes']);
        });
    }
};
