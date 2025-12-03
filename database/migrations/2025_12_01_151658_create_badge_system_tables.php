<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Catálogo de tipos de badges
        Schema::create('badge_types', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50);
            $table->string('color', 30);
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['is_active', 'sort_order'], 'idx_active_order');
        });

        // Asignación de badges a productos/combos (polimórfica)
        Schema::create('product_badges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('badge_type_id')->constrained('badge_types')->cascadeOnDelete();
            $table->morphs('badgeable'); // badgeable_type, badgeable_id
            // validity_type: 'permanent' (siempre), 'date_range' (fechas), 'weekdays' (días de la semana)
            $table->string('validity_type', 20)->default('permanent');
            $table->date('valid_from')->nullable();
            $table->date('valid_until')->nullable();
            $table->json('weekdays')->nullable(); // Array de 1-7: 1=Lunes, 7=Domingo
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['badgeable_type', 'badgeable_id', 'is_active'], 'idx_badgeable_active');
            $table->index(['is_active', 'valid_from', 'valid_until'], 'idx_validity');
            $table->unique(['badge_type_id', 'badgeable_type', 'badgeable_id'], 'unique_badge_assignment');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_badges');
        Schema::dropIfExists('badge_types');
    }
};
