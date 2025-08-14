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
        // Primero actualizar los nombres para usar nombres legibles
        DB::table('roles')->where('name', 'admin')->update(['name' => 'Administrador']);
        
        // Actualizar cualquier otro rol que pueda existir
        $roles = DB::table('roles')->whereNotIn('name', ['Administrador'])->get();
        foreach ($roles as $role) {
            if (isset($role->display_name) && $role->display_name) {
                DB::table('roles')->where('id', $role->id)->update(['name' => $role->display_name]);
            }
        }
        
        // Eliminar la columna display_name
        Schema::table('roles', function (Blueprint $table) {
            $table->dropColumn('display_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Agregar la columna display_name de vuelta
        Schema::table('roles', function (Blueprint $table) {
            $table->string('display_name')->after('name');
        });
        
        // Restaurar los valores
        DB::table('roles')->where('name', 'Administrador')->update([
            'name' => 'admin',
            'display_name' => 'Administrador'
        ]);
    }
};
