<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Agrega campos de motorista a la tabla de ordenes
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('driver_id')->nullable()->after('restaurant_id')->constrained('drivers')->nullOnDelete();
            $table->timestamp('assigned_to_driver_at')->nullable()->after('delivered_at');
            $table->timestamp('picked_up_at')->nullable()->after('assigned_to_driver_at');

            $table->index('driver_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['driver_id']);
            $table->dropIndex(['driver_id']);
            $table->dropColumn(['driver_id', 'assigned_to_driver_at', 'picked_up_at']);
        });
    }
};
