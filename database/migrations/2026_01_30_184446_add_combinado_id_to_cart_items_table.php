<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Agrega soporte para Combinados (bundle_special promotions) en el carrito.
     * combinado_id referencia a promotions donde type='bundle_special'
     */
    public function up(): void
    {
        Schema::table('cart_items', function (Blueprint $table) {
            $table->foreignId('combinado_id')
                ->nullable()
                ->after('combo_id')
                ->constrained('promotions')
                ->nullOnDelete();

            // JSON para guardar las selecciones del combinado (igual que combo_selections)
            $table->json('combinado_selections')->nullable()->after('combo_selections');

            $table->index('combinado_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cart_items', function (Blueprint $table) {
            $table->dropForeign(['combinado_id']);
            $table->dropColumn(['combinado_id', 'combinado_selections']);
        });
    }
};
