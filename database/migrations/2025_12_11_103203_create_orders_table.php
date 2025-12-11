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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number', 20)->unique();
            $table->foreignId('customer_id')->constrained('customers');
            $table->foreignId('restaurant_id')->constrained('restaurants');
            $table->enum('service_type', ['pickup', 'delivery']);
            $table->enum('zone', ['capital', 'interior']);
            $table->foreignId('delivery_address_id')->nullable()->constrained('customer_addresses');
            $table->json('delivery_address_snapshot')->nullable();
            $table->decimal('subtotal', 10, 2);
            $table->decimal('discount_total', 10, 2)->default(0);
            $table->decimal('delivery_fee', 10, 2)->default(0);
            $table->decimal('tax', 10, 2)->default(0);
            $table->decimal('total', 10, 2);
            $table->enum('status', ['pending', 'confirmed', 'preparing', 'ready', 'out_for_delivery', 'delivered', 'completed', 'cancelled', 'refunded'])->default('pending');
            $table->enum('payment_method', ['cash', 'card', 'online'])->default('cash');
            $table->enum('payment_status', ['pending', 'paid', 'failed', 'refunded'])->default('pending');
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('estimated_ready_at')->nullable();
            $table->timestamp('ready_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->integer('points_earned')->default(0);
            $table->integer('points_redeemed')->default(0);
            $table->foreignId('nit_id')->nullable()->constrained('customer_nits');
            $table->json('nit_snapshot')->nullable();
            $table->text('notes')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('customer_id');
            $table->index('restaurant_id');
            $table->index('status');
            $table->index('created_at');
            $table->index('order_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
