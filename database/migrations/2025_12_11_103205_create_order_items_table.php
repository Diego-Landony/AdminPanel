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
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
            $table->foreignId('product_id')->nullable()->constrained('products');
            $table->foreignId('variant_id')->nullable()->constrained('product_variants');
            $table->foreignId('combo_id')->nullable()->constrained('combos');
            $table->json('product_snapshot');
            $table->integer('quantity');
            $table->decimal('unit_price', 10, 2);
            $table->decimal('options_price', 10, 2)->default(0);
            $table->decimal('subtotal', 10, 2);
            $table->json('selected_options')->nullable();
            $table->json('combo_selections')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('order_id');
            $table->index('product_id');
            $table->index('combo_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
