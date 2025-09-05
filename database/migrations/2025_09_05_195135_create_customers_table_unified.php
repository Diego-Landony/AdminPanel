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
            
            // Tipos de cliente (legacy y nuevo sistema)
            $table->string('client_type')->nullable();
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
            $table->index(['client_type']);
            $table->index(['customer_type_id']);
            $table->index(['created_at']);
            $table->index(['last_activity_at']);
            $table->index(['last_purchase_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};