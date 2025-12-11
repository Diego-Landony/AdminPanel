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
        Schema::create('order_promotions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
            $table->foreignId('promotion_id')->nullable()->constrained('promotions');
            $table->string('promotion_type', 50);
            $table->string('promotion_name');
            $table->decimal('discount_amount', 10, 2);
            $table->text('description')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index('order_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_promotions');
    }
};
