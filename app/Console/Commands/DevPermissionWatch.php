<?php

namespace App\Console\Commands;

use App\Services\PermissionDiscoveryService;
use App\Models\Role;
use Illuminate\Console\Command;
use Symfony\Component\Console\Output\ConsoleOutput;

/**
 * Comando de desarrollo para sincronizar permisos automáticamente
 * Ideal para uso durante el desarrollo
 */
class DevPermissionWatch extends Command
{
    protected $signature = 'dev:permissions 
                          {--once : Ejecutar una vez sin observación continua}
                          {--force : Forzar sincronización sin preguntar}';

    protected $description = '🔧 [DEV] Sincroniza permisos automáticamente durante el desarrollo';

    public function handle()
    {
        $this->info('🔧 Herramienta de desarrollo - Sincronización automática de permisos');
        $this->newLine();

        if ($this->option('once')) {
            return $this->syncOnce();
        }

        $this->info('💡 Esta herramienta sincronizará automáticamente los permisos cuando agregues nuevas páginas.');
        $this->info('   Úsala durante el desarrollo para no tener que ejecutar comandos manualmente.');
        $this->newLine();

        if (!$this->option('force') && !$this->confirm('¿Continuar con la sincronización automática?', true)) {
            return Command::CANCELLED;
        }

        return $this->runSync();
    }

    /**
     * Ejecutar sincronización una sola vez
     */
    private function syncOnce(): int
    {
        $this->info('🔄 Ejecutando sincronización única...');
        
        $result = $this->performSync();
        
        if ($result['hasChanges']) {
            $this->info('✅ Sincronización completada con éxito');
            $this->displayResults($result);
        } else {
            $this->comment('ℹ️  No hay cambios que sincronizar');
        }

        return Command::SUCCESS;
    }

    /**
     * Ejecutar sincronización completa
     */
    private function runSync(): int
    {
        $result = $this->performSync();
        
        if ($result['hasChanges']) {
            $this->info('🎉 ¡Sincronización inicial completada!');
            $this->displayResults($result);
        } else {
            $this->comment('✅ Los permisos están actualizados');
        }

        $this->newLine();
        $this->info('💡 Consejos de uso:');
        $this->line('   • Para nuevas páginas, usa: php artisan dev:permissions --once');
        $this->line('   • Para desarrollo automático: php artisan dev:permissions');
        $this->line('   • Para producción: php artisan permissions:sync');

        return Command::SUCCESS;
    }

    /**
     * Realiza la sincronización de permisos
     */
    private function performSync(): array
    {
        $discoveryService = new PermissionDiscoveryService;
        
        // Obtener estado antes de sincronizar
        $permissionsBefore = \App\Models\Permission::count();
        
        // Ejecutar sincronización
        $syncResult = $discoveryService->syncPermissions(false);
        
        // Actualizar rol admin automáticamente
        $adminRole = Role::where('name', 'admin')->first();
        $adminUpdated = false;
        
        if ($adminRole) {
            $currentAdminPermissions = $adminRole->permissions()->count();
            $allPermissionIds = \App\Models\Permission::pluck('id');
            $adminRole->permissions()->sync($allPermissionIds);
            $adminUpdated = $currentAdminPermissions !== $allPermissionIds->count();
        }

        return [
            'hasChanges' => $syncResult['created'] > 0 || $syncResult['updated'] > 0,
            'syncResult' => $syncResult,
            'adminUpdated' => $adminUpdated,
            'totalPermissionsAfter' => \App\Models\Permission::count(),
        ];
    }

    /**
     * Muestra los resultados de la sincronización
     */
    private function displayResults(array $result): void
    {
        $this->newLine();
        $this->info('📊 Resultados:');
        
        $syncResult = $result['syncResult'];
        $this->line("   📄 Páginas descubiertas: {$syncResult['discovered_pages']}");
        $this->line("   🔑 Permisos totales: {$syncResult['total_permissions']}");
        
        if ($syncResult['created'] > 0) {
            $this->line("   ➕ Permisos creados: {$syncResult['created']}");
        }
        
        if ($syncResult['updated'] > 0) {
            $this->line("   ✏️  Permisos actualizados: {$syncResult['updated']}");
        }

        if ($result['adminUpdated']) {
            $this->line('   🛡️  Rol Admin actualizado automáticamente');
        }

        $this->line("   📈 Total de permisos en sistema: {$result['totalPermissionsAfter']}");
    }
}