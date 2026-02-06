<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Agrega índice compuesto para optimizar consultas de historial de motorista
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->index(['driver_id', 'status', 'delivered_at'], 'orders_driver_history_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('orders_driver_history_index');
        });
    }
};
