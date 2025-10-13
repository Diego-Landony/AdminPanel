<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Eliminar índices y columna slug de la tabla products
        Schema::table('products', function (Blueprint $table) {
            $table->dropUnique('products_slug_unique');
            $table->dropColumn('slug');
        });

        // Eliminar índices y columna slug de la tabla combos
        Schema::table('combos', function (Blueprint $table) {
            $table->dropUnique('combos_slug_unique');
            $table->dropIndex('combos_slug_index');
            $table->dropColumn('slug');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Restaurar columna slug en products
        Schema::table('products', function (Blueprint $table) {
            $table->string('slug')->unique()->after('name');
        });

        // Restaurar columna slug en combos
        Schema::table('combos', function (Blueprint $table) {
            $table->string('slug')->after('name');
            $table->unique('slug');
            $table->index('slug');
        });
    }
};
