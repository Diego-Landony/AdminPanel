<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Agrega soporte para Combinados (bundle_special promotions) en order_items.
     * combinado_id referencia a promotions donde type='bundle_special'
     */
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->foreignId('combinado_id')
                ->nullable()
                ->after('combo_id')
                ->constrained('promotions')
                ->nullOnDelete();

            // JSON para guardar las selecciones del combinado
            $table->json('combinado_selections')->nullable()->after('combo_selections');

            $table->index('combinado_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropForeign(['combinado_id']);
            $table->dropColumn(['combinado_id', 'combinado_selections']);
        });
    }
};
