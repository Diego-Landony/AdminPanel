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
        Schema::table('customer_points_transactions', function (Blueprint $table) {
            $table->timestamp('expires_at')->nullable()->after('description');
            $table->boolean('is_expired')->default(false)->after('expires_at');
            $table->index(['customer_id', 'is_expired', 'expires_at'], 'cpt_expiration_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customer_points_transactions', function (Blueprint $table) {
            $table->dropIndex('cpt_expiration_idx');
            $table->dropColumn(['expires_at', 'is_expired']);
        });
    }
};
