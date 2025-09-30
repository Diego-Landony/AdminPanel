<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\{HandlesExceptions, HasDataTableFeatures};
use App\Http\Requests\Role\{StoreRoleRequest, UpdateRoleRequest};
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\{ActivityLogService, PermissionDiscoveryService};
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Controlador para la gestión de roles del sistema
 */
class RoleController extends Controller
{
    use HandlesExceptions, HasDataTableFeatures;

    /**
     * Campos permitidos para ordenamiento
     */
    protected array $allowedSortFields = ['name', 'created_at'];

    public function __construct(
        protected ActivityLogService $activityLog,
        protected PermissionDiscoveryService $permissionDiscovery
    ) {}
    /**
     * Muestra la lista de roles
     */
    public function index(Request $request): Response
    {
        // Obtener parámetros usando trait
        $params = $this->getPaginationParams($request);

        // Query base con eager loading optimizado
        $query = Role::with([
            'permissions:id,name',
            'users:id,name,email',
        ]);

        // Aplicar búsqueda usando trait
        $query = $this->applySearch($query, $params['search'], [
            'name',
            'description',
            'permissions' => function ($permissionQuery, $search) {
                $permissionQuery->where('name', 'like', "%{$search}%");
            },
            'users' => function ($userQuery, $search) {
                $userQuery->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            },
        ]);

        // Aplicar ordenamiento usando trait
        $fieldMappings = [
            'role' => 'name',
        ];

        if (! empty($params['multiple_sort_criteria'])) {
            $query = $this->applyMultipleSorting($query, $params['multiple_sort_criteria'], $fieldMappings);
        } else {
            $query = $this->applySorting(
                $query,
                $params['sort_field'],
                $params['sort_direction'],
                $fieldMappings,
                'is_system DESC, name ASC'
            );
        }

        // Paginar y obtener roles
        $roles = $query->paginate($params['per_page'])
            ->appends($request->all())
            ->through(function ($role) {
                return [
                    'id' => $role->id,
                    'name' => $role->name,
                    'description' => $role->description,
                    'is_system' => $role->is_system,
                    'permissions' => $role->permissions->pluck('name'),
                    'users_count' => $role->users()->count(),
                    'users' => $role->users->map(function ($user) {
                        return [
                            'id' => $user->id,
                            'name' => $user->name,
                            'email' => $user->email,
                        ];
                    }),
                    'created_at' => $role->created_at,
                    'updated_at' => $role->updated_at,
                ];
            });

        $permissions = Permission::getGrouped();

        // Calcular estadísticas de roles
        $roleStats = [
            'total' => Role::count(),
            'system' => Role::where('is_system', true)->count(),
            'created' => Role::where('is_system', false)->count(),
        ];

        return Inertia::render('roles/index', [
            'roles' => $roles,
            'permissions' => $permissions,
            'filters' => $this->buildFiltersResponse($params),
            'roleStats' => $roleStats,
        ]);
    }

    /**
     * Muestra el formulario para crear un nuevo rol
     */
    public function create(): Response
    {
        // Sincronizar permisos automáticamente si hay nuevas páginas
        $this->syncPermissionsIfNeeded();

        return Inertia::render('roles/create', [
            'permissions' => Permission::getGrouped(),
        ]);
    }

    /**
     * Almacena un nuevo rol
     */
    public function store(StoreRoleRequest $request): \Illuminate\Http\RedirectResponse|\Inertia\Response
    {
        // If request contains search/filter parameters, redirect to index method
        if ($request->hasAny(['search', 'per_page', 'sort_field', 'sort_direction', 'page'])) {
            return $this->index($request);
        }

        return $this->executeWithExceptionHandling(
            operation: function () use ($request) {
                $role = Role::create([
                    'name' => $request->name,
                    'description' => $request->description,
                    'is_system' => false,
                ]);

                if ($request->has('permissions')) {
                    $permissionIds = Permission::whereIn('name', $request->permissions)->pluck('id');
                    $role->permissions()->sync($permissionIds);
                }

                return redirect()->route('roles.index')->with('success', 'Rol creado exitosamente');
            },
            context: 'crear',
            entity: 'rol'
        );
    }

