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
        Schema::create('restaurants', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Nombre del restaurante
            $table->text('description')->nullable(); // Descripción del restaurante
            $table->decimal('latitude', 10, 7)->nullable(); // Latitud
            $table->decimal('longitude', 10, 7)->nullable(); // Longitud
            $table->string('address'); // Dirección del restaurante
            $table->boolean('is_active')->default(true); // Está activo
            $table->boolean('delivery_active')->default(true); // Está domicilio activo
            $table->boolean('pickup_active')->default(true); // Pickup activo
            $table->string('phone')->nullable(); // Teléfono restaurante
            $table->json('schedule')->nullable(); // Horario (JSON con días y horas)
            $table->decimal('minimum_order_amount', 8, 2)->default(0); // Monto de compra mínimo
            $table->json('delivery_area')->nullable(); // JSON de zona de delivery
            $table->string('image')->nullable(); // Imagen del restaurante
            $table->string('email')->nullable(); // Email de contacto
            $table->string('manager_name')->nullable(); // Nombre del encargado
            $table->decimal('delivery_fee', 8, 2)->default(0); // Tarifa de domicilio
            $table->integer('estimated_delivery_time')->nullable(); // Tiempo estimado de entrega (minutos)
            $table->decimal('rating', 3, 2)->default(0); // Rating promedio
            $table->integer('total_reviews')->default(0); // Total de reseñas
            $table->integer('sort_order')->default(0); // Orden de visualización
            $table->timestamps();
            $table->softDeletes(); // Para borrado lógico
            
            // Índices para búsquedas y performance
            $table->index(['is_active', 'delivery_active']);
            $table->index(['pickup_active', 'is_active']);
            $table->index('sort_order');
            $table->index(['latitude', 'longitude']);
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