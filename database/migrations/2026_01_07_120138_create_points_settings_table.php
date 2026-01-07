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
        Schema::create('points_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('quetzales_per_point')->default(10);
            $table->string('expiration_method')->default('total');
            $table->unsignedInteger('expiration_months')->default(6);
            $table->decimal('rounding_threshold', 3, 2)->default(0.70);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('points_settings');
    }
};
