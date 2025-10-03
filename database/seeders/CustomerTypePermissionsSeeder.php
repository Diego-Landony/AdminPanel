<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class CustomerTypePermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear permisos para tipos de cliente
        $permissions = [
            [
                'name' => 'customer-types.view',
                'display_name' => 'Ver Tipos de Cliente',
                'description' => 'Acceso a visualizar gestión de tipos de cliente del sistema',
                'group' => 'customer-types',
            ],
            [
                'name' => 'customer-types.create',
                'display_name' => 'Crear Tipos de Cliente',
                'description' => 'Capacidad de crear nuevos tipos de cliente en el sistema',
                'group' => 'customer-types',
            ],
            [
                'name' => 'customer-types.edit',
                'display_name' => 'Editar Tipos de Cliente',
                'description' => 'Capacidad de modificar tipos de cliente existentes en el sistema',
                'group' => 'customer-types',
            ],
            [
                'name' => 'customer-types.delete',
                'display_name' => 'Eliminar Tipos de Cliente',
                'description' => 'Capacidad de eliminar tipos de cliente en el sistema',
                'group' => 'customer-types',
            ],
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission['name']],
                $permission
            );
        }

        // Asignar todos los permisos de customer-types al rol admin
        $adminRole = Role::where('name', 'admin')->first();
        if ($adminRole) {
            $customerTypePermissions = Permission::where('group', 'customer-types')->get();
            foreach ($customerTypePermissions as $permission) {
                if (! $adminRole->permissions()->where('permission_id', $permission->id)->exists()) {
                    $adminRole->permissions()->attach($permission->id);
                }
            }
        }

        $this->command->info('Customer type permissions created and assigned to admin role successfully!');
    }
}
