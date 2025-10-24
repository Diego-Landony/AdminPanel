<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->decimal('precio_pickup_capital', 8, 2)->nullable()->change();
            $table->decimal('precio_domicilio_capital', 8, 2)->nullable()->change();
            $table->decimal('precio_pickup_interior', 8, 2)->nullable()->change();
            $table->decimal('precio_domicilio_interior', 8, 2)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->decimal('precio_pickup_capital', 8, 2)->nullable(false)->change();
            $table->decimal('precio_domicilio_capital', 8, 2)->nullable(false)->change();
            $table->decimal('precio_pickup_interior', 8, 2)->nullable(false)->change();
            $table->decimal('precio_domicilio_interior', 8, 2)->nullable(false)->change();
        });
    }
};
