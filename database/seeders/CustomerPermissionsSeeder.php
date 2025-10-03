<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class CustomerPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear permisos para customers
        $permissions = [
            [
                'name' => 'customers.view',
                'display_name' => 'Ver Clientes',
                'description' => 'Acceso a visualizar Gestión de clientes del sistema',
                'group' => 'customers',
            ],
            [
                'name' => 'customers.create',
                'display_name' => 'Crear Clientes',
                'description' => 'Capacidad de crear nuevos elementos en Gestión de clientes del sistema',
                'group' => 'customers',
            ],
            [
                'name' => 'customers.edit',
                'display_name' => 'Editar Clientes',
                'description' => 'Capacidad de modificar elementos existentes en Gestión de clientes del sistema',
                'group' => 'customers',
            ],
            [
                'name' => 'customers.delete',
                'display_name' => 'Eliminar Clientes',
                'description' => 'Capacidad de eliminar elementos en Gestión de clientes del sistema',
                'group' => 'customers',
            ],
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission['name']],
                $permission
            );
        }

        // Asignar todos los permisos de customers al rol admin
        $adminRole = Role::where('name', 'admin')->first();
        if ($adminRole) {
            $customerPermissions = Permission::where('group', 'customers')->get();
            foreach ($customerPermissions as $permission) {
                if (! $adminRole->permissions()->where('permission_id', $permission->id)->exists()) {
                    $adminRole->permissions()->attach($permission->id);
                }
            }
        }

        $this->command->info('Customer permissions created and assigned to admin role successfully!');
    }
}
