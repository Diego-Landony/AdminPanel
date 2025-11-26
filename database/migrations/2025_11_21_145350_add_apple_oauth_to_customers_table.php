<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('apple_id')->nullable()->unique()->after('google_id');
            $table->index('apple_id');
        });

        DB::statement("ALTER TABLE customers MODIFY COLUMN oauth_provider ENUM('local', 'google', 'apple') DEFAULT 'local'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE customers MODIFY COLUMN oauth_provider ENUM('local', 'google') DEFAULT 'local'");

        Schema::table('customers', function (Blueprint $table) {
            $table->dropIndex(['apple_id']);
            $table->dropColumn('apple_id');
        });
    }
};
