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
     * Agrega índices de performance faltantes para bundle_special:
     * - Índice compuesto (type, is_active) para listados filtrados
     * - Índice compuesto (valid_from, valid_until) para queries de vigencia
     */
    public function up(): void
    {
        Schema::table('promotions', function (Blueprint $table) {
            // Índice compuesto para filtrar combinados activos eficientemente
            if (! $this->indexExists('promotions', 'idx_type_active')) {
                $table->index(['type', 'is_active'], 'idx_type_active');
            }

            // Índice compuesto para queries de vigencia temporal
            if (! $this->indexExists('promotions', 'idx_dates')) {
                $table->index(['valid_from', 'valid_until'], 'idx_dates');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('promotions', function (Blueprint $table) {
            // Eliminar índices en orden inverso
            if ($this->indexExists('promotions', 'idx_dates')) {
                $table->dropIndex('idx_dates');
            }

            if ($this->indexExists('promotions', 'idx_type_active')) {
                $table->dropIndex('idx_type_active');
            }
        });
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
