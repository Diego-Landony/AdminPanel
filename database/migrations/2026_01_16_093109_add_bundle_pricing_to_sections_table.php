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
        Schema::table('sections', function (Blueprint $table) {
            $table->boolean('bundle_discount_enabled')->default(false)->after('max_selections');
            $table->unsignedTinyInteger('bundle_size')->default(2)->after('bundle_discount_enabled');
            $table->decimal('bundle_discount_amount', 8, 2)->nullable()->after('bundle_size');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sections', function (Blueprint $table) {
            $table->dropColumn(['bundle_discount_enabled', 'bundle_size', 'bundle_discount_amount']);
        });
    }
};
