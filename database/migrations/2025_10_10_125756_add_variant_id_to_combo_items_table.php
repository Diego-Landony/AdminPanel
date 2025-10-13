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
        Schema::table('combo_items', function (Blueprint $table) {
            // Agregar variant_id (nullable, para productos sin variantes)
            $table->foreignId('variant_id')->nullable()->after('product_id')
                ->constrained('product_variants')
                ->onDelete('restrict')
                ->onUpdate('restrict');

            // Hacer label nullable (no siempre es necesario)
            $table->string('label', 100)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('combo_items', function (Blueprint $table) {
            $table->dropForeign(['variant_id']);
            $table->dropColumn('variant_id');
            $table->string('label', 100)->nullable(false)->change();
        });
    }
};
