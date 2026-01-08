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
     * Actualiza la tabla promotions para usar 4 precios independientes en Combinados:
     * - special_bundle_price_capital -> special_bundle_price_pickup_capital
     * - special_bundle_price_interior -> special_bundle_price_pickup_interior
     * - (nuevo) special_bundle_price_delivery_capital
     * - (nuevo) special_bundle_price_delivery_interior
     */
    public function up(): void
    {
        // Paso 1: Renombrar columnas existentes
        Schema::table('promotions', function (Blueprint $table) {
            $table->renameColumn('special_bundle_price_capital', 'special_bundle_price_pickup_capital');
            $table->renameColumn('special_bundle_price_interior', 'special_bundle_price_pickup_interior');
        });

        // Paso 2: Agregar nuevas columnas para delivery
        Schema::table('promotions', function (Blueprint $table) {
            $table->decimal('special_bundle_price_delivery_capital', 10, 2)
                ->nullable()
                ->after('special_bundle_price_pickup_capital');
            $table->decimal('special_bundle_price_delivery_interior', 10, 2)
                ->nullable()
                ->after('special_bundle_price_pickup_interior');
        });

        // Paso 3: Copiar datos de pickup a delivery (mismo precio inicialmente)
        DB::statement('
            UPDATE promotions
            SET special_bundle_price_delivery_capital = special_bundle_price_pickup_capital,
                special_bundle_price_delivery_interior = special_bundle_price_pickup_interior
            WHERE type = "bundle_special"
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Paso 1: Eliminar columnas de delivery
        Schema::table('promotions', function (Blueprint $table) {
            $table->dropColumn(['special_bundle_price_delivery_capital', 'special_bundle_price_delivery_interior']);
        });

        // Paso 2: Renombrar columnas de vuelta
        Schema::table('promotions', function (Blueprint $table) {
            $table->renameColumn('special_bundle_price_pickup_capital', 'special_bundle_price_capital');
            $table->renameColumn('special_bundle_price_pickup_interior', 'special_bundle_price_interior');
        });
    }
};
