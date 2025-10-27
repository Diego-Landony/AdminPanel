<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Migración consolidada que crea toda la estructura de combos:
     * - Tabla combos (sin slug)
     * - Tabla combo_items (con variant_id, sin label)
     * - Columna is_combo_category en categories
     * - Columna category_id en combos
     */
    public function up(): void
    {
        // Agregar is_combo_category a categories
        if (! Schema::hasColumn('categories', 'is_combo_category')) {
            Schema::table('categories', function (Blueprint $table) {
                $table->boolean('is_combo_category')->default(false)->after('uses_variants');
            });
        }

        // Crear tabla combos con estructura final
        if (! Schema::hasTable('combos')) {
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
        }

        // Crear tabla combo_items con estructura final
        if (! Schema::hasTable('combo_items')) {
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
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('combo_items');
        Schema::dropIfExists('combos');

        Schema::table('categories', function (Blueprint $table) {
            if (Schema::hasColumn('categories', 'is_combo_category')) {
                $table->dropColumn('is_combo_category');
            }
        });
    }
};
