<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds promotion_id and promotion_snapshot to order_items for analytics.
     * This allows tracking which promotion was applied to each item:
     * - Sub del Día (daily_special)
     * - 2x1 (two_for_one)
     * - Descuentos % (percentage_discount)
     * - Combinados (bundle_special)
     *
     * Using soft delete on promotions, this creates a historical record
     * for future analysis even if the promotion is deleted.
     */
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            // Foreign key to track which promotion was applied (nullable)
            $table->foreignId('promotion_id')
                ->nullable()
                ->after('combo_id')
                ->constrained('promotions')
                ->nullOnDelete();

            // Snapshot of promotion data at time of order for historical analysis
            $table->json('promotion_snapshot')->nullable()->after('promotion_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropForeign(['promotion_id']);
            $table->dropColumn(['promotion_id', 'promotion_snapshot']);
        });
    }
};
