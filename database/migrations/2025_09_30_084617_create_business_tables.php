<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Esta migración unificada crea todas las tablas del negocio:
     * - customer_types: Tipos de clientes con sistema de puntos
     * - customers: Clientes de Subway con autenticación
     * - restaurants: Restaurantes con geofence y delivery
     */
    public function up(): void
    {
        // Tabla de tipos de cliente
        Schema::create('customer_types', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->integer('points_required')->default(0);
            $table->decimal('multiplier', 4, 2)->default(1.00);
            $table->string('color', 20)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Tabla de clientes
        Schema::create('customers', function (Blueprint $table) {
            $table->id();

            // Información básica
            $table->string('full_name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('subway_card')->unique();
            $table->date('birth_date');
            $table->string('gender')->nullable();

            // Tipo de cliente
            $table->foreignId('customer_type_id')->nullable()->constrained('customer_types');

            // Información de contacto
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->string('location')->nullable();
            $table->string('nit')->nullable();

            // Tokens y sesiones
            $table->string('fcm_token')->nullable();
            $table->rememberToken();

            // Actividad y compras
            $table->timestamp('last_login_at')->nullable();
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamp('last_purchase_at')->nullable();

            // Sistema de puntos
            $table->integer('puntos')->default(0);
            $table->timestamp('puntos_updated_at')->nullable();

            // Configuración
            $table->string('timezone')->default('America/Guatemala');

            // Timestamps y soft deletes
            $table->timestamps();
            $table->softDeletes();

            // Índices para optimización
            $table->index(['email']);
            $table->index(['subway_card']);
            $table->index(['customer_type_id']);
            $table->index(['created_at']);
            $table->index(['last_activity_at']);
            $table->index(['last_purchase_at']);
        });

        // Tabla de restaurantes
        Schema::create('restaurants', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->longText('geofence_kml')->nullable();
            $table->string('address');
            $table->boolean('is_active')->default(true);
            $table->boolean('delivery_active')->default(true);
            $table->boolean('pickup_active')->default(true);
            $table->string('phone')->nullable();
            $table->json('schedule')->nullable();
            $table->decimal('minimum_order_amount', 8, 2)->default(0);
            $table->string('email')->nullable();
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
        Schema::dropIfExists('customers');
        Schema::dropIfExists('customer_types');
    }
};
