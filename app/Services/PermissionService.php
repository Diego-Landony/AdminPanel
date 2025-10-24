<?php

namespace App\Services;

use App\Models\Permission;
use Illuminate\Support\Facades\Log;

/**
 * Servicio para gestionar permisos del sistema
 *
 * Este servicio genera permisos basándose en el archivo de configuración
 * config/permissions.php, que define explícitamente todas las páginas
 * y sus acciones permitidas.
 */
class PermissionService
{
    /**
     * Genera todos los permisos basándose en la configuración
     *
     * @return array Array de permisos a crear/actualizar
     */
    public function generatePermissions(): array
    {
        $pages = config('permissions.pages', []);
        $baseActions = config('permissions.actions', []);
        $permissions = [];

        foreach ($pages as $pageName => $config) {
            foreach ($config['actions'] as $action) {
                $permissions[] = [
                    'name' => "{$pageName}.{$action}",
                    'display_name' => "{$baseActions[$action]} {$config['display_name']}",
                    'description' => $this->generateDescription($action, $config['description']),
                    'group' => $pageName,
                ];
            }
        }

        return $permissions;
    }

    /**
     * Sincroniza los permisos con la base de datos
     *
     * @param  bool  $removeObsolete  Si debe eliminar permisos obsoletos (no recomendado)
     * @return array Resultado de la sincronización
     */
    public function syncPermissions(bool $removeObsolete = false): array
    {
        Log::info('Iniciando sincronización de permisos', [
            'remove_obsolete' => $removeObsolete,
            'user_id' => auth()->id(),
        ]);

        $discoveredPermissions = $this->generatePermissions();
        $created = 0;
        $updated = 0;
        $deleted = 0;

        // Obtener nombres de permisos descubiertos
        $discoveredPermissionNames = collect($discoveredPermissions)->pluck('name');

        foreach ($discoveredPermissions as $permissionData) {
            $permission = Permission::updateOrCreate(
                ['name' => $permissionData['name']],
                $permissionData
            );

            if ($permission->wasRecentlyCreated) {
                $created++;
            } else {
                $updated++;
            }
        }

        // Eliminar permisos obsoletos si se solicita (usar con precaución)
        if ($removeObsolete) {
            $obsoletePermissions = Permission::whereNotIn('name', $discoveredPermissionNames)->get();

            foreach ($obsoletePermissions as $permission) {
                // No eliminar permisos de perfil (se manejan manualmente)
                if (! str_starts_with($permission->name, 'profile.')) {
                    $permission->delete();
                    $deleted++;
                }
            }
        }

        $result = [
            'total_pages' => count(config('permissions.pages', [])),
            'total_permissions' => count($discoveredPermissions),
            'created' => $created,
            'updated' => $updated,
            'deleted' => $deleted,
        ];

        Log::info('Sincronización de permisos completada', $result);

        return $result;
    }

    /**
     * Genera la descripción de un permiso
     *
     * @param  string  $action  Acción del permiso
     * @param  string  $pageDescription  Descripción de la página
     * @return string Descripción del permiso
     */
    private function generateDescription(string $action, string $pageDescription): string
    {
        return match ($action) {
            'view' => "Ver {$pageDescription}",
            'create' => "Crear {$pageDescription}",
            'edit' => "Editar {$pageDescription}",
            'delete' => "Eliminar {$pageDescription}",
            default => "Permiso de {$action} en {$pageDescription}"
        };
    }

    /**
     * Obtiene la configuración de todas las páginas
     *
     * @return array Configuración completa de páginas
     */
    public function getPagesConfiguration(): array
    {
        $pages = config('permissions.pages', []);
        $configuration = [];

        foreach ($pages as $pageName => $config) {
            $configuration[$pageName] = [
                'name' => $pageName,
                'display_name' => $config['display_name'],
                'description' => $config['description'],
                'group' => $pageName,
                'actions' => $config['actions'],
                'permissions' => array_map(
                    fn ($action) => "{$pageName}.{$action}",
                    $config['actions']
                ),
            ];
        }

        return $configuration;
    }

    /**
     * Obtiene el nombre legible de un grupo de permisos
     *
     * @param  string  $group  Nombre del grupo
     * @return string Nombre legible del grupo
     */
    public function getGroupDisplayName(string $group): string
    {
        $pages = config('permissions.pages', []);

        return $pages[$group]['display_name'] ?? str($group)->title()->value();
    }
}
