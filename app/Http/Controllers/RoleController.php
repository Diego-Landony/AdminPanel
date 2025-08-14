<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\Permission;
use App\Models\AuditLog;
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
        // Obtener parámetros de paginación
        $perPage = $request->get('per_page', 10);
        $search = $request->get('search', '');
        
        // Query base
        $query = Role::with(['permissions', 'users:id,name,email']);
        
        // Aplicar búsqueda si existe
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }
        
        // Paginar y obtener roles
        $roles = $query->orderBy('is_system', 'desc')
            ->orderBy('name')
            ->paginate($perPage)
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

        return Inertia::render('roles/index', [
            'roles' => $roles,
            'permissions' => $permissions,
            'filters' => [
                'search' => $search,
                'per_page' => $perPage,
            ],
        ]);
    }

    /**
     * Muestra el formulario para crear un nuevo rol
     */
    public function create(): Response
    {
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
        $request->validate([
            'name' => 'required|string|max:255|unique:roles,name',
            'description' => 'nullable|string',
            'permissions' => 'array',
            'permissions.*' => 'exists:permissions,name',
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

        // ✅ NOTA: El RoleObserver ya registra automáticamente la creación del rol
        // No necesitamos logging manual adicional

        return back()->with('success', 'Rol creado exitosamente');
    }

    /**
     * Muestra el formulario para editar un rol
     */
    public function edit(string $id): Response
    {
        $role = Role::with('permissions')->findOrFail($id);
        
        // Permitir editar el rol Administrador, pero no otros roles del sistema
        if ($role->is_system && $role->name !== 'Administrador') {
            return redirect()->route('roles.index')
                ->with('error', 'No se puede editar este rol del sistema');
        }

        $permissions = Permission::getGrouped();
        $allUsers = \App\Models\User::select('id', 'name', 'email')->orderBy('name')->get();

        // Cargar usuarios del rol
        $role->load('users:id,name,email');

        return Inertia::render('roles/edit', [
            'role' => [
                'id' => $role->id,
                'name' => $role->name,
                'description' => $role->description,
                'permissions' => $role->permissions->pluck('name')->toArray(),
                'users' => $role->users->map(function ($user) {
                    return [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                    ];
                }),
            ],
            'permissions' => $permissions,
            'all_users' => $allUsers->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ];
            }),
        ]);
    }

    /**
     * Actualiza un rol existente
     */
    public function update(Request $request, string $id)
    {
        $role = Role::findOrFail($id);
        
        // Permitir editar el rol Administrador, pero no otros roles del sistema
        if ($role->is_system && $role->name !== 'Administrador') {
            return redirect()->route('roles.index')
                ->with('error', 'No se puede editar este rol del sistema');
        }

        $request->validate([
            'name' => 'required|string|max:255|unique:roles,name,' . $id,
            'description' => 'nullable|string',
            'permissions' => 'array',
            'permissions.*' => 'exists:permissions,name',
        ]);

        $role->update([
            'name' => $request->name,
            'description' => $request->description,
        ]);

        // Para el rol Administrador, siempre sincronizar todos los permisos disponibles
        if ($role->name === 'Administrador') {
            $allPermissionIds = Permission::pluck('id');
            $role->permissions()->sync($allPermissionIds);
        } elseif ($request->has('permissions')) {
            $permissionIds = Permission::whereIn('name', $request->permissions)->pluck('id');
            $role->permissions()->sync($permissionIds);
        }

        return back()->with('success', 'Rol actualizado exitosamente');
    }

    /**
     * Actualiza los usuarios asignados a un rol
     */
    public function updateUsers(Request $request, string $id)
    {
        $role = Role::findOrFail($id);
        
        // Permitir gestionar usuarios del rol Administrador, pero no otros roles del sistema
        if ($role->is_system && $role->name !== 'Administrador') {
            return response()->json(['error' => 'No se puede modificar este rol del sistema'], 422);
        }

        $request->validate([
            'users' => 'array',
            'users.*' => 'exists:users,id',
        ]);

        $usersToAssign = $request->input('users', []);
        
        // Para el rol Administrador, asegurar que siempre tenga al menos un usuario admin
        if ($role->name === 'Administrador') {
            $adminUser = \App\Models\User::where('email', 'admin@admin.com')->first();
            if ($adminUser && !in_array($adminUser->id, $usersToAssign)) {
                return response()->json([
                    'error' => 'El rol Administrador debe tener al menos un usuario administrador asignado'
                ], 422);
            }
        }

        $previousUsers = $role->users()->pluck('users.id')->toArray();
        $role->users()->sync($usersToAssign);
        
        // Registrar operación de auditoría
        $this->logRoleUsersUpdate($role, $previousUsers, $usersToAssign);

        return response()->json(['success' => true]);
    }

    /**
     * Elimina un rol
     */
    public function destroy(string $id)
    {
        try {
            $role = Role::findOrFail($id);
            
            if ($role->is_system) {
                return back()->with('error', 'No se puede eliminar un rol del sistema');
            }

            // Si el rol tiene usuarios, mostrar advertencia pero permitir la eliminación
            if ($role->users()->count() > 0) {
                // Log de la acción para auditoría
                try {
                    \Log::info('Rol eliminado con usuarios asignados', [
                        'role_id' => $id,
                        'role_name' => $role->name,
                        'users_count' => $role->users()->count(),
                        'deleted_by_user_id' => auth()->id()
                    ]);
                } catch (\Exception $e) {
                    // Si falla el logging, continuar con la eliminación
                }
                
                // Eliminar el rol (esto automáticamente removerá las relaciones con usuarios)
                $role->delete();
                
                return back()->with('success', "Rol '{$role->name}' eliminado exitosamente.");
            }

            $role->delete();

            return back()->with('success', 'Rol eliminado exitosamente');
                
        } catch (\Exception $e) {
            return back()->with('error', 'Error al eliminar el rol: ' . $e->getMessage());
        }
    }

    /**
     * Registra operaciones específicas de roles en el sistema de auditoría
     */
    private function logRoleOperation(string $eventType, Role $role, string $description): void
    {
        try {
            AuditLog::create([
                'user_id' => auth()->id(),
                'event_type' => $eventType,
                'target_model' => 'Role',
                'target_id' => $role->id,
                'description' => $description,
                'old_values' => null,
                'new_values' => null,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        } catch (\Exception $e) {
            // Log del error pero no fallar la operación principal
            \Log::error('Error al registrar auditoría de rol: ' . $e->getMessage());
        }
    }

    /**
     * Registra cambios en la asignación de usuarios a roles
     */
    private function logRoleUsersUpdate(Role $role, array $previousUsers, array $newUsers): void
    {
        try {
            $addedUsers = array_diff($newUsers, $previousUsers);
            $removedUsers = array_diff($previousUsers, $newUsers);
            
            $description = "Usuarios actualizados para el rol '{$role->name}'";
            
            if (!empty($addedUsers)) {
                $userNames = \App\Models\User::whereIn('id', $addedUsers)->pluck('name')->toArray();
                $description .= " - Agregados: " . implode(', ', $userNames);
            }
            
            if (!empty($removedUsers)) {
                $userNames = \App\Models\User::whereIn('id', $removedUsers)->pluck('name')->toArray();
                $description .= " - Removidos: " . implode(', ', $userNames);
            }
            
            AuditLog::create([
                'user_id' => auth()->id(),
                'event_type' => 'role_users_updated',
                'target_model' => 'Role',
                'target_id' => $role->id,
                'description' => $description,
                'old_values' => ['users' => $previousUsers],
                'new_values' => ['users' => $newUsers],
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        } catch (\Exception $e) {
            // Log del error pero no fallar la operación principal
            \Log::error('Error al registrar auditoría de usuarios de rol: ' . $e->getMessage());
        }
    }
} 
