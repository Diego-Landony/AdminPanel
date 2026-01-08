<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Cambia de 2 precios (capital/interior) a 4 precios independientes
     * para consistencia con el sistema de productos.
     */
    public function up(): void
    {
        Schema::table('promotion_items', function (Blueprint $table) {
            // Renombrar columnas existentes
            $table->renameColumn('special_price_capital', 'special_price_pickup_capital');
            $table->renameColumn('special_price_interior', 'special_price_pickup_interior');
        });

        Schema::table('promotion_items', function (Blueprint $table) {
            // Agregar nuevas columnas para delivery
            $table->decimal('special_price_delivery_capital', 10, 2)->nullable()->after('special_price_pickup_capital');
            $table->decimal('special_price_delivery_interior', 10, 2)->nullable()->after('special_price_pickup_interior');
        });

        // Migrar datos existentes: copiar pickup a delivery (mismo precio por defecto)
        DB::table('promotion_items')
            ->whereNotNull('special_price_pickup_capital')
            ->update(['special_price_delivery_capital' => DB::raw('special_price_pickup_capital')]);

        DB::table('promotion_items')
            ->whereNotNull('special_price_pickup_interior')
            ->update(['special_price_delivery_interior' => DB::raw('special_price_pickup_interior')]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('promotion_items', function (Blueprint $table) {
            // Eliminar columnas de delivery
            $table->dropColumn(['special_price_delivery_capital', 'special_price_delivery_interior']);
        });

        Schema::table('promotion_items', function (Blueprint $table) {
            // Restaurar nombres originales
            $table->renameColumn('special_price_pickup_capital', 'special_price_capital');
            $table->renameColumn('special_price_pickup_interior', 'special_price_interior');
        });
    }
};
