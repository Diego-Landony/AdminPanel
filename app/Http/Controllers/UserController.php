<?php

namespace App\Http\Controllers;

use App\Jobs\LogActivityJob;
use App\Models\Role;
use App\Models\User;
use App\Rules\CustomPassword;
use App\Support\ActivityLogging;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Controlador para la gestión completa de usuarios del sistema
 * Proporciona funcionalidades CRUD para usuarios con roles y permisos
 */
class UserController extends Controller
{
    /**
     * Muestra la lista de todos los usuarios del sistema
     *
     * @param  Request  $request  - Request actual
     * @return \Inertia\Response - Vista de Inertia con la lista de usuarios
     */
    public function index(Request $request): Response
    {
        // Obtener parámetros de búsqueda, paginación y ordenamiento
        $search = $request->get('search', '');
        $perPage = $request->get('per_page', 15);
        $sortField = $request->get('sort_field', 'created_at');
        $sortDirection = $request->get('sort_direction', 'desc');

        // Query base con eager loading optimizado
        $query = User::with(['roles' => function ($query) {
            $query->select('roles.id', 'roles.name', 'roles.is_system');
        }])
            ->select([
                'id',
                'name',
                'email',
                'created_at',
                'updated_at',
                'last_activity_at',
            ]);

        // Aplicar búsqueda global si existe
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhereHas('roles', function ($roleQuery) use ($search) {
                        $roleQuery->where('name', 'like', "%{$search}%");
                    });
            });
        }

        // Aplicar ordenamiento
        if ($sortField === 'user' || $sortField === 'name') {
            $query->orderBy('name', $sortDirection);
        } elseif ($sortField === 'status') {
            $query->orderByRaw('
                CASE
                    WHEN last_activity_at IS NULL THEN 4
                    WHEN last_activity_at >= DATE_SUB(NOW(), INTERVAL 5 MINUTE) THEN 1
                    WHEN last_activity_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR) THEN 2
                    ELSE 3
                END '.($sortDirection === 'asc' ? 'ASC' : 'DESC'));
        } else {
            $query->orderBy($sortField, $sortDirection);
        }

        // Paginar y obtener usuarios
        $users = $query->paginate($perPage)
            ->appends($request->all()) // Preservar filtros en paginación
            ->through(function ($user) {
                $isOnline = $this->isUserOnline($user->last_activity_at);
                $status = $this->getUserStatus($user->last_activity_at);

                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'created_at' => $user->created_at,
                    'updated_at' => $user->updated_at,
                    'last_activity' => $user->last_activity_at,
                    'is_online' => $isOnline,
                    'status' => $status,
                    'roles' => $user->roles->map(function ($role) {
                        return [
                            'id' => $role->id,
                            'name' => $role->name,
                            'display_name' => $role->name,
                            'is_system' => $role->is_system,
                        ];
                    }),
                ];
            });

        // Obtener estadísticas del total (sin paginación)
        $totalStats = User::select([
            'id',
            'last_activity_at',
        ])->get();

        return Inertia::render('users/index', [
            'users' => $users,
            'total_users' => $totalStats->count(),
            'online_users' => $totalStats->filter(function ($user) {
                return $this->isUserOnline($user->last_activity_at);
            })->count(),
            'filters' => [
                'search' => $search,
                'per_page' => (int) $perPage,
                'sort_field' => $sortField,
                'sort_direction' => $sortDirection,
            ],
        ]);
    }

    /**
     * Mantiene la sesión del usuario activa
     * Actualiza el last_activity_at cada 30 segundos
     *
     * @return \Illuminate\Http\Response
     */
    public function keepAlive(Request $request)
    {
        if (auth()->check()) {
            $user = auth()->user();
            $user->updateLastActivity();

            // Devolver respuesta HTTP simple sin contenido
            return response('', 204);
        }

        // Si no está autenticado, devolver 401 sin contenido
        return response('', 401);
    }

    /**
     * Muestra el formulario para crear un nuevo usuario (solo datos básicos)
     */
    public function create(): Response
    {
        return Inertia::render('users/create');
    }

    /**
     * Almacena un nuevo usuario (solo datos básicos)
     */
    public function store(Request $request): RedirectResponse|\Inertia\Response
    {
        // If request contains search/filter parameters, redirect to index method
        if ($request->hasAny(['search', 'per_page', 'sort_field', 'sort_direction', 'page'])) {
            return $this->index($request);
        }

        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'email|max:255|unique:users',
                'password' => ['required', 'confirmed', new CustomPassword],
            ]);

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'email_verified_at' => now(), // Auto-verificar usuarios creados por admin
            ]);

            return redirect()->route('users.index')->with('success', 'Usuario creado exitosamente');
        } catch (\Illuminate\Database\QueryException $e) {
            \Log::error('Error de base de datos al crear usuario: '.$e->getMessage());

            // Verificar si es error de duplicado
            if (str_contains($e->getMessage(), 'UNIQUE constraint failed') ||
                str_contains($e->getMessage(), 'Duplicate entry') ||
                str_contains($e->getMessage(), 'UNIQUE constraint')) {
                return back()->with('error', 'El email ya está registrado en el sistema. Usa un email diferente.');
            }

            return back()->with('error', 'Error de base de datos al crear el usuario. Verifica que los datos sean correctos.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Los errores de validación se manejan automáticamente por Laravel
            throw $e;
        } catch (\Exception $e) {
            \Log::error('Error inesperado al crear usuario: '.$e->getMessage());

            return back()->with('error', 'Error inesperado al crear el usuario. Inténtalo de nuevo o contacta al administrador.');
        }
    }

    /**
     * Muestra el formulario para editar un usuario (solo datos básicos y contraseña)
     */
    public function edit(User $user): Response
    {
        $userData = [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'created_at' => $user->created_at ? $user->created_at->toISOString() : null,
            'updated_at' => $user->updated_at ? $user->updated_at->toISOString() : null,
            'last_activity_at' => $user->last_activity_at ? $user->last_activity_at->toISOString() : null,
            'roles' => $user->roles->pluck('id')->toArray(),
        ];

        $allRoles = \App\Models\Role::select('id', 'name', 'description')
            ->orderBy('name')
            ->get();

        return Inertia::render('users/edit', [
            'user' => $userData,
            'all_roles' => $allRoles,
        ]);
    }

    /**
     * Actualiza un usuario existente (solo datos básicos y contraseña)
     */
    public function update(Request $request, User $user): RedirectResponse
    {
        try {
            $rules = [
                'name' => 'required|string|max:255',
                'email' => 'required|string|lowercase|email|max:255|unique:users,email,'.$user->id,
            ];

            // Solo validar contraseña si se proporciona
            if ($request->filled('password')) {
                $rules['password'] = ['confirmed', new CustomPassword];
            }

            $request->validate($rules);

            $userData = [
                'name' => $request->name,
                'email' => $request->email,
            ];

            // Solo actualizar contraseña si se proporciona
            if ($request->filled('password')) {
                $userData['password'] = Hash::make($request->password);
            }

            $user->update($userData);

            return back()->with('success', 'Usuario actualizado exitosamente');
        } catch (\Illuminate\Database\QueryException $e) {
            \Log::error('Error de base de datos al actualizar usuario: '.$e->getMessage());

            // Verificar si es error de duplicado
            if (str_contains($e->getMessage(), 'UNIQUE constraint failed') ||
                str_contains($e->getMessage(), 'Duplicate entry') ||
                str_contains($e->getMessage(), 'UNIQUE constraint')) {
                return back()->with('error', 'El email ya está registrado por otro usuario. Usa un email diferente.');
            }

            return back()->with('error', 'Error de base de datos al actualizar el usuario. Verifica que los datos sean correctos.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Los errores de validación se manejan automáticamente por Laravel
            throw $e;
        } catch (\Exception $e) {
            \Log::error('Error inesperado al actualizar usuario: '.$e->getMessage());

            return back()->with('error', 'Error inesperado al actualizar el usuario. Inténtalo de nuevo o contacta al administrador.');
        }
    }

    /**
     * Elimina un usuario
     */
    public function destroy(User $user): RedirectResponse
    {
        try {
            // Proteger al usuario admin principal
            if ($user->email === 'admin@admin.com') {
                return back()->with('error', 'No se puede eliminar el usuario administrador principal');
            }

            // Verificar que el usuario no se elimine a sí mismo
            if ($user->id === auth()->id()) {
                return back()->with('error', 'No puedes eliminar tu propia cuenta');
            }

            $userName = $user->name;
            $user->delete();

            return back()->with('success', "Usuario '{$userName}' eliminado exitosamente");
        } catch (\Illuminate\Database\QueryException $e) {
            \Log::error('Error de base de datos al eliminar usuario: '.$e->getMessage());

            // Verificar si es error de restricción de clave foránea
            if (str_contains($e->getMessage(), 'FOREIGN KEY constraint failed') ||
                str_contains($e->getMessage(), 'Cannot delete or update a parent row')) {
                return back()->with('error', 'No se puede eliminar el usuario porque tiene registros asociados (roles, actividades, etc.).');
            }

            return back()->with('error', 'Error de base de datos al eliminar el usuario. Verifica que no tenga dependencias.');
        } catch (\Exception $e) {
            \Log::error('Error inesperado al eliminar usuario: '.$e->getMessage());

            return back()->with('error', 'Error inesperado al eliminar el usuario. Inténtalo de nuevo o contacta al administrador.');
        }
    }

    /**
     * Actualiza los roles de un usuario
     */
    public function updateRoles(Request $request, User $user): \Illuminate\Http\JsonResponse
    {
        $validated = $request->validate([
            'roles' => 'required|array',
            'roles.*' => 'exists:roles,id',
        ]);

        try {
            $roleIds = $validated['roles'];

            // Si el usuario siendo editado es el usuario autenticado y tiene el rol admin,
            // asegurar que el rol admin no se pueda remover
            if ($user->id === auth()->id() && $user->hasRole('admin')) {
                $adminRole = Role::where('name', 'admin')->first();
                if ($adminRole && ! in_array($adminRole->id, $roleIds)) {
                    return response()->json([
                        'success' => false,
                        'error' => 'No puedes remover tu propio rol de administrador',
                    ], 403);
                }
            }

            // Obtener roles anteriores para el log
            $oldRoles = $user->roles()->pluck('name')->toArray();

            $user->roles()->sync($roleIds);

            // Obtener nuevos roles para el log
            $newRoles = Role::whereIn('id', $roleIds)->pluck('name')->toArray();

            // Invalidar cache de permisos del usuario
            $user->flushPermissionsCache();

            // Registrar actividad si hay cambios
            if ($oldRoles !== $newRoles && ActivityLogging::isEnabled()) {
                $description = "Roles de '{$user->name}' actualizados";

                if (! empty($oldRoles) || ! empty($newRoles)) {
                    $oldStr = empty($oldRoles) ? '(ninguno)' : implode(', ', $oldRoles);
                    $newStr = empty($newRoles) ? '(ninguno)' : implode(', ', $newRoles);
                    $description .= " - roles: '{$oldStr}' -> '{$newStr}'";
                }

                $logData = [
                    'user_id' => auth()->id(),
                    'event_type' => 'roles_updated',
                    'target_model' => User::class,
                    'target_id' => $user->id,
                    'description' => $description,
                    'old_values' => ['roles' => $oldRoles],
                    'new_values' => ['roles' => $newRoles],
                    'user_agent' => $request->userAgent(),
                ];

                if (ActivityLogging::isAsync()) {
                    LogActivityJob::dispatch($logData);
                } else {
                    \App\Models\ActivityLog::create($logData);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Roles actualizados exitosamente',
            ]);
        } catch (\Exception $e) {
            \Log::error('Error al actualizar roles del usuario: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'error' => 'Error al actualizar los roles del usuario',
            ], 500);
        }
    }

    /**
     * Determina si un usuario está en línea
     * En línea: Última actividad < 5 minutos
     */
    private function isUserOnline($lastActivityAt): bool
    {
        if (! $lastActivityAt) {
            return false;
        }

        $lastActivity = Carbon::parse($lastActivityAt)->utc();
        $now = Carbon::now()->utc();

        return $lastActivity->diffInMinutes($now) < 5;
    }

    /**
     * Obtiene el estado del usuario basado en su última actividad
     * 🟢 En línea: Última actividad < 5 minutos (más realista)
     * 🔵 Reciente: Última actividad < 15 minutos (más preciso)
     * ⚫ Desconectado: Última actividad > 15 minutos
     * ❌ Nunca: Sin registro de actividad
     */
    private function getUserStatus($lastActivityAt): string
    {
        if (! $lastActivityAt) {
            return 'never';
        }

        $lastActivity = Carbon::parse($lastActivityAt)->utc();
        $now = Carbon::now()->utc();
        $minutesDiff = $lastActivity->diffInMinutes($now);

        if ($minutesDiff < 5) {
            return 'online';
        } elseif ($minutesDiff < 15) {
            return 'recent';
        } else {
            return 'offline';
        }
    }
}
