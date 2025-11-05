<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * MÓDULO: Sistema de Actividad y Logs
     * - Actividades de usuarios
     * - Logs de auditoría del sistema
     * - Índices optimizados para queries de performance
     */
    public function up(): void
    {
        // ==================== USER_ACTIVITIES ====================
        Schema::create('user_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('activity_type');
            $table->string('description');
            $table->string('user_agent')->nullable();
            $table->string('url')->nullable();
            $table->string('method')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            // Índices optimizados
            $table->index(['user_id', 'created_at']);
            $table->index(['activity_type']);
            $table->index(['activity_type', 'created_at'], 'user_activities_type_created_index');
            $table->index(['user_id', 'activity_type', 'created_at'], 'user_activities_user_type_created_index');
        });

        // ==================== ACTIVITY_LOGS ====================
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('event_type', 100);
            $table->string('target_model', 255)->nullable();
            $table->unsignedBigInteger('target_id')->nullable();
            $table->text('description');
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            // Índices optimizados
            $table->index(['user_id', 'created_at']);
            $table->index(['event_type', 'created_at']);
            $table->index(['target_model', 'target_id']);
            $table->index(['created_at']);
            $table->index(['user_id', 'event_type', 'created_at'], 'activity_logs_user_event_created_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('user_activities');
    }
};
