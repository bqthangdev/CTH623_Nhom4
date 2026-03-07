<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vouchers', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('type')->default('percent'); // percent | fixed
            $table->decimal('value', 15, 2)->unsigned();
            $table->decimal('min_order', 15, 2)->unsigned()->default(0);
            $table->unsignedInteger('max_uses')->nullable();        // null = unlimited
            $table->unsignedInteger('used_count')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('voucher_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('subtotal', 15, 2)->unsigned();
            $table->decimal('discount', 15, 2)->unsigned()->default(0);
            $table->decimal('shipping_fee', 15, 2)->unsigned()->default(0);
            $table->decimal('total', 15, 2)->unsigned();
            $table->string('status')->default('pending');
            // pending | confirmed | shipping | delivered | cancelled
            $table->string('payment_method')->default('cod'); // cod | vnpay
            $table->string('payment_status')->default('unpaid'); // unpaid | paid
            $table->text('shipping_address');
            $table->string('phone', 20);
            $table->string('recipient_name');
            $table->text('note')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'status']);
            $table->index(['status', 'created_at']);
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->string('product_name');      // snapshot tên sản phẩm lúc đặt
            $table->decimal('price', 15, 2)->unsigned();
            $table->unsignedInteger('quantity');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('vouchers');
    }
};
