<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Simplifica el flujo de estados eliminando 'confirmed'.
     * Nuevo flujo: pending -> preparing -> ready -> completed/delivered
     */
    public function up(): void
    {
        // Convertir órdenes con status 'confirmed' a 'preparing'
        DB::table('orders')
            ->where('status', 'confirmed')
            ->update(['status' => 'preparing']);

        // Actualizar historial de estados
        DB::table('order_status_history')
            ->where('previous_status', 'confirmed')
            ->update(['previous_status' => 'preparing']);

        DB::table('order_status_history')
            ->where('new_status', 'confirmed')
            ->update(['new_status' => 'preparing']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No es reversible de forma segura - los datos originales se perdieron
    }
};
