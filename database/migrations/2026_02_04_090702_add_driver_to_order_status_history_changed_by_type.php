<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE order_status_history MODIFY COLUMN changed_by_type ENUM('system', 'customer', 'admin', 'restaurant', 'driver') DEFAULT 'system'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE order_status_history MODIFY COLUMN changed_by_type ENUM('system', 'customer', 'admin', 'restaurant') DEFAULT 'system'");
    }
};
