<?php

namespace App\Console\Commands;

use App\Models\Permission;
use App\Models\Role;
use App\Services\PermissionService;
use Illuminate\Console\Command;

class SyncPermissionsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'permissions:sync
                            {--remove-obsolete : Elimina permisos que ya no existen en el sistema}
                            {--clear-cache : Limpia el caché de permisos antes de sincronizar}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sincroniza permisos del sistema detectando automáticamente nuevas páginas';

    /**
     * Execute the console command.
     */
    public function handle(PermissionService $service): int
    {
        $this->info('🔍 Sincronizando permisos del sistema...');
        $this->newLine();

        // Sincronizar permisos
        $result = $service->syncPermissions();

        // Mostrar resultados
        $this->info('📊 Resultado de la sincronización:');
        $this->table(
            ['Métrica', 'Valor'],
            [
                ['Páginas configuradas', $result['total_pages']],
                ['Permisos totales', $result['total_permissions']],
                ['Permisos creados', $result['created']],
                ['Permisos actualizados', $result['updated']],
                ['Permisos eliminados', $result['deleted']],
            ]
        );

        $this->newLine();

        // Actualizar rol admin con todos los permisos
        $this->info('🛡️  Actualizando rol de administrador...');
        $adminRole = Role::where('name', 'admin')->first();

        if ($adminRole) {
            $allPermissionIds = Permission::pluck('id');
            $adminRole->permissions()->sync($allPermissionIds);
            $this->info("✅ Rol 'admin' actualizado con {$allPermissionIds->count()} permisos");
        } else {
            $this->warn('⚠️  Rol "admin" no encontrado. Ejecuta el seeder para crearlo.');
        }

        $this->newLine();
        $this->info('✨ Sincronización completada exitosamente');

        return Command::SUCCESS;
    }
}
