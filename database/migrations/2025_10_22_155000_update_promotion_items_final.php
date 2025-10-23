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
     * Migración consolidada final para promotion_items:
     * - Actualiza constraint para permitir product_id + variant_id juntos
     * - Agrega columna discount_percentage
     */
    public function up(): void
    {
        // Agregar columna discount_percentage
        Schema::table('promotion_items', function (Blueprint $table) {
            if (! Schema::hasColumn('promotion_items', 'discount_percentage')) {
                $table->decimal('discount_percentage', 5, 2)
                    ->nullable()
                    ->after('special_price_interior')
                    ->comment('Porcentaje de descuento (1-100) para promociones percentage_discount');
            }
        });

        // Eliminar el constraint viejo
        DB::statement('ALTER TABLE promotion_items DROP CONSTRAINT IF EXISTS check_product_variant_or_category');

        // Poblar product_id para registros que tienen variant_id pero no product_id
        DB::statement('
            UPDATE promotion_items pi
            INNER JOIN product_variants pv ON pi.variant_id = pv.id
            SET pi.product_id = pv.product_id
            WHERE pi.variant_id IS NOT NULL AND pi.product_id IS NULL
        ');

        // Crear constraint nuevo que permite:
        // - product_id solo (producto sin variante)
        // - product_id + variant_id (producto con variante específica)
        // - category_id solo (para 2x1)
        DB::statement('ALTER TABLE promotion_items ADD CONSTRAINT check_product_variant_or_category CHECK (
            (product_id IS NOT NULL AND variant_id IS NULL AND category_id IS NULL) OR
            (product_id IS NOT NULL AND variant_id IS NOT NULL AND category_id IS NULL) OR
            (product_id IS NULL AND variant_id IS NULL AND category_id IS NOT NULL)
        )');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('promotion_items', function (Blueprint $table) {
            if (Schema::hasColumn('promotion_items', 'discount_percentage')) {
                $table->dropColumn('discount_percentage');
            }
        });

        // Volver al constraint original
        DB::statement('ALTER TABLE promotion_items DROP CONSTRAINT IF EXISTS check_product_variant_or_category');

        DB::statement('ALTER TABLE promotion_items ADD CONSTRAINT check_product_variant_or_category CHECK (
            (product_id IS NOT NULL AND variant_id IS NULL AND category_id IS NULL) OR
            (product_id IS NULL AND variant_id IS NOT NULL AND category_id IS NULL) OR
            (product_id IS NULL AND variant_id IS NULL AND category_id IS NOT NULL)
        )');
    }
};
