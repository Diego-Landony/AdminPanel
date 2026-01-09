<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Agrega campos de redención de puntos a productos, variantes y combos.
 * Permite definir qué items pueden ser canjeados por puntos en tienda física.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('is_redeemable')->default(false)->after('is_active');
            $table->unsignedInteger('points_cost')->nullable()->after('is_redeemable');
        });

        Schema::table('product_variants', function (Blueprint $table) {
            $table->boolean('is_redeemable')->default(false)->after('is_active');
            $table->unsignedInteger('points_cost')->nullable()->after('is_redeemable');
        });

        Schema::table('combos', function (Blueprint $table) {
            $table->boolean('is_redeemable')->default(false)->after('is_active');
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
