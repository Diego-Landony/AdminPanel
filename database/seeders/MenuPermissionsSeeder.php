<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class MenuPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $resources = ['categories', 'products', 'sections', 'promotions', 'combos'];
        $permissions = [];

        foreach ($resources as $resource) {
            $permissions = array_merge($permissions, [
                [
                    'name' => "menu.{$resource}.view",
                    'display_name' => 'Ver '.ucfirst($resource),
                    'description' => "Acceso a visualizar {$resource} del menú",
                    'group' => 'menu',
                ],
                [
                    'name' => "menu.{$resource}.create",
                    'display_name' => 'Crear '.ucfirst($resource),
                    'description' => "Capacidad de crear {$resource} del menú",
                    'group' => 'menu',
                ],
                [
                    'name' => "menu.{$resource}.update",
                    'display_name' => 'Actualizar '.ucfirst($resource),
                    'description' => "Capacidad de actualizar {$resource} del menú",
                    'group' => 'menu',
                ],
                [
                    'name' => "menu.{$resource}.delete",
                    'display_name' => 'Eliminar '.ucfirst($resource),
                    'description' => "Capacidad de eliminar {$resource} del menú",
                    'group' => 'menu',
                ],
                [
                    'name' => "menu.{$resource}.restore",
                    'display_name' => 'Restaurar '.ucfirst($resource),
                    'description' => "Capacidad de restaurar {$resource} eliminados del menú",
                    'group' => 'menu',
                ],
                [
                    'name' => "menu.{$resource}.force-delete",
                    'display_name' => 'Eliminar permanentemente '.ucfirst($resource),
                    'description' => "Capacidad de eliminar permanentemente {$resource} del menú",
                    'group' => 'menu',
                ],
            ]);
        }

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission['name']],
                $permission
            );
        }

        // Asignar todos los permisos de menú al rol admin
        $adminRole = Role::where('name', 'admin')->first();
        if ($adminRole) {
            $menuPermissions = Permission::where('group', 'menu')->get();
            foreach ($menuPermissions as $permission) {
                if (! $adminRole->permissions()->where('permission_id', $permission->id)->exists()) {
                    $adminRole->permissions()->attach($permission->id);
                }
            }
        }

        $this->command->info('Menu permissions created and assigned to admin role successfully!');
    }
}
