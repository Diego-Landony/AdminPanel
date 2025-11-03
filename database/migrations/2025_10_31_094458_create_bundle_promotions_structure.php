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
     * Migración para estructura de Combinados (bundle_special):
     * - Actualiza tipo ENUM en promotions
     * - Agrega campos de temporalidad a promotions
     * - Agrega campos de precio especial a promotions
     * - Crea tabla bundle_promotion_items
     * - Crea tabla bundle_promotion_item_options
     */
    public function up(): void
    {
        // ================== TABLA PROMOTIONS ==================

        // Actualizar ENUM de type para incluir 'bundle_special'
        DB::statement("
            ALTER TABLE promotions
            MODIFY COLUMN type
            ENUM('two_for_one', 'percentage_discount', 'daily_special', 'bundle_special')
            NOT NULL
        ");

        // Agregar campos específicos para bundle_special
        Schema::table('promotions', function (Blueprint $table) {
            // Precios especiales del combinado (solo para bundle_special)
            if (! Schema::hasColumn('promotions', 'special_bundle_price_capital')) {
                $table->decimal('special_bundle_price_capital', 10, 2)
                    ->nullable()
                    ->after('type')
                    ->comment('Precio para zona capital (bundle_special)');
            }

            if (! Schema::hasColumn('promotions', 'special_bundle_price_interior')) {
                $table->decimal('special_bundle_price_interior', 10, 2)
                    ->nullable()
                    ->after('special_bundle_price_capital')
                    ->comment('Precio para zona interior (bundle_special)');
            }

            // Vigencia temporal (solo para bundle_special)
            if (! Schema::hasColumn('promotions', 'valid_from')) {
                $table->date('valid_from')
                    ->nullable()
                    ->after('special_bundle_price_interior')
                    ->comment('Fecha de inicio de vigencia (bundle_special)');
            }

            if (! Schema::hasColumn('promotions', 'valid_until')) {
                $table->date('valid_until')
                    ->nullable()
                    ->after('valid_from')
                    ->comment('Fecha de fin de vigencia (bundle_special)');
            }

            if (! Schema::hasColumn('promotions', 'time_from')) {
                $table->time('time_from')
                    ->nullable()
                    ->after('valid_until')
                    ->comment('Hora de inicio de vigencia (bundle_special)');
            }

            if (! Schema::hasColumn('promotions', 'time_until')) {
                $table->time('time_until')
                    ->nullable()
                    ->after('time_from')
                    ->comment('Hora de fin de vigencia (bundle_special)');
            }

            if (! Schema::hasColumn('promotions', 'weekdays')) {
                $table->json('weekdays')
                    ->nullable()
                    ->after('time_until')
                    ->comment('Días de la semana aplicables [1-7] (bundle_special)');
            }
        });

        // ================== TABLA BUNDLE_PROMOTION_ITEMS ==================

        if (! Schema::hasTable('bundle_promotion_items')) {
            Schema::create('bundle_promotion_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('promotion_id')
                    ->constrained('promotions')
                    ->onDelete('cascade');
                $table->foreignId('product_id')
                    ->nullable()
                    ->constrained('products')
                    ->onDelete('restrict');
                $table->foreignId('variant_id')
                    ->nullable()
                    ->constrained('product_variants')
                    ->onDelete('restrict');
                $table->boolean('is_choice_group')->default(false);
                $table->string('choice_label', 255)->nullable();
                $table->unsignedInteger('quantity')->default(1);
                $table->integer('sort_order')->default(0);
                $table->timestamps();

                // Índices para performance
                $table->index('promotion_id');
                $table->index('product_id');
                $table->index('variant_id');
                $table->index('sort_order');
                $table->index(['promotion_id', 'is_choice_group'], 'idx_bundle_promo_choice_group');
            });
        }

        // ================== TABLA BUNDLE_PROMOTION_ITEM_OPTIONS ==================

        if (! Schema::hasTable('bundle_promotion_item_options')) {
            Schema::create('bundle_promotion_item_options', function (Blueprint $table) {
                $table->id();
                $table->foreignId('bundle_item_id')
                    ->constrained('bundle_promotion_items')
                    ->onDelete('cascade');
                $table->foreignId('product_id')
                    ->constrained('products')
                    ->onDelete('restrict');
                $table->foreignId('variant_id')
                    ->nullable()
                    ->constrained('product_variants')
                    ->onDelete('restrict');
                $table->integer('sort_order')->default(0);
                $table->timestamps();

                // Índices para performance
                $table->index('bundle_item_id', 'idx_bundle_item');
                $table->index('product_id', 'idx_product');
                $table->index('variant_id', 'idx_variant');
                $table->index('sort_order', 'idx_sort_order');

                // Constraint de unicidad: no permite duplicados (mismo producto+variante en mismo grupo)
                $table->unique(['bundle_item_id', 'product_id', 'variant_id'], 'unique_bundle_option');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Eliminar tablas en orden inverso (options primero por FK)
        Schema::dropIfExists('bundle_promotion_item_options');
        Schema::dropIfExists('bundle_promotion_items');

        // Eliminar columnas de promotions
        Schema::table('promotions', function (Blueprint $table) {
            $columnsToRemove = [
                'special_bundle_price_capital',
                'special_bundle_price_interior',
                'valid_from',
                'valid_until',
                'time_from',
                'time_until',
                'weekdays',
            ];

            foreach ($columnsToRemove as $column) {
                if (Schema::hasColumn('promotions', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        // Revertir ENUM de type (quitar bundle_special)
        DB::statement("
            ALTER TABLE promotions
            MODIFY COLUMN type
            ENUM('two_for_one', 'percentage_discount', 'daily_special')
            NOT NULL
        ");
    }
};
