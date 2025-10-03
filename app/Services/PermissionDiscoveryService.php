<?php

namespace App\Services;

use App\Models\Permission;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Servicio para descubrir automáticamente páginas y generar permisos
 *
 * Este servicio escanea las páginas del sistema y genera automáticamente
 * los permisos necesarios basado en las rutas y páginas existentes.
 */
class PermissionDiscoveryService
{
    /**
     * Tiempo de cache en minutos
     */
    private const CACHE_TTL = 60;

    /**
     * Páginas que deben ser excluidas del sistema de permisos
     */
    private array $excludedPages = [
        'auth',
        'settings',  // Settings tiene su propio manejo
        'no-access', // Página de sistema para usuarios sin permisos
    ];

    /**
     * Acciones base que cada página puede tener
     */
    private array $baseActions = [
        'view' => 'Ver',
        'create' => 'Crear',
        'edit' => 'Editar',
        'delete' => 'Eliminar',
    ];

    /**
     * Configuración específica de páginas con sus acciones permitidas
     */
    private array $pageConfig = [
        'home' => [
            'actions' => ['view'],
            'display_name' => 'Inicio',
            'description' => 'Página principal después del login',
        ],
        'dashboard' => [
            'actions' => ['view'],
            'display_name' => 'Dashboard',
            'description' => 'Panel principal del sistema',
        ],
        'users' => [
            'actions' => ['view', 'create', 'edit', 'delete'],
            'display_name' => 'Usuarios',
            'description' => 'Gestión de usuarios del sistema',
        ],
        'customers' => [
            'actions' => ['view', 'create', 'edit', 'delete'],
            'display_name' => 'Clientes',
            'description' => 'Gestión de clientes del sistema',
        ],
        'customer-types' => [
            'actions' => ['view', 'create', 'edit', 'delete'],
            'display_name' => 'Tipos de Cliente',
            'description' => 'Gestión de tipos de cliente del sistema',
        ],
        'activity' => [
            'actions' => ['view'],
            'display_name' => 'Actividad',
            'description' => 'Logs de actividad del sistema',
        ],
        'roles' => [
            'actions' => ['view', 'create', 'edit', 'delete'],
            'display_name' => 'Roles y Permisos',
            'description' => 'Gestión de roles y permisos del sistema',
        ],
    ];

    /**
     * Descubre automáticamente todas las páginas del sistema
     *
     * @param  bool  $useCache  Si debe usar cache
     * @return array Array de configuraciones de páginas
     */
    public function discoverPages(bool $useCache = true): array
    {
        // Intentar obtener desde cache
        if ($useCache) {
            $cached = Cache::get('permission_discovery.pages');
            if ($cached !== null) {
                return $cached;
            }
        }

        $pagesPath = resource_path('js/pages');
        $discoveredPages = [];

        if (! File::exists($pagesPath)) {
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

            // Buscar subdirectorios para páginas anidadas (como customers/types -> customer-types)
            $this->discoverNestedPages($directory, $discoveredPages);
        }

        // Agregar páginas de un solo archivo (como dashboard.tsx)
        $files = File::files($pagesPath);
        foreach ($files as $file) {
            $pageName = pathinfo($file->getFilename(), PATHINFO_FILENAME);

            if (! in_array($pageName, $this->excludedPages) && ! isset($discoveredPages[$pageName])) {
                if (isset($this->pageConfig[$pageName])) {
                    $discoveredPages[$pageName] = $this->pageConfig[$pageName];
                } else {
                    $discoveredPages[$pageName] = $this->autoDetectPageConfig($pageName);
                }
            }
        }

        // Guardar en cache si está habilitado
        if ($useCache) {
            Cache::put('permission_discovery.pages', $discoveredPages, now()->addMinutes(self::CACHE_TTL));
        }

        return $discoveredPages;
    }

