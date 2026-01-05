<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class MarketingPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            [
                'name' => 'marketing.banners.view',
                'display_name' => 'Ver Banners',
                'description' => 'Acceso a visualizar banners promocionales',
                'group' => 'marketing',
            ],
            [
                'name' => 'marketing.banners.create',
                'display_name' => 'Crear Banners',
                'description' => 'Capacidad de crear banners promocionales',
                'group' => 'marketing',
            ],
            [
                'name' => 'marketing.banners.edit',
                'display_name' => 'Editar Banners',
                'description' => 'Capacidad de editar banners promocionales',
                'group' => 'marketing',
            ],
            [
                'name' => 'marketing.banners.delete',
                'display_name' => 'Eliminar Banners',
                'description' => 'Capacidad de eliminar banners promocionales',
                'group' => 'marketing',
            ],
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission['name']],
                $permission
            );
        }

        // Asignar todos los permisos de marketing al rol admin
        $adminRole = Role::where('name', 'admin')->first();
        if ($adminRole) {
            $marketingPermissions = Permission::where('group', 'marketing')->get();
            foreach ($marketingPermissions as $permission) {
                if (! $adminRole->permissions()->where('permission_id', $permission->id)->exists()) {
                    $adminRole->permissions()->attach($permission->id);
                }
            }
        }

        $this->command->info('Marketing permissions created and assigned to admin role successfully!');
    }
}
