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
     * Limpia columnas obsoletas de la tabla promotions que quedaron
     * de una implementación fallida anterior.
     */
    public function up(): void
    {
        // Eliminar columnas obsoletas de promotions
        Schema::table('promotions', function (Blueprint $table) {
            // Verificar y eliminar solo si existen
            if (Schema::hasColumn('promotions', 'discount_value')) {
                $table->dropColumn('discount_value');
            }
            if (Schema::hasColumn('promotions', 'applies_to')) {
                $table->dropColumn('applies_to');
            }
            if (Schema::hasColumn('promotions', 'is_permanent')) {
                $table->dropColumn('is_permanent');
            }
            if (Schema::hasColumn('promotions', 'valid_from')) {
                $table->dropColumn('valid_from');
            }
            if (Schema::hasColumn('promotions', 'valid_until')) {
                $table->dropColumn('valid_until');
            }
            if (Schema::hasColumn('promotions', 'has_time_restriction')) {
                $table->dropColumn('has_time_restriction');
            }
            if (Schema::hasColumn('promotions', 'time_from')) {
                $table->dropColumn('time_from');
            }
            if (Schema::hasColumn('promotions', 'time_until')) {
                $table->dropColumn('time_until');
            }
            if (Schema::hasColumn('promotions', 'active_days')) {
                $table->dropColumn('active_days');
            }
        });

        // Actualizar ENUM de type para incluir solo los tipos válidos
        DB::statement("
            ALTER TABLE promotions
            MODIFY COLUMN type
            ENUM('two_for_one', 'percentage_discount', 'daily_special')
            NOT NULL
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No revertir - las columnas obsoletas no son necesarias
        // Esta es una migración de limpieza unidireccional
    }
};
