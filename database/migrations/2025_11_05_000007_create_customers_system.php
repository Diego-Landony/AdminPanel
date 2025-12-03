<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * MÓDULO: Sistema de Clientes
     * - Tipos de clientes (sistema de puntos y beneficios)
     * - Clientes (usuarios de la app móvil)
     * - Direcciones de clientes (múltiples direcciones de entrega)
     * - Dispositivos de clientes (múltiples dispositivos FCM)
     * - NITs de clientes (múltiples NITs para facturación)
     */
    public function up(): void
    {
        // ==================== CUSTOMER_TYPES ====================
        Schema::create('customer_types', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->integer('points_required')->default(0);
            $table->decimal('multiplier', 4, 2)->default(1.00);
            $table->string('color', 20)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // ==================== CUSTOMERS ====================
        Schema::create('customers', function (Blueprint $table) {
            $table->id();

            // Información básica (nombre separado en first/last)
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password')->nullable(); // Nullable para OAuth
            $table->string('google_id')->nullable()->unique();
            $table->string('apple_id')->nullable()->unique();
            $table->text('avatar')->nullable();
            $table->enum('oauth_provider', ['local', 'google', 'apple'])->default('local');
            $table->string('subway_card')->nullable()->unique();
            $table->date('birth_date')->nullable();
            $table->string('gender')->nullable();

            // Tipo de cliente
            $table->foreignId('customer_type_id')->nullable()->constrained('customer_types');

            // Información de contacto
            $table->string('phone')->nullable();

            // Tokens y sesiones
            $table->rememberToken();

            // Actividad y compras
            $table->timestamp('last_login_at')->nullable();
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamp('last_purchase_at')->nullable();

            // Sistema de puntos
            $table->integer('points')->default(0);
            $table->timestamp('points_updated_at')->nullable();

            // Timestamps y soft deletes
            $table->timestamps();
            $table->softDeletes();

            // Índices para optimización
            $table->index(['email']);
            $table->index(['subway_card']);
            $table->index(['google_id']);
            $table->index(['apple_id']);
            $table->index(['customer_type_id']);
            $table->index(['created_at']);
            $table->index(['last_activity_at']);
            $table->index(['last_purchase_at']);
        });

        // ==================== CUSTOMER_ADDRESSES ====================
        Schema::create('customer_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');
            $table->string('label', 100);
            $table->text('address_line');
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->text('delivery_notes')->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->index('customer_id');
            $table->index('is_default');
        });

        // ==================== CUSTOMER_DEVICES ====================
        Schema::create('customer_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');
            $table->foreignId('sanctum_token_id')->nullable()
                ->references('id')->on('personal_access_tokens')
                ->onDelete('set null');
            $table->string('fcm_token')->nullable()->unique();
            $table->string('device_identifier')->nullable()->unique();
            $table->string('device_name')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('login_count')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['customer_id', 'last_used_at']);
            $table->index('sanctum_token_id');
            $table->index('device_identifier');
            $table->index('is_active');
        });

        // ==================== CUSTOMER_NITS ====================
        Schema::create('customer_nits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');
            $table->string('nit', 20);
            $table->enum('nit_type', ['personal', 'company', 'other'])->default('personal');
            $table->string('business_name')->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->index(['customer_id', 'is_default']);
            $table->unique(['customer_id', 'nit']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_nits');
        Schema::dropIfExists('customer_devices');
        Schema::dropIfExists('customer_addresses');
        Schema::dropIfExists('customers');
        Schema::dropIfExists('customer_types');
    }
};
