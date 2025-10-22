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
        // Agregar índices optimizados para user_activities
        Schema::table('user_activities', function (Blueprint $table) {
            // Índice para consultas con activity_type y created_at
            $table->index(['activity_type', 'created_at'], 'user_activities_type_created_index');

            // Índice para consultas con user_id, activity_type y created_at
            $table->index(['user_id', 'activity_type', 'created_at'], 'user_activities_user_type_created_index');
        });

        // Agregar índices optimizados para activity_logs
        Schema::table('activity_logs', function (Blueprint $table) {
            // Índice para consultas con user_id, event_type y created_at
            $table->index(['user_id', 'event_type', 'created_at'], 'activity_logs_user_event_created_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_activities', function (Blueprint $table) {
            $table->dropIndex('user_activities_type_created_index');
            $table->dropIndex('user_activities_user_type_created_index');
        });

        Schema::table('activity_logs', function (Blueprint $table) {
            $table->dropIndex('activity_logs_user_event_created_index');
        });
    }
};
