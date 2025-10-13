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
        if (! Schema::hasTable('combo_items')) {
            Schema::create('combo_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('combo_id')->constrained('combos')->onDelete('cascade')->onUpdate('restrict');
                $table->foreignId('product_id')->constrained('products')->onDelete('restrict')->onUpdate('restrict');
                $table->unsignedInteger('quantity')->default(1);
                $table->string('label', 100);
                $table->integer('sort_order')->default(0);
                $table->timestamps();

                $table->index('combo_id');
                $table->index('product_id');
                $table->index('sort_order');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('combo_items');
    }
};
