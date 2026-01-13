<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Agrega nullOnDelete a las foreign keys de cart_items para evitar
     * errores cuando se eliminan productos/variantes/combos.
     */
    public function up(): void
    {
        Schema::table('cart_items', function (Blueprint $table) {
            // Eliminar foreign keys existentes
            $table->dropForeign(['product_id']);
            $table->dropForeign(['variant_id']);
            $table->dropForeign(['combo_id']);

            // Recrear con nullOnDelete
            $table->foreign('product_id')
                ->references('id')
                ->on('products')
                ->nullOnDelete();

            $table->foreign('variant_id')
                ->references('id')
                ->on('product_variants')
                ->nullOnDelete();

            $table->foreign('combo_id')
                ->references('id')
                ->on('combos')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cart_items', function (Blueprint $table) {
            // Eliminar foreign keys con nullOnDelete
            $table->dropForeign(['product_id']);
            $table->dropForeign(['variant_id']);
            $table->dropForeign(['combo_id']);

            // Recrear sin nullOnDelete (comportamiento original: RESTRICT)
            $table->foreign('product_id')
                ->references('id')
                ->on('products');

            $table->foreign('variant_id')
                ->references('id')
                ->on('product_variants');

            $table->foreign('combo_id')
                ->references('id')
                ->on('combos');
        });
    }
};
