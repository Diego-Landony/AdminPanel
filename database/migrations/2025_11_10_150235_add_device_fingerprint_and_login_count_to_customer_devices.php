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
        Schema::table('customer_devices', function (Blueprint $table) {
            $table->string('device_fingerprint')->nullable()->after('device_identifier');
            $table->integer('login_count')->default(0)->after('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customer_devices', function (Blueprint $table) {
            $table->dropColumn(['device_fingerprint', 'login_count']);
        });
    }
};
