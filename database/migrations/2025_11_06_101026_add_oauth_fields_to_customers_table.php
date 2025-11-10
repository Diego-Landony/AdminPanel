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
            $table->string('google_id')->nullable()->unique()->after('password');
            $table->text('avatar')->nullable()->after('google_id');
            $table->enum('oauth_provider', ['local', 'google'])->default('local')->after('avatar');

            $table->index('google_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropIndex(['google_id']);
            $table->dropColumn(['google_id', 'avatar', 'oauth_provider']);
        });
    }
};
