<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Step 1: Add a plain index on user_id so the composite unique can be safely dropped
        // (MySQL uses the composite unique as the FK support index for user_id)
        Schema::table('reviews', function (Blueprint $table) {
            $table->index('user_id', 'reviews_user_id_fk_support');
        });

        Schema::table('reviews', function (Blueprint $table) {
            // Drop old unique constraint (one review per user+product globally)
            $table->dropUnique('reviews_user_id_product_id_unique');

            // Allow tracking which order this review belongs to
            $table->foreignId('order_id')->nullable()->after('product_id')
                ->constrained()->nullOnDelete();

            // New unique: one review per user+product+order combination
            $table->unique(['user_id', 'product_id', 'order_id']);

            // Remove the temporary plain user_id index (now covered by the new composite unique)
            $table->dropIndex('reviews_user_id_fk_support');
        });
    }

    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->index('user_id', 'reviews_user_id_fk_support');
        });

        Schema::table('reviews', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'product_id', 'order_id']);
            $table->dropForeign(['order_id']);
            $table->dropColumn('order_id');
            $table->unique(['user_id', 'product_id']);
            $table->dropIndex('reviews_user_id_fk_support');
        });
    }
};
