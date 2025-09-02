<?php

namespace App\Console\Commands;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

/**
 * Comando para verificar el estado de la tabla activity_logs
 * Ayuda a diagnosticar problemas con el sistema de auditoría
 */
class CheckActivityLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'activity:check 
                          {--fix : Intentar corregir problemas automáticamente}
                          {--details : Mostrar información detallada}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verifica el estado de la tabla activity_logs y diagnostica problemas';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Verificando estado de la tabla activity_logs...');

        // Verificar si la tabla existe
        if (! Schema::hasTable('activity_logs')) {
            $this->error('❌ La tabla activity_logs NO existe');

            if ($this->option('fix')) {
                $this->info('🛠️  Ejecutando migración...');
                $this->call('migrate');
            } else {
                $this->warn('💡 Ejecuta: php artisan migrate para crear la tabla');
            }

            return Command::FAILURE;
        }

        $this->info('✅ La tabla activity_logs existe');

        // Verificar estructura de la tabla
        $this->checkTableStructure();

        // Verificar registros existentes
        $this->checkExistingRecords();

        // Verificar observers
        $this->checkObservers();

        // Verificar permisos de escritura
        $this->checkWritePermissions();

        $this->info('🎉 Verificación completada');

        return Command::SUCCESS;
    }

    /**
     * Verifica la estructura de la tabla
     */
    private function checkTableStructure(): void
    {
        $this->line('');
        $this->info('📋 Verificando estructura de la tabla...');

        $columns = Schema::getColumnListing('activity_logs');
        $requiredColumns = [
            'id', 'user_id', 'event_type', 'target_model', 'target_id',
            'description', 'old_values', 'new_values', 'user_agent',
        ];

        $missingColumns = array_diff($requiredColumns, $columns);
        $extraColumns = array_diff($columns, $requiredColumns);

        if (empty($missingColumns)) {
            $this->info('✅ Todas las columnas requeridas están presentes');
        } else {
            $this->error('❌ Columnas faltantes: '.implode(', ', $missingColumns));
        }

        if (! empty($extraColumns)) {
            $this->warn('⚠️  Columnas extra detectadas: '.implode(', ', $extraColumns));
        }

        if ($this->option('details')) {
            $this->line('📊 Columnas actuales: '.implode(', ', $columns));
        }
    }

    /**
     * Verifica registros existentes
     */
    private function checkExistingRecords(): void
    {
        $this->line('');
        $this->info('📊 Verificando registros existentes...');

        $totalRecords = ActivityLog::count();
        $this->line("   Total de registros: {$totalRecords}");

        if ($totalRecords > 0) {
            $recentRecords = ActivityLog::latest()->take(5)->get();
            $this->line('   Últimos 5 registros:');

            foreach ($recentRecords as $record) {
                $this->line("     - ID: {$record->id}, Tipo: {$record->event_type}, Modelo: {$record->target_model}");
            }

            // Verificar tipos de eventos
            $eventTypes = ActivityLog::select('event_type')
                ->distinct()
                ->pluck('event_type')
                ->toArray();

            $this->line('   Tipos de eventos: '.implode(', ', $eventTypes));
        } else {
            $this->warn('⚠️  No hay registros en la tabla');
        }
    }

    /**
     * Verifica que los observers estén funcionando
     */
    private function checkObservers(): void
    {
        $this->line('');
        $this->info('👀 Verificando observers...');

        // Verificar si hay registros de roles
        $roleRecords = ActivityLog::where('target_model', 'Role')->count();
        $this->line("   Registros de roles: {$roleRecords}");

        // Verificar si hay registros de usuarios
        $userRecords = ActivityLog::where('target_model', 'User')->count();
        $this->line("   Registros de usuarios: {$userRecords}");

        if ($roleRecords === 0) {
            $this->warn('⚠️  No hay registros de roles - El RoleObserver podría no estar funcionando');
        }

        if ($userRecords === 0) {
            $this->warn('⚠️  No hay registros de usuarios - El UserObserver podría no estar funcionando');
        }
    }

    /**
     * Verifica permisos de escritura
     */
    private function checkWritePermissions(): void
    {
        $this->line('');
        $this->info('✍️  Verificando permisos de escritura...');

        try {
            // Intentar crear un registro de prueba
            $testLog = ActivityLog::create([
                'user_id' => User::first()?->id ?? 1,
                'event_type' => 'test_event',
                'target_model' => 'Test',
                'target_id' => 1,
                'description' => 'Registro de prueba para verificar permisos',
                'user_agent' => 'Test Command',
            ]);

            $this->info('✅ Permisos de escritura OK');

            // Eliminar el registro de prueba
            $testLog->delete();
            $this->line('   Registro de prueba eliminado');
        } catch (\Exception $e) {
            $this->error('❌ Error de escritura: '.$e->getMessage());
        }
    }
}
