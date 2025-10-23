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
        Schema::table('customers', function (Blueprint $table) {
            // Eliminar la columna location (que era varchar)
            $table->dropColumn('location');

            // Agregar coordenadas GPS
            $table->decimal('latitude', 10, 7)->nullable()->after('address');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            // Restaurar la columna location
            $table->string('location')->nullable()->after('address');

            // Eliminar las coordenadas
            $table->dropColumn(['latitude', 'longitude']);
        });
    }
};
