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
     * Migración consolidada para promociones:
     * - Estructura de promotions
     * - Estructura de promotion_items
     * - Constraints para category_id
     */
    public function up(): void
    {
        // ================== TABLA PROMOTIONS ==================
        Schema::table('promotions', function (Blueprint $table) {
            // Agregar soft deletes si no existe
            if (! Schema::hasColumn('promotions', 'deleted_at')) {
                $table->softDeletes()->after('updated_at');
            }
        });

        // Actualizar ENUM de type
        DB::statement("
            ALTER TABLE promotions
            MODIFY COLUMN type
            ENUM('two_for_one', 'percentage_discount', 'daily_special')
            NOT NULL
        ");

        // Eliminar columnas obsoletas de promotions (las que ya no se usan a nivel de promoción)
        Schema::table('promotions', function (Blueprint $table) {
            $columnsToRemove = [
                'discount_value', 'applies_to', 'is_permanent', 'valid_from',
                'valid_until', 'has_time_restriction', 'time_from', 'time_until',
                'active_days', 'service_type', 'validity_type', 'scope_type',
                'special_price_capital', 'special_price_interior', 'weekdays',
            ];

            foreach ($columnsToRemove as $column) {
                if (Schema::hasColumn('promotions', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        // ================== TABLA PROMOTION_ITEMS ==================
        Schema::table('promotion_items', function (Blueprint $table) {
            // Agregar campos de precio especial (Sub del Día) si no existen
            if (! Schema::hasColumn('promotion_items', 'special_price_capital')) {
                $table->decimal('special_price_capital', 10, 2)->nullable()->after('category_id');
            }

            if (! Schema::hasColumn('promotion_items', 'special_price_interior')) {
                $table->decimal('special_price_interior', 10, 2)->nullable()->after('special_price_capital');
            }

            // Agregar discount_percentage si no existe
            if (! Schema::hasColumn('promotion_items', 'discount_percentage')) {
                $table->decimal('discount_percentage', 5, 2)
                    ->nullable()
                    ->after('special_price_interior')
                    ->comment('Porcentaje de descuento (1-100) para promociones percentage_discount');
            }

            // Agregar service_type si no existe
            if (! Schema::hasColumn('promotion_items', 'service_type')) {
                $table->enum('service_type', ['both', 'delivery_only', 'pickup_only'])
                    ->nullable()
                    ->after('discount_percentage');
            }

            // Agregar validity_type si no existe
            if (! Schema::hasColumn('promotion_items', 'validity_type')) {
                $table->enum('validity_type', ['permanent', 'date_range', 'time_range', 'date_time_range', 'weekdays'])
                    ->default('weekdays')
                    ->after('service_type');
            }

            // Agregar campos de fecha si no existen
            if (! Schema::hasColumn('promotion_items', 'valid_from')) {
                $table->date('valid_from')->nullable()->after('validity_type');
            }

            if (! Schema::hasColumn('promotion_items', 'valid_until')) {
                $table->date('valid_until')->nullable()->after('valid_from');
            }

            // Agregar campos de hora si no existen
            if (! Schema::hasColumn('promotion_items', 'time_from')) {
                $table->time('time_from')->nullable()->after('valid_until');
            }

            if (! Schema::hasColumn('promotion_items', 'time_until')) {
                $table->time('time_until')->nullable()->after('time_from');
            }

            // Agregar weekdays si no existe
            if (! Schema::hasColumn('promotion_items', 'weekdays')) {
                $table->json('weekdays')->nullable()->after('time_until');
            }
        });

        // Poblar product_id para registros que tienen variant_id pero no product_id
        DB::statement('
            UPDATE promotion_items pi
            INNER JOIN product_variants pv ON pi.variant_id = pv.id
            SET pi.product_id = pv.product_id
            WHERE pi.variant_id IS NOT NULL AND pi.product_id IS NULL
        ');

        // Poblar category_id desde products para items que no lo tienen
        DB::statement('
            UPDATE promotion_items pi
            JOIN products p ON pi.product_id = p.id
            SET pi.category_id = p.category_id
            WHERE pi.product_id IS NOT NULL AND pi.category_id IS NULL
        ');

        // Eliminar el constraint viejo si existe
        try {
            DB::statement('ALTER TABLE promotion_items DROP CONSTRAINT IF EXISTS check_product_variant_or_category');
        } catch (\Exception $e) {
            // Si no existe el constraint, continuar
        }

        // Eliminar unique_promo_product si existe (permite múltiples items con mismo producto pero diferente variante)
        try {
            DB::statement('ALTER TABLE promotion_items DROP INDEX IF EXISTS unique_promo_product');
        } catch (\Exception $e) {
            // Si no existe, continuar
        }

        // Crear constraint nuevo que requiere category_id SIEMPRE:
        // - (product_id solo) + category_id
        // - (product_id + variant_id) + category_id
        // - (category_id solo) para 2x1
        DB::statement('
            ALTER TABLE promotion_items
            ADD CONSTRAINT check_product_variant_or_category CHECK (
                (product_id IS NOT NULL AND variant_id IS NULL AND category_id IS NOT NULL) OR
                (product_id IS NOT NULL AND variant_id IS NOT NULL AND category_id IS NOT NULL) OR
                (product_id IS NULL AND variant_id IS NULL AND category_id IS NOT NULL)
            )
        ');

        // Eliminar índices únicos problemáticos si existen
        try {
            DB::statement('ALTER TABLE promotion_items DROP INDEX IF EXISTS unique_promo_variant');
            DB::statement('ALTER TABLE promotion_items DROP INDEX IF EXISTS unique_promo_category');
            DB::statement('ALTER TABLE promotion_items DROP INDEX IF EXISTS unique_promo_cat_prod_var');
        } catch (\Exception $e) {
            // Si no existen, continuar
        }

        // NOTA IMPORTANTE: No creamos índices únicos restrictivos aquí porque:
        // - Daily Special: Permite múltiples items con misma variante pero diferentes weekdays
        // - Percentage: Permite múltiples productos con misma variante en una promoción
        // - Two For One: Permite solo una categoría (se valida en backend FormRequest)
        //
        // Las validaciones de negocio se manejan en:
        // - StorePromotionRequest::validateNoDuplicateProductVariantCombinations()
        // - UpdatePromotionRequest::validateNoDuplicateProductVariantCombinations()
        // - StorePromotionRequest::validateNoConflictingVariantWeekdays() para Daily Special

        // Solo agregamos índices de performance (no únicos)
        Schema::table('promotion_items', function (Blueprint $table) {
            // Índices para mejorar performance de queries
            if (! $this->indexExists('promotion_items', 'idx_promotion_variant')) {
                $table->index(['promotion_id', 'variant_id'], 'idx_promotion_variant');
            }
            if (! $this->indexExists('promotion_items', 'idx_promotion_category')) {
                $table->index(['promotion_id', 'category_id'], 'idx_promotion_category');
            }
            if (! $this->indexExists('promotion_items', 'idx_promotion_product')) {
                $table->index(['promotion_id', 'product_id'], 'idx_promotion_product');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No revertir para preservar datos
    }

    /**
     * Check if an index exists
     */
    private function indexExists(string $table, string $index): bool
    {
        $indexes = DB::select("SHOW INDEX FROM {$table} WHERE Key_name = ?", [$index]);

        return count($indexes) > 0;
    }
};
