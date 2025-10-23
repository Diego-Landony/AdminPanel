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
        Schema::create('customer_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('label', 100); // "Casa", "Trabajo", "Casa de mamá"
            $table->text('address_line'); // Dirección completa en texto
            $table->decimal('latitude', 10, 7); // Coordenada GPS
            $table->decimal('longitude', 10, 7); // Coordenada GPS
            $table->text('delivery_notes')->nullable(); // "Portón azul", "Código: 1234"
            $table->boolean('is_default')->default(false); // Solo una por cliente
            $table->timestamps();

            // Índices para mejorar performance
            $table->index('customer_id');
            $table->index('is_default');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_addresses');
    }
};
