<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * MÓDULO: Sistema de Restaurantes
     * - Restaurantes (sucursales Subway)
     * - Configuración de delivery y pickup
     * - Geofencing (áreas de cobertura)
     * - Horarios de operación
     */
    public function up(): void
    {
        // ==================== RESTAURANTS ====================
        Schema::create('restaurants', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->longText('geofence_kml')->nullable();
            $table->string('address');
            $table->enum('price_location', ['capital', 'interior'])->default('capital');
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('delivery_active')->default(true);
            $table->boolean('pickup_active')->default(true);
            $table->string('phone')->nullable();
            $table->json('schedule')->nullable();
            $table->decimal('minimum_order_amount', 8, 2)->default(0);
            $table->string('email')->nullable();
            $table->string('ip')->nullable();
            $table->string('franchise_number')->nullable();
            $table->integer('estimated_delivery_time')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Índices para búsquedas y performance
            $table->index(['latitude', 'longitude']);
            $table->index(['is_active']);
            $table->index(['delivery_active', 'is_active']);
            $table->index(['pickup_active', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('restaurants');
    }
};
