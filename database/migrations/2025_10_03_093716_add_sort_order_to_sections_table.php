<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('sections', function (Blueprint $table) {
            $table->integer('sort_order')->default(0)->after('max_selections');
        });

        // Asignar sort_order a las secciones existentes
        $sections = DB::table('sections')->orderBy('id')->get();
        foreach ($sections as $index => $section) {
            DB::table('sections')
                ->where('id', $section->id)
                ->update(['sort_order' => $index + 1]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sections', function (Blueprint $table) {
            $table->dropColumn('sort_order');
        });
    }
};