    /**
     * Detecta automáticamente la configuración de una página
     *
     * @param  string  $pageName  Nombre de la página
     * @param  string|null  $directory  Directorio de la página
     * @return array Configuración de la página
     */
    private function autoDetectPageConfig(string $pageName, ?string $directory = null): array
    {
        $config = [
            'display_name' => Str::title($pageName),
            'description' => "Gestión de {$pageName}",
            'actions' => ['view'], // Por defecto solo ver
        ];

        // Si tiene directorio, verificar qué archivos existen para determinar acciones
        if ($directory && File::exists($directory)) {
            $files = collect(File::files($directory))->map(fn ($file) => pathinfo($file->getFilename(), PATHINFO_FILENAME));

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
     * @param  bool  $useCache  Si debe usar cache
     * @return array Array de permisos a crear
     */
    public function generatePermissions(bool $useCache = true): array
    {
        // Intentar obtener desde cache
        if ($useCache) {
            $cached = Cache::get('permission_discovery.permissions');
            if ($cached !== null) {
                return $cached;
            }
        }

        $pages = $this->discoverPages($useCache);
        $permissions = [];

        foreach ($pages as $pageName => $config) {
            foreach ($config['actions'] as $action) {
                $permissions[] = [
                    'name' => "{$pageName}.{$action}",
                    'display_name' => "{$this->baseActions[$action]} {$config['display_name']}",
                    'description' => $this->generatePermissionDescription($action, $config['description']),
                    'group' => $pageName,
                ];
            }
        }

        // Guardar en cache si está habilitado
        if ($useCache) {
            Cache::put('permission_discovery.permissions', $permissions, now()->addMinutes(self::CACHE_TTL));
        }

        return $permissions;
    }

    /**
     * Genera la descripción de un permiso
     *
     * @param  string  $action  Acción del permiso
     * @param  string  $pageDescription  Descripción de la página
     * @return string Descripción del permiso
     */
    private function generatePermissionDescription(string $action, string $pageDescription): string
    {
        return match ($action) {
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
     * @param  bool  $removeObsolete  Si debe eliminar permisos obsoletos
     * @return array Resultado de la sincronización
     */
    public function syncPermissions(bool $removeObsolete = false): array
    {
        Log::info('Iniciando sincronización de permisos', [
            'remove_obsolete' => $removeObsolete,
            'user_id' => auth()->id(),
        ]);

        // Limpiar cache antes de sincronizar
        $this->clearCache();

        $discoveredPermissions = $this->generatePermissions(false); // Sin cache durante sync
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

        // Eliminar permisos obsoletos si se solicita
        if ($removeObsolete) {
            $obsoletePermissions = Permission::whereNotIn('name', $discoveredPermissionNames)->get();

            foreach ($obsoletePermissions as $permission) {
                // No eliminar permisos de configuración (settings) ya que se manejan manualmente
                if (! str_starts_with($permission->name, 'settings.')) {
                    $permission->delete();
                    $deleted++;
                }
            }
        }

        $result = [
            'discovered_pages' => count($this->discoverPages(false)),
            'total_permissions' => count($discoveredPermissions),
            'created' => $created,
            'updated' => $updated,
            'deleted' => $deleted,
            'permissions' => $discoveredPermissions,
        ];

        Log::info('Sincronización de permisos completada', $result);

        return $result;
    }

    /**
     * Limpia el cache de permisos descubiertos
     */
    public function clearCache(): void
    {
        Cache::forget('permission_discovery.pages');
        Cache::forget('permission_discovery.permissions');

        Log::info('Cache de permisos limpiado');
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
                    fn ($action) => "{$pageName}.{$action}",
                    $config['actions']
                ),
            ];
        }

        return $configuration;
    }

    /**
     * Descubre páginas anidadas en subdirectorios
     *
     * @param  string  $parentDirectory  Directorio padre
     * @param  array  &$discoveredPages  Array de páginas descubiertas (por referencia)
     */
    private function discoverNestedPages(string $parentDirectory, array &$discoveredPages): void
    {
        $parentName = basename($parentDirectory);
        $subdirectories = File::directories($parentDirectory);

        foreach ($subdirectories as $subdirectory) {
            $subName = basename($subdirectory);

            // Crear nombre de página anidada (ej: customers/types -> customer-types)
            $nestedPageName = Str::singular($parentName).'-'.$subName;

            // Saltar si está excluida o ya existe
            if (in_array($nestedPageName, $this->excludedPages) || isset($discoveredPages[$nestedPageName])) {
                continue;
            }

            // Solo procesar si tiene archivos tsx/jsx (páginas React)
            $pageFiles = collect(File::files($subdirectory))
                ->filter(fn ($file) => in_array($file->getExtension(), ['tsx', 'jsx']));

            if ($pageFiles->isNotEmpty()) {
                // Usar configuración predefinida o detectar automáticamente
                if (isset($this->pageConfig[$nestedPageName])) {
                    $discoveredPages[$nestedPageName] = $this->pageConfig[$nestedPageName];
                } else {
                    $discoveredPages[$nestedPageName] = $this->autoDetectPageConfig($nestedPageName, $subdirectory);
                }
            }

            // Recursión para subdirectorios más profundos si es necesario
            $this->discoverNestedPages($subdirectory, $discoveredPages);
        }
    }

    /**
     * Obtiene el nombre legible de un grupo de permisos
     *
     * @param  string  $group  Nombre del grupo
     * @return string Nombre legible del grupo
     */
    public function getGroupDisplayName(string $group): string
    {
        $pages = $this->discoverPages();

        return $pages[$group]['display_name'] ?? Str::title($group);
    }
}
