<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class SupportPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            // Legal documents permissions
            [
                'name' => 'support.legal.view',
                'display_name' => 'Ver Documentos Legales',
                'description' => 'Acceso a visualizar términos y condiciones y política de privacidad',
                'group' => 'support',
            ],
            [
                'name' => 'support.legal.edit',
                'display_name' => 'Editar Documentos Legales',
                'description' => 'Capacidad de crear y editar términos y condiciones y política de privacidad',
                'group' => 'support',
            ],
            // Support tickets permissions
            [
                'name' => 'support.tickets.view',
                'display_name' => 'Ver Tickets de Soporte',
                'description' => 'Acceso a visualizar tickets de soporte',
                'group' => 'support',
            ],
            [
                'name' => 'support.tickets.manage',
                'display_name' => 'Gestionar Tickets de Soporte',
                'description' => 'Capacidad de responder, cambiar estado y prioridad de tickets',
                'group' => 'support',
            ],
            [
                'name' => 'support.tickets.assign',
                'display_name' => 'Asignar Tickets de Soporte',
                'description' => 'Capacidad de asignar tickets a otros usuarios',
                'group' => 'support',
            ],
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission['name']],
                $permission
            );
        }

        // Asignar todos los permisos de soporte al rol admin
        $adminRole = Role::where('name', 'admin')->first();
        if ($adminRole) {
            $supportPermissions = Permission::where('group', 'support')->get();
            foreach ($supportPermissions as $permission) {
                if (! $adminRole->permissions()->where('permission_id', $permission->id)->exists()) {
                    $adminRole->permissions()->attach($permission->id);
                }
            }
        }

        $this->command->info('Support permissions created and assigned to admin role successfully!');
    }
}
