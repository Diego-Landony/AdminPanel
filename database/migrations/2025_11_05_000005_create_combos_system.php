<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * MÓDULO: Sistema de Combos
     * - Combos (paquetes de productos a precio especial)
     * - Items de combos (productos incluidos)
     * - Opciones de items (para grupos de elección)
     */
    public function up(): void
    {
        // ==================== COMBOS ====================
        Schema::create('combos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->nullable()->constrained('categories')->onDelete('set null');
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->decimal('precio_pickup_capital', 10, 2);
            $table->decimal('precio_domicilio_capital', 10, 2);
            $table->decimal('precio_pickup_interior', 10, 2);
            $table->decimal('precio_domicilio_interior', 10, 2);
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index('category_id');
            $table->index('is_active');
            $table->index('sort_order');
        });

        // ==================== COMBO_ITEMS ====================
        Schema::create('combo_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('combo_id')->constrained('combos')->onDelete('cascade');
            $table->foreignId('product_id')->nullable()->constrained('products')->onDelete('restrict');
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->onDelete('restrict');
            $table->boolean('is_choice_group')->default(false);
            $table->string('choice_label', 255)->nullable();
            $table->unsignedInteger('quantity')->default(1);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index('combo_id');
            $table->index('product_id');
            $table->index('variant_id');
            $table->index('sort_order');
            $table->index(['combo_id', 'is_choice_group'], 'idx_combo_choice_group');
        });

        // ==================== COMBO_ITEM_OPTIONS ====================
        Schema::create('combo_item_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('combo_item_id')->constrained('combo_items')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products')->onDelete('restrict');
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->onDelete('restrict');
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index('combo_item_id', 'idx_combo_item');
            $table->index('product_id', 'idx_product');
            $table->index('variant_id', 'idx_variant');
            $table->index('sort_order', 'idx_sort_order');

            $table->unique(['combo_item_id', 'product_id', 'variant_id'], 'unique_option');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('combo_item_options');
        Schema::dropIfExists('combo_items');
        Schema::dropIfExists('combos');
    }
};
