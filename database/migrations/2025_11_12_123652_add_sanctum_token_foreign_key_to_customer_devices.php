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
            $table->foreign('sanctum_token_id')
                ->references('id')
                ->on('personal_access_tokens')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customer_devices', function (Blueprint $table) {
            $table->dropForeign(['sanctum_token_id']);
        });
    }
};
