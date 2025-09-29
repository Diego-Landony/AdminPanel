<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('customer_types', function (Blueprint $table) {
            // Primero, copiar datos de display_name a una columna temporal
            $table->string('temp_name', 100)->nullable()->after('display_name');
        });

        // Copiar los datos
        DB::statement('UPDATE customer_types SET temp_name = display_name');

        Schema::table('customer_types', function (Blueprint $table) {
            // Eliminar las columnas innecesarias
            $table->dropColumn(['name', 'display_name', 'sort_order']);
        });

        Schema::table('customer_types', function (Blueprint $table) {
            // Renombrar la columna temporal a name y hacerla unique
            $table->renameColumn('temp_name', 'name');
        });

        Schema::table('customer_types', function (Blueprint $table) {
            // Aplicar la restricción unique
            $table->unique('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customer_types', function (Blueprint $table) {
            // Restaurar las columnas eliminadas
            $table->dropUnique(['name']);
            $table->string('temp_name', 50)->unique()->after('name');
            $table->string('display_name', 100)->after('temp_name');
            $table->integer('sort_order')->default(0)->after('is_active');
        });

        // Copiar datos de vuelta
        DB::statement('UPDATE customer_types SET temp_name = LOWER(name), display_name = name');

        Schema::table('customer_types', function (Blueprint $table) {
            $table->dropColumn('name');
            $table->renameColumn('temp_name', 'name');
        });
    }
};
