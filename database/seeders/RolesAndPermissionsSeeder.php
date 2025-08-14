<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\Permission;
use App\Models\User;
use App\Services\PermissionDiscoveryService;

/**
 * Seeder dinámico para roles y permisos
 * 
 * Utiliza el servicio de descubrimiento para generar automáticamente
 * todos los permisos basado en las páginas del sistema
 */
class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🔍 Descubriendo permisos automáticamente...');
        
        // Usar el servicio de descubrimiento para generar permisos
        $discoveryService = new PermissionDiscoveryService();
        $result = $discoveryService->syncPermissions();
        
        $this->command->info("   ✅ {$result['total_permissions']} permisos sincronizados");
        $this->command->info("   ➕ {$result['created']} permisos creados");
        $this->command->info("   ✏️  {$result['updated']} permisos actualizados");

        // Crear rol de administrador del sistema
        $this->command->info('🛡️  Creando rol de administrador...');
        
        $adminRole = Role::firstOrCreate(
            ['name' => 'Administrador'],
            [
                'description' => 'Acceso completo al sistema con todos los permisos. Este rol tiene control total sobre todas las funcionalidades.',
                'is_system' => true,
            ]
        );

        // Asignar todos los permisos al administrador
        $allPermissionIds = Permission::pluck('id');
        $adminRole->permissions()->sync($allPermissionIds);
        
        $this->command->info("   ✅ Rol administrador con {$allPermissionIds->count()} permisos");

        // Crear usuario administrador por defecto
        $this->command->info('👤 Creando usuario administrador por defecto...');
        
        $adminUser = User::firstOrCreate(
            ['email' => 'admin@admin.com'],
            [
                'name' => 'Administrador',
                'password' => bcrypt('admin'),
                'email_verified_at' => now(),
                'timezone' => 'America/Guatemala',
            ]
        );

        // Asignar rol de administrador al usuario
        if (!$adminUser->hasRole('Administrador')) {
            $adminUser->roles()->attach($adminRole->id);
            $this->command->info('   ✅ Rol administrador asignado al usuario admin@admin.com');
        } else {
            $this->command->info('   ℹ️  Usuario admin@admin.com ya tiene el rol administrador');
        }

        $this->command->line('');
        $this->command->info('🎉 Configuración de roles y permisos completada:');
        $this->command->line("   📄 Páginas detectadas: {$result['discovered_pages']}");
        $this->command->line("   🔑 Permisos totales: {$result['total_permissions']}");
        $this->command->line('   🛡️  Rol: Administrador (acceso completo)');
        $this->command->line('   👤 Usuario: admin@admin.com (contraseña: admin)');
    }
}
