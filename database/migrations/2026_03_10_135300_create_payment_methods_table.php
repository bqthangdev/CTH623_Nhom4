<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);                  // Tên hiển thị: "Thanh toán khi nhận hàng"
            $table->string('code', 50)->unique();          // Mã code: cod, vnpay
            $table->text('description')->nullable();       // Mô tả thêm
            $table->boolean('is_external')->default(false); // true = cần API key (vnpay, momo...)
            $table->json('config')->nullable();            // API keys và cấu hình riêng
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_methods');
    }
};
