<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\PermissionDiscoveryService;
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
    /**
     * Muestra la lista de roles
     */
    public function index(Request $request): Response
    {
        // Obtener parámetros de paginación y ordenamiento
        $perPage = $request->get('per_page', 10);
        $search = $request->get('search', '');
        $sortField = $request->get('sort_field', 'name');
        $sortDirection = $request->get('sort_direction', 'asc');

        // Query base con eager loading optimizado
        $query = Role::with([
            'permissions:id,name',
            'users:id,name,email',
        ]);

        // Aplicar búsqueda si existe
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhereHas('permissions', function ($permissionQuery) use ($search) {
                        $permissionQuery->where('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('users', function ($userQuery) use ($search) {
                        $userQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        // Aplicar ordenamiento
        if ($sortField === 'name') {
            $query->orderBy('name', $sortDirection);
        } elseif ($sortField === 'created_at') {
            $query->orderBy('created_at', $sortDirection);
        } else {
            // Ordenamiento por defecto: roles del sistema primero, luego por nombre
            $query->orderBy('is_system', 'desc')->orderBy('name', 'asc');
        }

        // Paginar y obtener roles
        $roles = $query->paginate($perPage)
            ->appends($request->all()) // ✅ SOLUCIÓN: Preservar filtros en paginación
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
            'filters' => [
                'search' => $search,
                'per_page' => $perPage,
                'sort_field' => $sortField,
                'sort_direction' => $sortDirection,
            ],
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

        $permissions = Permission::getGrouped();

        return Inertia::render('roles/create', [
            'permissions' => $permissions,
        ]);
    }

    /**
     * Almacena un nuevo rol
     */
    public function store(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255|unique:roles,name',
                'description' => 'nullable|string',
                'permissions' => 'required|array|min:1',
                'permissions.*' => 'exists:permissions,name',
            ], [
                'permissions.required' => 'Debes seleccionar al menos un permiso para el rol.',
                'permissions.min' => 'Debes seleccionar al menos un permiso para el rol.',
            ]);

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
        } catch (\Illuminate\Database\QueryException $e) {
            \Log::error('Error de base de datos al crear rol: '.$e->getMessage());

            // Verificar si es error de duplicado
            if (str_contains($e->getMessage(), 'UNIQUE constraint failed') ||
                str_contains($e->getMessage(), 'Duplicate entry') ||
                str_contains($e->getMessage(), 'UNIQUE constraint')) {
                return back()->with('error', 'El nombre del rol ya existe en el sistema. Usa un nombre diferente.');
            }

            return back()->with('error', 'Error de base de datos al crear el rol. Verifica que los datos sean correctos.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Los errores de validación se manejan automáticamente por Laravel
            throw $e;
        } catch (\Exception $e) {
            \Log::error('Error inesperado al crear rol: '.$e->getMessage());

            return back()->with('error', 'Error inesperado al crear el rol. Inténtalo de nuevo o contacta al administrador.');
        }
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
    public function update(Request $request, Role $role): RedirectResponse
    {
        try {
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

            $request->validate([
                'name' => 'required|string|max:255|unique:roles,name,'.$role->id,
                'description' => 'nullable|string|max:500',
                'permissions' => 'array',
            ]);

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
                $allPermissions = Permission::pluck('name')->toArray();
                $permissionNames = $allPermissions;
            }

            $role->update($newValues);

            // Sincronizar permisos - convertir nombres a IDs
            $permissionIds = Permission::whereIn('name', $permissionNames)->pluck('id')->toArray();
            $role->permissions()->sync($permissionIds);

            // Preparar nuevos valores para el log
            $newValues['permissions'] = $permissionNames;

            // Log de la actividad
            $this->logRoleUpdate($role, $oldValues, $newValues);

            return back()->with('success', 'Rol actualizado exitosamente');
        } catch (\Illuminate\Database\QueryException $e) {
            \Log::error('Error de base de datos al actualizar rol: '.$e->getMessage());

            // Verificar si es error de duplicado
            if (str_contains($e->getMessage(), 'UNIQUE constraint failed') ||
                str_contains($e->getMessage(), 'Duplicate entry') ||
                str_contains($e->getMessage(), 'UNIQUE constraint')) {
                return back()->with('error', 'El nombre del rol ya existe en el sistema. Usa un nombre diferente.');
            }

            return back()->with('error', 'Error de base de datos al actualizar el rol. Verifica que los datos sean correctos.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Los errores de validación se manejan automáticamente por Laravel
            throw $e;
        } catch (\Exception $e) {
            \Log::error('Error inesperado al actualizar rol: '.$e->getMessage());

            return back()->with('error', 'Error inesperado al actualizar el rol. Inténtalo de nuevo o contacta al administrador.');
        }
    }

    /**
     * Actualiza los usuarios asignados a un rol
     */
    public function updateUsers(Request $request, Role $role): JsonResponse
    {
        try {
            // Verificar si el usuario actual es administrador
            $currentUser = auth()->user();
            $isCurrentUserAdmin = $currentUser && $currentUser->hasRole('admin');

            // Verificar si es un rol del sistema
            if ($role->is_system && $role->name !== 'admin') {
                // Solo administradores pueden editar usuarios de roles del sistema
                if (! $isCurrentUserAdmin) {
                    return response()->json(['error' => 'No tienes permisos para editar usuarios de este rol del sistema'], 403);
                }
            }

            // Para el rol "admin", solo usuarios administradores pueden gestionar usuarios
            if ($role->name === 'admin' && ! $isCurrentUserAdmin) {
                return response()->json(['error' => 'Solo los administradores pueden gestionar usuarios del rol Administrador'], 403);
            }

            $request->validate([
                'users' => 'array',
                'users.*' => 'exists:users,id',
            ]);

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

            // Log de la acción para actividad
            $this->logRoleUsersUpdate($role, $oldUserIds, $newUserIds);

            // Log adicional para debug
            \Log::info("Usuarios del rol '{$role->name}' actualizados", [
                'role_id' => $role->id,
                'old_users' => $oldUserIds,
                'new_users' => $newUserIds,
                'user_id' => auth()->id(),
            ]);

            return response()->json(['success' => 'Usuarios del rol actualizados exitosamente']);
        } catch (\Illuminate\Database\QueryException $e) {
            \Log::error('Error de base de datos al actualizar usuarios del rol: '.$e->getMessage());

            return response()->json(['error' => 'Error de base de datos al actualizar usuarios del rol. Verifica que los usuarios existan.'], 500);
        } catch (\Exception $e) {
            \Log::error('Error inesperado al actualizar usuarios del rol: '.$e->getMessage());

            return response()->json(['error' => 'Error inesperado al actualizar usuarios del rol. Inténtalo de nuevo o contacta al administrador.'], 500);
        }
    }

    /**
     * Elimina un rol
     */
    public function destroy(Role $role): RedirectResponse
    {
        try {
            // Verificar si el usuario actual es administrador
            $currentUser = auth()->user();
            $isCurrentUserAdmin = $currentUser && $currentUser->hasRole('admin');

            // Verificar si es un rol del sistema
            if ($role->is_system) {
                // Solo administradores pueden eliminar roles del sistema
                if (! $isCurrentUserAdmin) {
                    abort(403, 'No tienes permisos para eliminar este rol del sistema');
                }
            }

            // Verificar si tiene usuarios asignados
            $userCount = $role->users()->count();
            $successMessage = $userCount > 0
                ? "Rol eliminado exitosamente. {$userCount} usuarios perdieron este rol."
                : 'Rol eliminado exitosamente';

            $role->delete();

            return back()->with('success', $successMessage);
        } catch (\Illuminate\Database\QueryException $e) {
            \Log::error('Error de base de datos al eliminar rol: '.$e->getMessage());

            // Verificar si es error de restricción de clave foránea
            if (str_contains($e->getMessage(), 'FOREIGN KEY constraint failed') ||
                str_contains($e->getMessage(), 'Cannot delete or update a parent row')) {
                return back()->with('error', 'No se puede eliminar el rol porque tiene usuarios asignados. Primero remueve todos los usuarios del rol.');
            }

            return back()->with('error', 'Error de base de datos al eliminar el rol. Verifica que no tenga dependencias.');
        } catch (\Exception $e) {
            \Log::error('Error inesperado al eliminar rol: '.$e->getMessage());

            return back()->with('error', 'Error inesperado al eliminar el rol. Inténtalo de nuevo o contacta al administrador.');
        }
    }

    /**
     * Sincroniza automáticamente los permisos del sistema
     * Detecta nuevas páginas y actualiza permisos en tiempo real
     */
    private function syncPermissionsIfNeeded(): void
    {
        try {
            $discoveryService = new PermissionDiscoveryService;

            // Verificar si hay páginas nuevas detectadas
            $currentPages = $discoveryService->discoverPages();
            $currentPermissionNames = collect($discoveryService->generatePermissions())->pluck('name');
            $existingPermissionNames = Permission::pluck('name');

            // Si hay permisos nuevos, sincronizar automáticamente
            $newPermissions = $currentPermissionNames->diff($existingPermissionNames);

            if ($newPermissions->count() > 0) {
                \Log::info('Auto-sincronizando permisos: '.$newPermissions->join(', '));
                $discoveryService->syncPermissions();

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

    /**
     * Registra cambios en usuarios de roles
     */
    private function logRoleUsersUpdate(Role $role, array $oldUserIds, array $newUserIds): void
    {
        try {
            $addedUsers = array_diff($newUserIds, $oldUserIds);
            $removedUsers = array_diff($oldUserIds, $newUserIds);

            $description = "Usuarios actualizados para el rol '{$role->name}'";
            if (! empty($addedUsers)) {
                $addedUserNames = User::whereIn('id', $addedUsers)->pluck('name')->join(', ');
                $description .= " - Agregados: {$addedUserNames}";
            }
            if (! empty($removedUsers)) {
                $removedUserNames = User::whereIn('id', $removedUsers)->pluck('name')->join(', ');
                $description .= " - Removidos: {$removedUserNames}";
            }

            ActivityLog::create([
                'user_id' => auth()->id(),
                'event_type' => 'role_users_updated',
                'target_model' => 'Role',
                'target_id' => $role->id,
                'description' => $description,
                'old_values' => ['user_ids' => $oldUserIds],
                'new_values' => ['user_ids' => $newUserIds],
            ]);
        } catch (\Exception $e) {
            \Log::error('Error al registrar actividad de usuarios de rol: '.$e->getMessage());
        }
    }

    /**
     * Registra la actividad de actualización de un rol
     */
    private function logRoleUpdate(Role $role, array $oldValues, array $newValues): void
    {
        try {
            $changes = [];
            $description = "Rol '{$role->name}' actualizado";

            // Detectar cambios en nombre
            if (isset($oldValues['name']) && isset($newValues['name']) && $oldValues['name'] !== $newValues['name']) {
                $changes[] = "nombre: '{$oldValues['name']}' → '{$newValues['name']}'";
            }

            // Detectar cambios en descripción
            if (isset($oldValues['description']) && isset($newValues['description']) && $oldValues['description'] !== $newValues['description']) {
                $oldDesc = $oldValues['description'] ?: '(sin descripción)';
                $newDesc = $newValues['description'] ?: '(sin descripción)';
                $changes[] = "descripción: '{$oldDesc}' → '{$newDesc}'";
            }

            // Detectar cambios en permisos
            if (isset($oldValues['permissions']) && isset($newValues['permissions'])) {
                $oldPermissions = array_values($oldValues['permissions']);
                $newPermissions = array_values($newValues['permissions']);
                
                sort($oldPermissions);
                sort($newPermissions);
                
                if ($oldPermissions !== $newPermissions) {
                    $addedPermissions = array_diff($newPermissions, $oldPermissions);
                    $removedPermissions = array_diff($oldPermissions, $newPermissions);
                    
                    if (!empty($addedPermissions)) {
                        $changes[] = "permisos agregados: " . implode(', ', $addedPermissions);
                    }
                    
                    if (!empty($removedPermissions)) {
                        $changes[] = "permisos removidos: " . implode(', ', $removedPermissions);
                    }
                }
            }

            // Si hay cambios, agregar al descripción
            if (!empty($changes)) {
                $description .= " - " . implode(', ', $changes);
            }

            ActivityLog::create([
                'user_id' => auth()->id(),
                'event_type' => 'role_updated',
                'target_model' => 'Role',
                'target_id' => $role->id,
                'description' => $description,
                'old_values' => $oldValues,
                'new_values' => $newValues,
            ]);
        } catch (\Exception $e) {
            \Log::error('Error al registrar actividad de actualización de rol: '.$e->getMessage());
        }
    }
}
