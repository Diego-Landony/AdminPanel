<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Role;
use App\Models\Permission;
use App\Services\PermissionDiscoveryService;

class SyncAdminPermissions extends Command
{
    protected $signature = 'permissions:sync-admin';
    protected $description = 'Sincroniza todos los permisos para el rol admin';

    public function handle()
    {
        $this->info('Sincronizando permisos para el rol admin...');

        // Obtener el rol admin
        $adminRole = Role::where('name', 'admin')->first();

        if (!$adminRole) {
            $this->error('El rol admin no existe!');
            return 1;
        }

        // Sincronizar permisos
        $service = new PermissionDiscoveryService();
        $service->syncPermissions();

        // Obtener todos los permisos y asignarlos al rol admin
        $allPermissions = Permission::all();
        $adminRole->permissions()->sync($allPermissions->pluck('id'));

        $this->info('Se han sincronizado ' . $allPermissions->count() . ' permisos para el rol admin.');
        
        return 0;
    }
}
