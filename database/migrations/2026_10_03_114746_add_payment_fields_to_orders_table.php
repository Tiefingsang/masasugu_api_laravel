<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('payment_status')->default('unpaid')->after('status');
            $table->decimal('subtotal', 15, 2)->default(0)->after('total');
            $table->decimal('platform_fee_total', 15, 2)->default(0)->after('subtotal');
            $table->decimal('gateway_fee_total', 15, 2)->default(0)->after('platform_fee_total');
            $table->string('currency', 3)->default('XOF')->after('gateway_fee_total');
            $table->string('country', 2)->default('ML')->after('currency');
            $table->timestamp('paid_at')->nullable()->after('country');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'payment_status',
                'subtotal',
                'platform_fee_total',
                'gateway_fee_total',
                'currency',
                'country',
                'paid_at',
            ]);
        });
    }
};
