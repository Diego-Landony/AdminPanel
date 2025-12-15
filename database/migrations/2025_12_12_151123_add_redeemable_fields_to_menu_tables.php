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
        // Add redeemable fields to products table
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('is_redeemable')->default(false)->after('precio_domicilio_interior');
            $table->unsignedInteger('points_cost')->nullable()->after('is_redeemable');
        });

        // Add redeemable fields to product_variants table
        Schema::table('product_variants', function (Blueprint $table) {
            $table->boolean('is_redeemable')->default(false)->after('precio_domicilio_interior');
            $table->unsignedInteger('points_cost')->nullable()->after('is_redeemable');
        });

        // Add redeemable fields to combos table
        Schema::table('combos', function (Blueprint $table) {
            $table->boolean('is_redeemable')->default(false)->after('precio_domicilio_interior');
            $table->unsignedInteger('points_cost')->nullable()->after('is_redeemable');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['is_redeemable', 'points_cost']);
        });

        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropColumn(['is_redeemable', 'points_cost']);
        });

        Schema::table('combos', function (Blueprint $table) {
            $table->dropColumn(['is_redeemable', 'points_cost']);
        });
    }
};
