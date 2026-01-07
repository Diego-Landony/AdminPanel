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
        Schema::table('promotional_banners', function (Blueprint $table) {
            $table->time('time_from')->nullable()->after('valid_until');
            $table->time('time_until')->nullable()->after('time_from');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('promotional_banners', function (Blueprint $table) {
            $table->dropColumn(['time_from', 'time_until']);
        });
    }
};
