<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Remove 'online' from payment_method enum since there's no payment gateway implemented.
     */
    public function up(): void
    {
        // First, update any existing orders with 'online' to 'cash' (safety measure)
        DB::table('orders')
            ->where('payment_method', 'online')
            ->update(['payment_method' => 'cash']);

        // Alter the enum to only allow 'cash' and 'card'
        DB::statement("ALTER TABLE orders MODIFY COLUMN payment_method ENUM('cash', 'card') NOT NULL DEFAULT 'cash'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Re-add 'online' option
        DB::statement("ALTER TABLE orders MODIFY COLUMN payment_method ENUM('cash', 'card', 'online') NOT NULL DEFAULT 'cash'");
    }
};
