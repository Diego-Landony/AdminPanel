<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_devices', function (Blueprint $table) {
            $table->integer('trust_score')->default(50)->after('login_count');
        });
    }

    public function down(): void
    {
        Schema::table('customer_devices', function (Blueprint $table) {
            $table->dropColumn('trust_score');
        });
    }
};
