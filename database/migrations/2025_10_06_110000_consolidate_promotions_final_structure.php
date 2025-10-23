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
     * Migración consolidada de promociones - estructura final
     * - Actualiza ENUM de type en promotions
     * - Elimina columnas obsoletas de promotions
     * - Agrega campos necesarios a promotion_items
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

        // Eliminar columnas obsoletas de promotions
        Schema::table('promotions', function (Blueprint $table) {
            $columnsToRemove = [
                'discount_value', 'applies_to', 'is_permanent', 'valid_from',
                'valid_until', 'has_time_restriction', 'time_from', 'time_until',
                'active_days', 'service_type', 'validity_type',
            ];

            foreach ($columnsToRemove as $column) {
                if (Schema::hasColumn('promotions', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        // ================== TABLA PROMOTION_ITEMS ==================
        Schema::table('promotion_items', function (Blueprint $table) {
            // Agregar campos de precio especial (Sub del Día)
            if (! Schema::hasColumn('promotion_items', 'special_price_capital')) {
                $table->decimal('special_price_capital', 10, 2)->nullable()->after('category_id');
            }

            if (! Schema::hasColumn('promotion_items', 'special_price_interior')) {
                $table->decimal('special_price_interior', 10, 2)->nullable()->after('special_price_capital');
            }

            // Agregar service_type
            if (! Schema::hasColumn('promotion_items', 'service_type')) {
                $table->enum('service_type', ['both', 'delivery_only', 'pickup_only'])
                    ->nullable()
                    ->after('special_price_interior');
            }

            // Agregar validity_type
            if (! Schema::hasColumn('promotion_items', 'validity_type')) {
                $table->enum('validity_type', ['permanent', 'date_range', 'time_range', 'date_time_range', 'weekdays'])
                    ->default('weekdays')
                    ->after('service_type');
            }

            // Agregar campos de fecha
            if (! Schema::hasColumn('promotion_items', 'valid_from')) {
                $table->date('valid_from')->nullable()->after('validity_type');
            }

            if (! Schema::hasColumn('promotion_items', 'valid_until')) {
                $table->date('valid_until')->nullable()->after('valid_from');
            }

            // Agregar campos de hora
            if (! Schema::hasColumn('promotion_items', 'time_from')) {
                $table->time('time_from')->nullable()->after('valid_until');
            }

            if (! Schema::hasColumn('promotion_items', 'time_until')) {
                $table->time('time_until')->nullable()->after('time_from');
            }

            // Agregar weekdays
            if (! Schema::hasColumn('promotion_items', 'weekdays')) {
                $table->json('weekdays')->nullable()->after('time_until');
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
};
