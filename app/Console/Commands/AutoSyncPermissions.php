<?php

namespace App\Console\Commands;

use App\Services\PermissionDiscoveryService;
use App\Models\Role;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * Comando para sincronizar permisos automáticamente cuando se detectan cambios
 */
class AutoSyncPermissions extends Command
{
    protected $signature = 'permissions:auto-sync 
                          {--watch : Ejecutar en modo observación continua}
                          {--interval=5 : Intervalo en segundos para verificar cambios}';

    protected $description = 'Sincroniza automáticamente los permisos cuando se detectan cambios en las páginas';

    private ?string $lastHash = null;

    public function handle()
    {
        if ($this->option('watch')) {
            $this->info('🔄 Modo observación activado. Presiona Ctrl+C para detener.');
            $this->watchForChanges();
        } else {
            $this->syncIfNeeded();
        }

        return Command::SUCCESS;
    }

    /**
     * Observa cambios de forma continua
     */
    private function watchForChanges(): void
    {
        $interval = (int) $this->option('interval');
        
        while (true) {
            try {
                if ($this->syncIfNeeded()) {
                    $this->info('✅ Permisos sincronizados automáticamente a las ' . now()->format('H:i:s'));
                }
                sleep($interval);
            } catch (\Exception $e) {
                $this->error("Error durante la sincronización: {$e->getMessage()}");
                sleep($interval);
            }
        }
    }

    /**
     * Sincroniza solo si hay cambios
     */
    private function syncIfNeeded(): bool
    {
        $currentHash = $this->getPagesHash();
        
        if ($this->lastHash !== null && $this->lastHash === $currentHash) {
            return false; // No hay cambios
        }

        $this->lastHash = $currentHash;
        
        // Ejecutar sincronización silenciosa
        $discoveryService = new PermissionDiscoveryService;
        $result = $discoveryService->syncPermissions();

        // Actualizar rol admin automáticamente
        $adminRole = Role::where('name', 'admin')->first();
        if ($adminRole) {
            $allPermissionIds = \App\Models\Permission::pluck('id');
            $adminRole->permissions()->sync($allPermissionIds);
        }

        return $result['created'] > 0 || $result['updated'] > 0;
    }

    /**
     * Genera un hash del estado actual de las páginas
     */
    private function getPagesHash(): string
    {
        $pagesPath = resource_path('js/pages');
        
        if (!File::exists($pagesPath)) {
            return '';
        }

        $files = [];
        $this->collectFiles($pagesPath, $files);
        
        // Crear hash basado en la estructura y fechas de modificación
        $hashData = collect($files)->map(function ($file) {
            return $file . ':' . filemtime($file);
        })->sort()->join('|');

        return md5($hashData);
    }

    /**
     * Recolecta recursivamente todos los archivos tsx/jsx
     */
    private function collectFiles(string $directory, array &$files): void
    {
        foreach (File::allFiles($directory) as $file) {
            if (in_array($file->getExtension(), ['tsx', 'jsx'])) {
                $files[] = $file->getPathname();
            }
        }
    }
}