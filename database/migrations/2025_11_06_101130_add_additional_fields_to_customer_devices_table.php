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
            $table->unsignedBigInteger('sanctum_token_id')->nullable()->after('customer_id');
            $table->string('device_identifier')->nullable()->unique()->after('fcm_token');
            $table->string('app_version', 20)->nullable()->after('device_model');
            $table->string('os_version', 20)->nullable()->after('app_version');

            $table->foreign('sanctum_token_id')
                ->references('id')
                ->on('personal_access_tokens')
                ->onDelete('set null');

            $table->index('sanctum_token_id');
            $table->index('device_identifier');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customer_devices', function (Blueprint $table) {
            $table->dropForeign(['sanctum_token_id']);
            $table->dropIndex(['sanctum_token_id']);
            $table->dropIndex(['device_identifier']);
            $table->dropColumn(['sanctum_token_id', 'device_identifier', 'app_version', 'os_version']);
        });
    }
};
