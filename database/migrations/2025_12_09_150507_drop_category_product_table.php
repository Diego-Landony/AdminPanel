<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Elimina la tabla category_product que nunca fue utilizada.
     * El sistema usa relación N:1 (products.category_id) en lugar de N:N.
     */
    public function up(): void
    {
        Schema::dropIfExists('category_product');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('category_product', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('categories')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['category_id', 'product_id']);
            $table->index(['category_id', 'sort_order']);
        });
    }
};
