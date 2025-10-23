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
            $table->renameColumn('puntos', 'points');
            $table->renameColumn('puntos_updated_at', 'points_updated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->renameColumn('points', 'puntos');
            $table->renameColumn('points_updated_at', 'puntos_updated_at');
        });
    }
};
