<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\PermissionDiscoveryService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->command->info('🚀 Iniciando configuración del sistema...');

        // 1. Descubrir y crear permisos automáticamente
        $this->command->info('🔍 Descubriendo permisos del sistema...');
        $discoveryService = new PermissionDiscoveryService;
        $permissionsResult = $discoveryService->syncPermissions();

        $this->command->info("   ✅ {$permissionsResult['total_permissions']} permisos sincronizados");
        $this->command->info("   ➕ {$permissionsResult['created']} permisos creados");
        $this->command->info("   ✏️  {$permissionsResult['updated']} permisos actualizados");

        // 2. Crear rol de administrador del sistema
        $this->command->info('🛡️  Creando rol de administrador...');

        $adminRole = Role::firstOrCreate(
            ['name' => 'admin'],
            [
                'description' => 'Acceso completo al sistema con todos los permisos. Este rol tiene control total sobre todas las funcionalidades.',
                'is_system' => true,
            ]
        );

        // 3. Asignar todos los permisos al administrador
        $allPermissionIds = Permission::pluck('id');
        $adminRole->permissions()->sync($allPermissionIds);

        $this->command->info("   ✅ Rol administrador con {$allPermissionIds->count()} permisos");

        // 4. Crear usuario administrador por defecto
        $this->command->info('👤 Verificando usuario administrador por defecto...');

        $adminUser = User::firstOrCreate(
            ['email' => 'admin@admin.com'],
            [
                'name' => 'Administrador',
                'password' => Hash::make('admin'),
                'email_verified_at' => now(),
                'timezone' => 'America/Guatemala',
            ]
        );

        // 5. Asignar rol de administrador al usuario
        if (! $adminUser->hasRole('admin')) {
            $adminUser->roles()->attach($adminRole->id);
            $this->command->info('   ✅ Rol administrador asignado al usuario admin@admin.com');
        } else {
            $this->command->info('   ℹ️  Usuario admin@admin.com ya tiene el rol administrador');
        }

        // 6. El usuario admin@admin.com ahora se crea automáticamente en la migración inicial
        // No es necesario crearlo aquí para evitar duplicados
        $this->command->info('ℹ️  Usuario admin@admin.com se crea automáticamente en la migración inicial');

        // 7. Crear algunos usuarios de prueba (opcional)
        if (app()->environment('local')) {
            $this->command->info('🧪 Creando usuarios de prueba...');

            // Usuario de prueba 1
            User::firstOrCreate(
                ['email' => 'user1@test.com'],
                [
                    'name' => 'Usuario Prueba 1',
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                    'timezone' => 'America/Guatemala',
                ]
            );

            // Usuario de prueba 2
            User::firstOrCreate(
                ['email' => 'user2@test.com'],
                [
                    'name' => 'Usuario Prueba 2',
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                    'timezone' => 'America/Guatemala',
                ]
            );

            $this->command->info('   ✅ 2 usuarios de prueba creados (user1@test.com, user2@test.com)');
        }

        $this->command->line('');
        $this->command->info('🎉 Configuración del sistema completada exitosamente:');
        $this->command->line("   📄 Páginas detectadas: {$permissionsResult['discovered_pages']}");
        $this->command->line("   🔑 Permisos totales: {$permissionsResult['total_permissions']}");
        $this->command->line('   🛡️  Rol: admin (acceso completo)');
        $this->command->line('   👤 Usuario: admin@admin.com (contraseña: admin)');
        $this->command->line('   👤 Usuario: admin@test.com (contraseña: admintest)');

        if (app()->environment('local')) {
            $this->command->line('   🧪 Usuarios de prueba: user1@test.com, user2@test.com (contraseña: password)');
        }

        $this->command->line('');
        $this->command->info('🔐 Credenciales de acceso:');
        $this->command->line('   Email: admin@admin.com');
        $this->command->line('   Contraseña: admin');
        $this->command->line('   Email: admin@test.com');
        $this->command->line('   Contraseña: admintest');
        $this->command->line('');
        $this->command->info('✨ El sistema está listo para usar.');
    }
}
