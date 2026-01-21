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
        // Remove unused trust_score column from customer_devices
        Schema::table('customer_devices', function (Blueprint $table) {
            if (Schema::hasColumn('customer_devices', 'trust_score')) {
                $table->dropColumn('trust_score');
            }
        });

        // Add composite index to customer_points_transactions for common queries
        Schema::table('customer_points_transactions', function (Blueprint $table) {
            $table->index(['customer_id', 'type', 'is_expired'], 'cpt_customer_type_expired_idx');
            $table->index(['customer_id', 'created_at'], 'cpt_customer_created_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customer_devices', function (Blueprint $table) {
            $table->integer('trust_score')->default(100)->after('login_count');
        });

        Schema::table('customer_points_transactions', function (Blueprint $table) {
            $table->dropIndex('cpt_customer_type_expired_idx');
            $table->dropIndex('cpt_customer_created_idx');
        });
    }
};