    /**
     * Muestra el formulario para editar un rol
     */
    public function edit(Role $role): Response
    {
        // Verificar si el usuario actual es administrador
        $currentUser = auth()->user();
        $isCurrentUserAdmin = $currentUser && $currentUser->hasRole('admin');

        // Verificar si es un rol del sistema
        if ($role->is_system && $role->name !== 'admin') {
            // Solo administradores pueden editar roles del sistema
            if (! $isCurrentUserAdmin) {
                abort(403, 'No tienes permisos para editar este rol del sistema');
            }
        }

        // Para el rol "admin", solo usuarios administradores pueden editarlo
        if ($role->name === 'admin' && ! $isCurrentUserAdmin) {
            abort(403, 'Solo los administradores pueden editar el rol Administrador');
        }

        // Cargar relaciones necesarias para el frontend
        $role->load(['permissions', 'users']);

        // Sincronizar permisos automáticamente si hay nuevas páginas
        $this->syncPermissionsIfNeeded();

        // Obtener permisos agrupados como espera el frontend
        $permissions = Permission::getGrouped();
        $users = User::orderBy('name')->get();

        // Formatear el rol con los datos que espera el frontend
        $roleData = [
            'id' => $role->id,
            'name' => $role->name,
            'description' => $role->description,
            'is_system' => $role->is_system,
            'permissions' => $role->permissions->pluck('name')->toArray(),
            'users' => $role->users->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ];
            })->toArray(),
        ];

        return Inertia::render('roles/edit', [
            'role' => $roleData,
            'permissions' => $permissions,
            'all_users' => $users,
        ]);
    }

    /**
     * Actualiza un rol existente
     */
    public function update(UpdateRoleRequest $request, Role $role): RedirectResponse
    {
        return $this->executeWithExceptionHandling(
            operation: function () use ($request, $role) {
                // Capturar valores anteriores para el log de actividad
                $oldValues = [
                    'name' => $role->name,
                    'description' => $role->description,
                    'permissions' => $role->permissions()->pluck('name')->toArray(),
                ];

                $newValues = $request->only(['name', 'description']);
                $permissionNames = $request->input('permissions', []);

                // Si es el rol "admin", asegurar que tenga todos los permisos
                if ($role->name === 'admin') {
                    $permissionNames = Permission::pluck('name')->toArray();
                }

                $role->update($newValues);

                // Sincronizar permisos - convertir nombres a IDs
                $permissionIds = Permission::whereIn('name', $permissionNames)->pluck('id')->toArray();
                $role->permissions()->sync($permissionIds);

                // Preparar nuevos valores para el log
                $newValues['permissions'] = $permissionNames;

                // Log de la actividad usando servicio
                $this->activityLog->logUpdated($role, $oldValues, $newValues);

                return back()->with('success', 'Rol actualizado exitosamente');
            },
            context: 'actualizar',
            entity: 'rol'
        );
    }

    /**
     * Actualiza los usuarios asignados a un rol
     */
    public function updateUsers(Request $request, Role $role): JsonResponse
    {
        $request->validate([
            'users' => 'array',
            'users.*' => 'exists:users,id',
        ]);

        try {
            $oldUserIds = $role->users()->pluck('users.id')->toArray();
            $newUserIds = $request->input('users', []);

            // Si es el rol "admin", asegurar que el usuario admin@admin.com siempre esté asignado
            if ($role->name === 'admin') {
                $adminUser = User::where('email', 'admin@admin.com')->first();
                if ($adminUser && ! in_array($adminUser->id, $newUserIds)) {
                    $newUserIds[] = $adminUser->id;
                }
            }

            $role->users()->sync($newUserIds);

            // Log de la acción usando servicio
            $this->activityLog->logRoleUsersUpdate($role, $oldUserIds, $newUserIds);

            return response()->json(['success' => 'Usuarios del rol actualizados exitosamente']);
        } catch (\Exception $e) {
            \Log::error('Error al actualizar usuarios del rol: '.$e->getMessage());

            return response()->json(['error' => 'Error al actualizar usuarios del rol'], 500);
        }
    }

    /**
     * Elimina un rol
     */
    public function destroy(Role $role): RedirectResponse
    {
        return $this->executeWithExceptionHandling(
            operation: function () use ($role) {
                $userCount = $role->users()->count();
                $roleName = $role->name;

                $role->delete();

                $successMessage = $userCount > 0
                    ? "Rol '{$roleName}' eliminado exitosamente. {$userCount} usuarios perdieron este rol."
                    : "Rol '{$roleName}' eliminado exitosamente";

                return back()->with('success', $successMessage);
            },
            context: 'eliminar',
            entity: 'rol'
        );
    }

    /**
     * Sincroniza automáticamente los permisos del sistema
     * Detecta nuevas páginas y actualiza permisos en tiempo real
     */
    private function syncPermissionsIfNeeded(): void
    {
        try {
            // Verificar si hay páginas nuevas detectadas
            $currentPermissionNames = collect($this->permissionDiscovery->generatePermissions())->pluck('name');
            $existingPermissionNames = Permission::pluck('name');

            // Si hay permisos nuevos, sincronizar automáticamente
            $newPermissions = $currentPermissionNames->diff($existingPermissionNames);

            if ($newPermissions->count() > 0) {
                \Log::info('Auto-sincronizando permisos: '.$newPermissions->join(', '));
                $this->permissionDiscovery->syncPermissions();

                // Actualizar rol admin con nuevos permisos
                $adminRole = Role::where('name', 'admin')->first();
                if ($adminRole) {
                    $allPermissionIds = Permission::pluck('id');
                    $adminRole->permissions()->sync($allPermissionIds);
                }
            }
        } catch (\Exception $e) {
            \Log::error('Error en auto-sincronización de permisos: '.$e->getMessage());
        }
    }
}
