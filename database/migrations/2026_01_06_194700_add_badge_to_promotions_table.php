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
        Schema::table('promotions', function (Blueprint $table) {
            $table->foreignId('badge_type_id')
                ->nullable()
                ->after('image')
                ->constrained('badge_types')
                ->nullOnDelete();

            $table->boolean('show_badge_on_menu')
                ->default(true)
                ->after('badge_type_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('promotions', function (Blueprint $table) {
            $table->dropForeignIfExists('promotions_badge_type_id_foreign');
            $table->dropColumn(['badge_type_id', 'show_badge_on_menu']);
        });
    }
};
