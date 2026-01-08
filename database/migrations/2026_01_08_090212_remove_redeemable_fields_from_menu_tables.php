<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Elimina campos de redención de puntos de productos, variantes y combos.
 * La redención de puntos solo se maneja en tienda física, no en la app.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
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

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('points_redeemed');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('is_redeemable')->default(false)->after('is_active');
            $table->integer('points_cost')->nullable()->after('is_redeemable');
        });

        Schema::table('product_variants', function (Blueprint $table) {
            $table->boolean('is_redeemable')->default(false)->after('is_active');
            $table->integer('points_cost')->nullable()->after('is_redeemable');
        });

        Schema::table('combos', function (Blueprint $table) {
            $table->boolean('is_redeemable')->default(false)->after('is_active');
            $table->integer('points_cost')->nullable()->after('is_redeemable');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->integer('points_redeemed')->default(0)->after('points_earned');
        });
    }
};
