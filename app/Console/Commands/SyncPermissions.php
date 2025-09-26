<?php

namespace App\Console\Commands;

use App\Models\Role;
use App\Services\PermissionDiscoveryService;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Comando para sincronizar permisos automáticamente
 *
 * Este comando descubre páginas del sistema y sincroniza los permisos
 * correspondientes en la base de datos de forma automática.
 */
class SyncPermissions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'permissions:sync 
                          {--force : Forzar la sincronización sin confirmación}
                          {--show-only : Solo mostrar qué permisos se crearían sin ejecutar}
                          {--clean : Eliminar permisos obsoletos de páginas que ya no existen}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sincroniza automáticamente los permisos del sistema basado en las páginas existentes';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Descubriendo páginas del sistema...');

        $discoveryService = new PermissionDiscoveryService;

        // Obtener configuración de páginas
        $pagesConfig = $discoveryService->getPagesConfiguration();
        $generatedPermissions = $discoveryService->generatePermissions();

        // Mostrar páginas descubiertas
        $this->line('');
        $this->info('📄 Páginas descubiertas:');
        $this->table(
            ['Página', 'Nombre', 'Acciones', 'Permisos'],
            collect($pagesConfig)->map(function ($config, $key) {
                return [
                    $key,
                    $config['display_name'],
                    implode(', ', $config['actions']),
                    count($config['permissions']),
                ];
            })->toArray()
        );

        // Mostrar permisos que se van a crear/actualizar
        $this->line('');
        $this->info('🔑 Permisos que se sincronizarán:');
        $this->table(
            ['Permiso', 'Nombre', 'Grupo', 'Descripción'],
            collect($generatedPermissions)->map(function ($permission) {
                return [
                    $permission['name'],
                    $permission['display_name'],
                    $permission['group'],
                    Str::limit($permission['description'], 50),
                ];
            })->toArray()
        );

        // Si es solo mostrar, terminar aquí
        if ($this->option('show-only')) {
            $this->line('');
            $this->comment('👆 Estos permisos se crearían/actualizarían. Use el comando sin --show-only para ejecutar.');

            return Command::SUCCESS;
        }

        // Confirmar ejecución
        if (! $this->option('force')) {
            if (! $this->confirm('¿Proceder con la sincronización de permisos?', true)) {
                $this->comment('Operación cancelada.');

                return Command::FAILURE;
            }
        }

        // Ejecutar sincronización
        $this->line('');
        $this->info('⚡ Sincronizando permisos...');

        $cleanObsolete = $this->option('clean');
        if ($cleanObsolete) {
            $this->warn('🧹 Modo limpieza activado - Se eliminarán permisos obsoletos');
        }

        $result = $discoveryService->syncPermissions($cleanObsolete);

        // Mostrar resultados
        $this->line('');
        $this->info('✅ Sincronización completada:');
        $this->line("   📄 Páginas descubiertas: {$result['discovered_pages']}");
        $this->line("   🔑 Permisos totales: {$result['total_permissions']}");
        $this->line("   ➕ Permisos creados: {$result['created']}");
        $this->line("   ✏️  Permisos actualizados: {$result['updated']}");

        if ($cleanObsolete && $result['deleted'] > 0) {
            $this->line("   🗑️  Permisos obsoletos eliminados: {$result['deleted']}");
        }

        // Actualizar rol de administrador con todos los permisos
        $this->info('🛡️  Actualizando rol de administrador...');
        $adminRole = Role::where('name', 'admin')->first();

        if ($adminRole) {
            $allPermissionIds = \App\Models\Permission::pluck('id');
            $adminRole->permissions()->sync($allPermissionIds);
            $this->line("   ✅ Rol Administrador actualizado con {$allPermissionIds->count()} permisos");
        } else {
            $this->warn('   ⚠️  No se encontró el rol Administrador');
        }

        $this->line('');
        $this->info('🎉 ¡Sincronización completada exitosamente!');

        return Command::SUCCESS;
    }
}
