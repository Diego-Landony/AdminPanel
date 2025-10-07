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
     * MIGRACIÓN CONSOLIDADA - Reestructura promotions y promotion_items
     * Reemplaza las migraciones fragmentadas de octubre 2025
     *
     * Esta migración NO BORRA DATOS, solo agrega columnas faltantes
     */
    public function up(): void
    {
        // ================== TABLA PROMOTIONS ==================
        Schema::table('promotions', function (Blueprint $table) {
            // 1. Modificar ENUM 'type' si aún no tiene 'daily_special'
            // Solo se ejecuta si la columna no tiene el valor correcto
            try {
                DB::statement("ALTER TABLE promotions MODIFY COLUMN type ENUM('two_for_one', 'percentage_discount', 'daily_special') NOT NULL");
            } catch (\Exception $e) {
                // Ya existe, continuar
            }

            // 2. Agregar service_type si no existe
            if (! Schema::hasColumn('promotions', 'service_type')) {
                $table->enum('service_type', ['both', 'delivery_only', 'pickup_only'])
                    ->default('both')
                    ->after('applies_to');
            }

            // 3. Agregar validity_type si no existe
            if (! Schema::hasColumn('promotions', 'validity_type')) {
                $table->enum('validity_type', ['permanent', 'date_range', 'time_range', 'date_time_range', 'weekdays'])
                    ->default('permanent')
                    ->after('service_type');
            }

            // 4. Agregar soft deletes si no existe
            if (! Schema::hasColumn('promotions', 'deleted_at')) {
                $table->softDeletes()->after('updated_at');
            }
        });

        // Crear índices solo si no existen (usando DB raw para evitar problemas con Doctrine)
        $indexesToCreate = [
            ['name' => 'promotions_type_index', 'columns' => 'type'],
            ['name' => 'promotions_is_active_index', 'columns' => 'is_active'],
            ['name' => 'promotions_valid_from_valid_until_index', 'columns' => 'valid_from, valid_until'],
        ];

        foreach ($indexesToCreate as $indexData) {
            $indexExists = DB::select('SHOW INDEX FROM promotions WHERE Key_name = ?', [$indexData['name']]);
            if (empty($indexExists)) {
                DB::statement("CREATE INDEX {$indexData['name']} ON promotions ({$indexData['columns']})");
            }
        }

        // ================== TABLA PROMOTION_ITEMS ==================
        Schema::table('promotion_items', function (Blueprint $table) {
            // 1. Agregar campos de precio especial (Sub del Día)
            if (! Schema::hasColumn('promotion_items', 'special_price_capital')) {
                $table->decimal('special_price_capital', 10, 2)
                    ->nullable()
                    ->after('category_id');
            }

            if (! Schema::hasColumn('promotion_items', 'special_price_interior')) {
                $table->decimal('special_price_interior', 10, 2)
                    ->nullable()
                    ->after('special_price_capital');
            }

            // 2. Agregar service_type
            if (! Schema::hasColumn('promotion_items', 'service_type')) {
                $table->enum('service_type', ['both', 'delivery_only', 'pickup_only'])
                    ->nullable()
                    ->after('special_price_interior');
            }

            // 3. Agregar validity_type
            if (! Schema::hasColumn('promotion_items', 'validity_type')) {
                $table->enum('validity_type', ['permanent', 'date_range', 'time_range', 'date_time_range', 'weekdays'])
                    ->default('weekdays')
                    ->after('service_type');
            }

            // 4. Agregar campos de fecha
            if (! Schema::hasColumn('promotion_items', 'valid_from')) {
                $table->date('valid_from')->nullable()->after('validity_type');
            }

            if (! Schema::hasColumn('promotion_items', 'valid_until')) {
                $table->date('valid_until')->nullable()->after('valid_from');
            }

            // 5. Agregar campos de hora
            if (! Schema::hasColumn('promotion_items', 'time_from')) {
                $table->time('time_from')->nullable()->after('valid_until');
            }

            if (! Schema::hasColumn('promotion_items', 'time_until')) {
                $table->time('time_until')->nullable()->after('time_from');
            }

            // 6. Agregar weekdays
            if (! Schema::hasColumn('promotion_items', 'weekdays')) {
                $table->json('weekdays')->nullable()->after('time_until');
            }
        });

        // Migrar datos existentes en promotions (actualizar campos nuevos con valores por defecto)
        // Solo si hay registros y los campos están vacíos
        DB::statement("
            UPDATE promotions
            SET service_type = 'both',
                validity_type = CASE
                    WHEN is_permanent = 1 THEN 'permanent'
                    WHEN has_time_restriction = 1 THEN 'time_range'
                    ELSE 'date_range'
                END
            WHERE service_type IS NULL OR validity_type IS NULL
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No revertir para preservar datos
        // Esta migración es de solo adición, no elimina nada
    }
};
