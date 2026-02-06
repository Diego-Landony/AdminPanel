<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    /**
     * Tabla para almacenar registros de dispositivos Apple Wallet.
     * Cuando un usuario agrega un pase a su Wallet, Apple envía una
     * solicitud de registro con el device token para push notifications.
     */
    public function up(): void
    {
        Schema::create('apple_wallet_registrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')
                ->constrained('customers')
                ->onDelete('cascade');
            $table->string('device_library_identifier');
            $table->string('push_token');
            $table->string('pass_type_identifier');
            $table->string('serial_number');
            $table->timestamps();

            // Índices para búsquedas eficientes
            $table->index('device_library_identifier', 'awr_device_idx');
            $table->index('serial_number', 'awr_serial_idx');
            $table->index(['pass_type_identifier', 'serial_number'], 'awr_pass_serial_idx');

            // Un dispositivo solo puede registrar un pase una vez
            $table->unique(['device_library_identifier', 'pass_type_identifier', 'serial_number'], 'awr_unique_device_pass');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('apple_wallet_registrations');
    }
};
