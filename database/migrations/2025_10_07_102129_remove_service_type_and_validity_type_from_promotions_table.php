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
        Schema::table('promotions', function (Blueprint $table) {
            // Estos campos solo deben existir en promotion_items, no en promotions
            $table->dropColumn(['service_type', 'validity_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('promotions', function (Blueprint $table) {
            $table->enum('service_type', ['both', 'delivery_only', 'pickup_only'])->default('both');
            $table->enum('validity_type', ['permanent', 'date_range', 'time_range', 'date_time_range', 'weekdays'])->default('permanent');
        });
    }
};
