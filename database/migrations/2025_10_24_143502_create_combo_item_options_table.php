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
        Schema::create('combo_item_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('combo_item_id')
                ->constrained('combo_items')
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
    }
};
