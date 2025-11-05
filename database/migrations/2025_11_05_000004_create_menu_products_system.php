<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * MÓDULO: Sistema de Menú y Productos
     * - Categorías (con soporte para combos y variantes)
     * - Productos (con precios por zona y tipo de servicio)
     * - Variantes de productos
     * - Secciones de personalización
     * - Opciones de secciones
     */
    public function up(): void
    {
        // ==================== CATEGORIES ====================
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->boolean('uses_variants')->default(false);
            $table->boolean('is_combo_category')->default(false);
            $table->json('variant_definitions')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['is_active', 'sort_order'], 'idx_active_order');
        });

        // ==================== PRODUCTS ====================
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->nullable()->constrained('categories')->onDelete('set null');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->boolean('has_variants')->default(false);
            $table->decimal('precio_pickup_capital', 8, 2)->nullable();
            $table->decimal('precio_domicilio_capital', 8, 2)->nullable();
            $table->decimal('precio_pickup_interior', 8, 2)->nullable();
            $table->decimal('precio_domicilio_interior', 8, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['category_id']);
            $table->index(['is_active'], 'idx_active');
        });

        // ==================== CATEGORY_PRODUCT (Pivot) ====================
        Schema::create('category_product', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('categories')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['category_id', 'product_id']);
            $table->index(['category_id', 'sort_order']);
        });

        // ==================== PRODUCT_VARIANTS ====================
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->string('sku')->unique();
            $table->string('name');
            $table->string('size')->nullable();
            $table->decimal('precio_pickup_capital', 8, 2)->nullable();
            $table->decimal('precio_domicilio_capital', 8, 2)->nullable();
            $table->decimal('precio_pickup_interior', 8, 2)->nullable();
            $table->decimal('precio_domicilio_interior', 8, 2)->nullable();
            $table->boolean('is_daily_special')->default(false);
            $table->json('daily_special_days')->nullable();
            $table->decimal('daily_special_precio_pickup_capital', 8, 2)->nullable();
            $table->decimal('daily_special_precio_domicilio_capital', 8, 2)->nullable();
            $table->decimal('daily_special_precio_pickup_interior', 8, 2)->nullable();
            $table->decimal('daily_special_precio_domicilio_interior', 8, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['product_id']);
            $table->index(['sku']);
            $table->index(['is_active']);
        });

        // ==================== SECTIONS ====================
        Schema::create('sections', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->boolean('is_required')->default(false);
            $table->boolean('allow_multiple')->default(false);
            $table->unsignedTinyInteger('min_selections')->default(0);
            $table->unsignedTinyInteger('max_selections')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['title'], 'idx_title');
        });

        // ==================== SECTION_OPTIONS ====================
        Schema::create('section_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('section_id')->constrained('sections')->onDelete('cascade');
            $table->string('name');
            $table->boolean('is_extra')->default(false);
            $table->decimal('price_modifier', 8, 2)->default(0);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['section_id'], 'idx_section');
        });

        // ==================== PRODUCT_SECTIONS (Pivot) ====================
        Schema::create('product_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->foreignId('section_id')->constrained('sections')->onDelete('cascade');
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['product_id', 'section_id'], 'unique_product_section');
            $table->index(['product_id'], 'idx_product');
            $table->index(['section_id'], 'idx_section');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_sections');
        Schema::dropIfExists('section_options');
        Schema::dropIfExists('sections');
        Schema::dropIfExists('product_variants');
        Schema::dropIfExists('category_product');
        Schema::dropIfExists('products');
        Schema::dropIfExists('categories');
    }
};
