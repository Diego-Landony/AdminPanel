<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * MÓDULO: Sistema de Promociones
     * - Promociones (2x1, descuento %, sub del día, combinados)
     * - Items de promoción (productos/variantes incluidos)
     * - Items de combinados (para promociones tipo bundle)
     * - Opciones de items de combinados
     */
    public function up(): void
    {
        // ==================== PROMOTIONS ====================
        Schema::create('promotions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('type', ['two_for_one', 'percentage_discount', 'daily_special', 'bundle_special']);

            // Campos para bundle_special
            $table->decimal('special_bundle_price_capital', 10, 2)->nullable();
            $table->decimal('special_bundle_price_interior', 10, 2)->nullable();
            $table->date('valid_from')->nullable();
            $table->date('valid_until')->nullable();
            $table->time('time_from')->nullable();
            $table->time('time_until')->nullable();
            $table->json('weekdays')->nullable();

            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            // Índices
            $table->index(['is_active']);
            $table->index(['type']);
            $table->index(['type', 'is_active'], 'idx_type_active');
            $table->index(['valid_from', 'valid_until'], 'idx_dates');
            $table->index('sort_order');
        });

        // ==================== PROMOTION_ITEMS ====================
        Schema::create('promotion_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('promotion_id')->constrained('promotions')->onDelete('cascade');
            $table->foreignId('product_id')->nullable()->constrained('products')->onDelete('cascade');
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->onDelete('cascade');
            $table->foreignId('category_id')->nullable()->constrained('categories')->onDelete('cascade');

            // Campos para daily_special
            $table->decimal('special_price_capital', 10, 2)->nullable();
            $table->decimal('special_price_interior', 10, 2)->nullable();

            // Campos para percentage_discount
            $table->decimal('discount_percentage', 5, 2)->nullable();

            // Configuración de vigencia
            $table->enum('service_type', ['both', 'delivery_only', 'pickup_only'])->nullable();
            $table->enum('validity_type', ['permanent', 'date_range', 'time_range', 'date_time_range', 'weekdays'])->default('weekdays');
            $table->date('valid_from')->nullable();
            $table->date('valid_until')->nullable();
            $table->time('time_from')->nullable();
            $table->time('time_until')->nullable();
            $table->json('weekdays')->nullable();

            $table->timestamps();

            // Índices compuestos adicionales (los simples ya se crean con foreignId)
            $table->index(['promotion_id', 'variant_id'], 'idx_promotion_variant');
            $table->index(['promotion_id', 'category_id'], 'idx_promotion_category');
            $table->index(['promotion_id', 'product_id'], 'idx_promotion_product');
        });

        // Constraint: category_id siempre requerido
        DB::statement('
            ALTER TABLE promotion_items
            ADD CONSTRAINT check_product_variant_or_category CHECK (
                (product_id IS NOT NULL AND variant_id IS NULL AND category_id IS NOT NULL) OR
                (product_id IS NOT NULL AND variant_id IS NOT NULL AND category_id IS NOT NULL) OR
                (product_id IS NULL AND variant_id IS NULL AND category_id IS NOT NULL)
            )
        ');

        // ==================== BUNDLE_PROMOTION_ITEMS ====================
        Schema::create('bundle_promotion_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('promotion_id')->constrained('promotions')->onDelete('cascade');
            $table->foreignId('product_id')->nullable()->constrained('products')->onDelete('restrict');
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->onDelete('restrict');
            $table->boolean('is_choice_group')->default(false);
            $table->string('choice_label', 255)->nullable();
            $table->unsignedInteger('quantity')->default(1);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index('promotion_id');
            $table->index('product_id');
            $table->index('variant_id');
            $table->index('sort_order');
            $table->index(['promotion_id', 'is_choice_group'], 'idx_bundle_promo_choice_group');
        });

        // ==================== BUNDLE_PROMOTION_ITEM_OPTIONS ====================
        Schema::create('bundle_promotion_item_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bundle_item_id')->constrained('bundle_promotion_items')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products')->onDelete('restrict');
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->onDelete('restrict');
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index('bundle_item_id', 'idx_bpio_bundle_item');
            $table->index('product_id', 'idx_bpio_product');
            $table->index('variant_id', 'idx_bpio_variant');
            $table->index('sort_order', 'idx_bpio_sort_order');

            $table->unique(['bundle_item_id', 'product_id', 'variant_id'], 'unique_bundle_option');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bundle_promotion_item_options');
        Schema::dropIfExists('bundle_promotion_items');
        Schema::dropIfExists('promotion_items');
        Schema::dropIfExists('promotions');
    }
};
