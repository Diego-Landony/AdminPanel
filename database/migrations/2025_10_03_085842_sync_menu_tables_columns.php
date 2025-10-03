<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Esta migración sincroniza las columnas de las tablas de menú existentes
     * sin eliminar datos. Solo agrega columnas faltantes o elimina columnas obsoletas.
     */
    public function up(): void
    {
        // Sincronizar tabla categories
        if (Schema::hasTable('categories')) {
            Schema::table('categories', function (Blueprint $table) {
                if (! Schema::hasColumn('categories', 'uses_variants')) {
                    $table->boolean('uses_variants')->default(false)->after('is_active');
                }
                if (! Schema::hasColumn('categories', 'variant_definitions')) {
                    $table->json('variant_definitions')->nullable()->after('uses_variants');
                }
                if (! Schema::hasColumn('categories', 'sort_order')) {
                    $table->integer('sort_order')->default(0)->after('variant_definitions');
                }
            });
        }

        // Sincronizar tabla products - eliminar is_customizable si existe
        if (Schema::hasTable('products')) {
            Schema::table('products', function (Blueprint $table) {
                if (Schema::hasColumn('products', 'is_customizable')) {
                    $table->dropColumn('is_customizable');
                }

                // Asegurar que category_id existe y es nullable
                if (! Schema::hasColumn('products', 'category_id')) {
                    $table->foreignId('category_id')->nullable()->after('id')->constrained('categories')->onDelete('set null');
                }

                // Asegurar que has_variants existe
                if (! Schema::hasColumn('products', 'has_variants')) {
                    $table->boolean('has_variants')->default(false)->after('image');
                }

                // Asegurar columnas de precios
                if (! Schema::hasColumn('products', 'precio_pickup_capital')) {
                    $table->decimal('precio_pickup_capital', 8, 2)->nullable()->after('has_variants');
                }
                if (! Schema::hasColumn('products', 'precio_domicilio_capital')) {
                    $table->decimal('precio_domicilio_capital', 8, 2)->nullable()->after('precio_pickup_capital');
                }
                if (! Schema::hasColumn('products', 'precio_pickup_interior')) {
                    $table->decimal('precio_pickup_interior', 8, 2)->nullable()->after('precio_domicilio_capital');
                }
                if (! Schema::hasColumn('products', 'precio_domicilio_interior')) {
                    $table->decimal('precio_domicilio_interior', 8, 2)->nullable()->after('precio_pickup_interior');
                }
            });
        }

        // Sincronizar tabla category_product - eliminar columnas de precios si existen
        if (Schema::hasTable('category_product')) {
            Schema::table('category_product', function (Blueprint $table) {
                // Eliminar columnas de precio si existen (ya no se usan)
                $priceColumns = [
                    'precio_pickup_capital',
                    'precio_domicilio_capital',
                    'precio_pickup_interior',
                    'precio_domicilio_interior',
                ];

                foreach ($priceColumns as $column) {
                    if (Schema::hasColumn('category_product', $column)) {
                        $table->dropColumn($column);
                    }
                }

                // Asegurar sort_order existe
                if (! Schema::hasColumn('category_product', 'sort_order')) {
                    $table->integer('sort_order')->default(0)->after('product_id');
                }
            });
        }

        // Sincronizar tabla product_variants
        if (Schema::hasTable('product_variants')) {
            Schema::table('product_variants', function (Blueprint $table) {
                if (! Schema::hasColumn('product_variants', 'is_daily_special')) {
                    $table->boolean('is_daily_special')->default(false)->after('precio_domicilio_interior');
                }
                if (! Schema::hasColumn('product_variants', 'daily_special_days')) {
                    $table->json('daily_special_days')->nullable()->after('is_daily_special');
                }
                if (! Schema::hasColumn('product_variants', 'daily_special_precio_pickup_capital')) {
                    $table->decimal('daily_special_precio_pickup_capital', 8, 2)->nullable()->after('daily_special_days');
                }
                if (! Schema::hasColumn('product_variants', 'daily_special_precio_domicilio_capital')) {
                    $table->decimal('daily_special_precio_domicilio_capital', 8, 2)->nullable()->after('daily_special_precio_pickup_capital');
                }
                if (! Schema::hasColumn('product_variants', 'daily_special_precio_pickup_interior')) {
                    $table->decimal('daily_special_precio_pickup_interior', 8, 2)->nullable()->after('daily_special_precio_domicilio_capital');
                }
                if (! Schema::hasColumn('product_variants', 'daily_special_precio_domicilio_interior')) {
                    $table->decimal('daily_special_precio_domicilio_interior', 8, 2)->nullable()->after('daily_special_precio_pickup_interior');
                }
                if (! Schema::hasColumn('product_variants', 'sort_order')) {
                    $table->integer('sort_order')->default(0)->after('is_active');
                }
            });
        }

        // Sincronizar tabla section_options - unificar a price_modifier
        if (Schema::hasTable('section_options')) {
            Schema::table('section_options', function (Blueprint $table) {
                // Si existen múltiples columnas de precio, eliminarlas
                $oldPriceColumns = [
                    'price_modifier_pickup_capital',
                    'price_modifier_domicilio_capital',
                    'price_modifier_pickup_interior',
                    'price_modifier_domicilio_interior',
                ];

                foreach ($oldPriceColumns as $column) {
                    if (Schema::hasColumn('section_options', $column)) {
                        $table->dropColumn($column);
                    }
                }

                // Asegurar que existe price_modifier único
                if (! Schema::hasColumn('section_options', 'price_modifier')) {
                    $table->decimal('price_modifier', 8, 2)->default(0)->after('is_extra');
                }
            });
        }

        // Sincronizar tabla promotions - agregar columnas de restricciones de tiempo
        if (Schema::hasTable('promotions')) {
            Schema::table('promotions', function (Blueprint $table) {
                if (! Schema::hasColumn('promotions', 'has_time_restriction')) {
                    $table->boolean('has_time_restriction')->default(false)->after('valid_until');
                }
                if (! Schema::hasColumn('promotions', 'time_from')) {
                    $table->time('time_from')->nullable()->after('has_time_restriction');
                }
                if (! Schema::hasColumn('promotions', 'time_until')) {
                    $table->time('time_until')->nullable()->after('time_from');
                }
                if (! Schema::hasColumn('promotions', 'active_days')) {
                    $table->json('active_days')->nullable()->after('time_until');
                }
            });
        }

        // Sincronizar tabla promotion_items - agregar variant_id si no existe
        if (Schema::hasTable('promotion_items')) {
            Schema::table('promotion_items', function (Blueprint $table) {
                if (! Schema::hasColumn('promotion_items', 'variant_id')) {
                    $table->foreignId('variant_id')->nullable()->after('product_id')->constrained('product_variants')->onDelete('cascade');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revertir cambios en categories
        if (Schema::hasTable('categories')) {
            Schema::table('categories', function (Blueprint $table) {
                if (Schema::hasColumn('categories', 'variant_definitions')) {
                    $table->dropColumn('variant_definitions');
                }
                if (Schema::hasColumn('categories', 'uses_variants')) {
                    $table->dropColumn('uses_variants');
                }
                if (Schema::hasColumn('categories', 'sort_order')) {
                    $table->dropColumn('sort_order');
                }
            });
        }

        // Revertir cambios en products
        if (Schema::hasTable('products')) {
            Schema::table('products', function (Blueprint $table) {
                $columns = [
                    'has_variants',
                    'precio_pickup_capital',
                    'precio_domicilio_capital',
                    'precio_pickup_interior',
                    'precio_domicilio_interior',
                ];

                foreach ($columns as $column) {
                    if (Schema::hasColumn('products', $column)) {
                        $table->dropColumn($column);
                    }
                }

                // Re-agregar is_customizable
                if (! Schema::hasColumn('products', 'is_customizable')) {
                    $table->boolean('is_customizable')->default(false)->after('image');
                }
            });
        }

        // Revertir cambios en product_variants
        if (Schema::hasTable('product_variants')) {
            Schema::table('product_variants', function (Blueprint $table) {
                $columns = [
                    'daily_special_precio_domicilio_interior',
                    'daily_special_precio_pickup_interior',
                    'daily_special_precio_domicilio_capital',
                    'daily_special_precio_pickup_capital',
                    'daily_special_days',
                    'is_daily_special',
                    'sort_order',
                ];

                foreach ($columns as $column) {
                    if (Schema::hasColumn('product_variants', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        // Revertir cambios en promotions
        if (Schema::hasTable('promotions')) {
            Schema::table('promotions', function (Blueprint $table) {
                $columns = ['active_days', 'time_until', 'time_from', 'has_time_restriction'];

                foreach ($columns as $column) {
                    if (Schema::hasColumn('promotions', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        // Revertir cambios en promotion_items
        if (Schema::hasTable('promotion_items')) {
            Schema::table('promotion_items', function (Blueprint $table) {
                if (Schema::hasColumn('promotion_items', 'variant_id')) {
                    $table->dropForeign(['variant_id']);
                    $table->dropColumn('variant_id');
                }
            });
        }
    }
};
