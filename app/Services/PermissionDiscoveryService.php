<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use App\Models\Permission;

/**
 * Servicio para descubrir automáticamente páginas y generar permisos
 * 
 * Este servicio escanea las páginas del sistema y genera automáticamente
 * los permisos necesarios basado en las rutas y páginas existentes.
 */
class PermissionDiscoveryService
{
    /**
     * Páginas que deben ser excluidas del sistema de permisos
     */
    private array $excludedPages = [
        'auth',
        'settings'  // Settings tiene su propio manejo
    ];

    /**
     * Acciones base que cada página puede tener
     */
    private array $baseActions = [
        'view' => 'Ver',
        'create' => 'Crear',
        'edit' => 'Editar',
        'delete' => 'Eliminar'
    ];

    /**
     * Configuración específica de páginas con sus acciones permitidas
     */
    private array $pageConfig = [
        'dashboard' => [
            'actions' => ['view'],
            'display_name' => 'Dashboard',
            'description' => 'Panel principal del sistema'
        ],
        'users' => [
            'actions' => ['view', 'create', 'edit', 'delete'],
            'display_name' => 'Usuarios',
            'description' => 'Gestión de usuarios del sistema'
        ],
        'audit' => [
            'actions' => ['view'],
            'display_name' => 'Actividad',
            'description' => 'Logs de auditoría y actividad del sistema'
        ],
        'roles' => [
            'actions' => ['view', 'create', 'edit', 'delete'],
            'display_name' => 'Roles y Permisos',
            'description' => 'Gestión de roles y permisos del sistema'
        ]
    ];

    /**
     * Descubre automáticamente todas las páginas del sistema
     * 
     * @return array Array de configuraciones de páginas
     */
    public function discoverPages(): array
    {
        $pagesPath = resource_path('js/pages');
        $discoveredPages = [];

        if (!File::exists($pagesPath)) {
            return $discoveredPages;
        }

        // Escanear directorios en pages/
        $directories = File::directories($pagesPath);
        
        foreach ($directories as $directory) {
            $pageName = basename($directory);
            
            // Saltar páginas excluidas
            if (in_array($pageName, $this->excludedPages)) {
                continue;
            }

            // Usar configuración predefinida o detectar automáticamente
            if (isset($this->pageConfig[$pageName])) {
                $discoveredPages[$pageName] = $this->pageConfig[$pageName];
            } else {
                $discoveredPages[$pageName] = $this->autoDetectPageConfig($pageName, $directory);
            }
        }

        // Agregar páginas de un solo archivo (como dashboard.tsx)
        $files = File::files($pagesPath);
        foreach ($files as $file) {
            $pageName = pathinfo($file->getFilename(), PATHINFO_FILENAME);
            
            if (!in_array($pageName, $this->excludedPages) && !isset($discoveredPages[$pageName])) {
                if (isset($this->pageConfig[$pageName])) {
                    $discoveredPages[$pageName] = $this->pageConfig[$pageName];
                } else {
                    $discoveredPages[$pageName] = $this->autoDetectPageConfig($pageName);
                }
            }
        }

        return $discoveredPages;
    }

    /**
     * Detecta automáticamente la configuración de una página
     * 
     * @param string $pageName Nombre de la página
     * @param string|null $directory Directorio de la página
     * @return array Configuración de la página
     */
    private function autoDetectPageConfig(string $pageName, ?string $directory = null): array
    {
        $config = [
            'display_name' => Str::title($pageName),
            'description' => "Gestión de {$pageName}",
            'actions' => ['view'] // Por defecto solo ver
        ];

        // Si tiene directorio, verificar qué archivos existen para determinar acciones
        if ($directory && File::exists($directory)) {
            $files = collect(File::files($directory))->map(fn($file) => pathinfo($file->getFilename(), PATHINFO_FILENAME));
            
            if ($files->contains('create')) {
                $config['actions'][] = 'create';
            }
            if ($files->contains('edit')) {
                $config['actions'][] = 'edit';
            }
            // Si tiene index y create/edit, probablemente también tiene delete
            if ($files->contains('index') && ($files->contains('create') || $files->contains('edit'))) {
                $config['actions'][] = 'delete';
            }
        }

        return $config;
    }

    /**
     * Genera todos los permisos basado en las páginas descubiertas
     * 
     * @return array Array de permisos a crear
     */
    public function generatePermissions(): array
    {
        $pages = $this->discoverPages();
        $permissions = [];

        foreach ($pages as $pageName => $config) {
            foreach ($config['actions'] as $action) {
                $permissions[] = [
                    'name' => "{$pageName}.{$action}",
                    'display_name' => "{$this->baseActions[$action]} {$config['display_name']}",
                    'description' => $this->generatePermissionDescription($action, $config['description']),
                    'group' => $pageName
                ];
            }
        }

        return $permissions;
    }

    /**
     * Genera la descripción de un permiso
     * 
     * @param string $action Acción del permiso
     * @param string $pageDescription Descripción de la página
     * @return string Descripción del permiso
     */
    private function generatePermissionDescription(string $action, string $pageDescription): string
    {
        return match($action) {
            'view' => "Acceso a visualizar {$pageDescription}",
            'create' => "Capacidad de crear nuevos elementos en {$pageDescription}",
            'edit' => "Capacidad de modificar elementos existentes en {$pageDescription}",
            'delete' => "Capacidad de eliminar elementos en {$pageDescription}",
            default => "Permiso de {$action} en {$pageDescription}"
        };
    }

    /**
     * Sincroniza los permisos con la base de datos
     * 
     * @return array Resultado de la sincronización
     */
    public function syncPermissions(): array
    {
        $discoveredPermissions = $this->generatePermissions();
        $created = 0;
        $updated = 0;

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

        return [
            'discovered_pages' => count($this->discoverPages()),
            'total_permissions' => count($discoveredPermissions),
            'created' => $created,
            'updated' => $updated,
            'permissions' => $discoveredPermissions
        ];
    }

    /**
     * Obtiene la configuración de todas las páginas con sus grupos
     * 
     * @return array Configuración completa de páginas
     */
    public function getPagesConfiguration(): array
    {
        $pages = $this->discoverPages();
        $configuration = [];

        foreach ($pages as $pageName => $config) {
            $configuration[$pageName] = [
                'name' => $pageName,
                'display_name' => $config['display_name'],
                'description' => $config['description'],
                'group' => $pageName,
                'actions' => $config['actions'],
                'permissions' => array_map(
                    fn($action) => "{$pageName}.{$action}",
                    $config['actions']
                )
            ];
        }

        return $configuration;
    }

    /**
     * Obtiene el nombre legible de un grupo de permisos
     * 
     * @param string $group Nombre del grupo
     * @return string Nombre legible del grupo
     */
    public function getGroupDisplayName(string $group): string
    {
        $pages = $this->discoverPages();
        return $pages[$group]['display_name'] ?? Str::title($group);
    }
}
