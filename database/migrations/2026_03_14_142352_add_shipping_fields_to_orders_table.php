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
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('shipping_carrier_id')
                ->nullable()
                ->after('shipping_fee')
                ->constrained('shipping_carriers')
                ->nullOnDelete();
            $table->string('tracking_code', 100)->nullable()->after('shipping_carrier_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['shipping_carrier_id']);
            $table->dropColumn(['shipping_carrier_id', 'tracking_code']);
        });
    }
};
