<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_devices', function (Blueprint $table) {
            $table->dropUnique(['fcm_token']);
            $table->string('fcm_token')->nullable()->change();
            $table->unique('fcm_token');
        });
    }

    public function down(): void
    {
        Schema::table('customer_devices', function (Blueprint $table) {
            $table->dropUnique(['fcm_token']);
            $table->string('fcm_token')->nullable(false)->change();
            $table->unique('fcm_token');
        });
    }
};
