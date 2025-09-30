<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\{HandlesExceptions, HasDataTableFeatures};
use App\Http\Requests\User\{StoreUserRequest, UpdateUserRequest};
use App\Models\User;
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
    use HandlesExceptions, HasDataTableFeatures;

    /**
     * Campos permitidos para ordenamiento
     */
    protected array $allowedSortFields = ['name', 'email', 'created_at', 'last_activity_at'];

    /**
     * Muestra la lista de todos los usuarios del sistema
     */
    public function index(Request $request): Response
    {
        // Obtener parámetros usando trait
        $params = $this->getPaginationParams($request);

        // Query base con eager loading optimizado
        $query = User::with(['roles' => function ($query) {
            $query->select('roles.id', 'roles.name', 'roles.is_system');
        }])
            ->select([
                'id',
                'name',
                'email',
                'email_verified_at',
                'created_at',
                'updated_at',
                'last_activity_at',
            ]);

        // Aplicar búsqueda usando trait
        $query = $this->applySearch($query, $params['search'], [
            'name',
            'email',
            'roles' => function ($roleQuery, $search) {
                $roleQuery->where('name', 'like', "%{$search}%");
            },
        ]);

        // Aplicar ordenamiento usando trait
        $fieldMappings = [
            'user' => 'name',
            'status' => $this->getStatusSortExpression('asc'),
        ];

        if (! empty($params['multiple_sort_criteria'])) {
            $query = $this->applyMultipleSorting($query, $params['multiple_sort_criteria'], $fieldMappings);
        } else {
            $query = $this->applySorting(
                $query,
                $params['sort_field'],
                $params['sort_direction'],
                $fieldMappings,
                $this->getStatusSortExpression('asc').' , created_at DESC'
            );
        }

        // Paginar y obtener usuarios
        $users = $query->paginate($params['per_page'])
            ->appends($request->all())
            ->through(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'email_verified_at' => $user->email_verified_at,
                    'created_at' => $user->created_at,
                    'updated_at' => $user->updated_at,
                    'last_activity' => $user->last_activity_at,
                    'is_online' => $user->is_online, // Usa accessor del modelo
                    'status' => $user->status, // Usa accessor del modelo
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
        $totalStats = User::select(['id', 'email_verified_at', 'last_activity_at'])->get();

        return Inertia::render('users/index', [
            'users' => $users,
            'total_users' => $totalStats->count(),
            'verified_users' => $totalStats->where('email_verified_at', '!=', null)->count(),
            'online_users' => $totalStats->filter(fn ($user) => $user->is_online)->count(),
            'filters' => $this->buildFiltersResponse($params),
        ]);
    }

    /**
     * Mantiene la sesión del usuario activa
     * Actualiza el last_activity_at cada 30 segundos
     */
    public function keepAlive(Request $request)
    {
        if (auth()->check()) {
            $user = auth()->user();
            $user->updateLastActivity();

            return response('', 204);
        }

        return response('', 401);
    }

    /**
     * Muestra el formulario para crear un nuevo usuario
     */
    public function create(): Response
    {
        return Inertia::render('users/create');
    }

    /**
     * Almacena un nuevo usuario
     */
    public function store(StoreUserRequest $request): RedirectResponse|\Inertia\Response
    {
        // If request contains search/filter parameters, redirect to index method
        if ($request->hasAny(['search', 'per_page', 'sort_field', 'sort_direction', 'page'])) {
            return $this->index($request);
        }

        return $this->executeWithExceptionHandling(
            operation: function () use ($request) {
                User::create([
                    'name' => $request->name,
                    'email' => $request->email,
                    'password' => Hash::make($request->password),
                    'email_verified_at' => now(), // Auto-verificar usuarios creados por admin
                ]);

                return redirect()->route('users.index')
                    ->with('success', 'Usuario creado exitosamente');
            },
            context: 'crear',
            entity: 'usuario'
        );
    }

    /**
     * Muestra el formulario para editar un usuario
     */
    public function edit(User $user): Response
    {
        $userData = [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'email_verified_at' => $user->email_verified_at?->toISOString(),
            'created_at' => $user->created_at?->toISOString(),
            'updated_at' => $user->updated_at?->toISOString(),
            'last_activity_at' => $user->last_activity_at?->toISOString(),
        ];

        return Inertia::render('users/edit', [
            'user' => $userData,
        ]);
    }

    /**
     * Actualiza un usuario existente
     */
    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        return $this->executeWithExceptionHandling(
            operation: function () use ($request, $user) {
                $userData = [
                    'name' => $request->name,
                    'email' => $request->email,
                ];

                // Solo actualizar contraseña si se proporciona
                if ($request->filled('password')) {
                    $userData['password'] = Hash::make($request->password);
                }

                // Si el email cambió, marcar como no verificado
                if ($user->email !== $request->email) {
                    $userData['email_verified_at'] = null;
                }

                $user->update($userData);

                return back()->with('success', 'Usuario actualizado exitosamente');
            },
            context: 'actualizar',
            entity: 'usuario'
        );
    }

    /**
     * Elimina un usuario
     */
    public function destroy(User $user): RedirectResponse
    {
        // Proteger al usuario admin principal
        if ($user->email === 'admin@admin.com') {
            return back()->with('error', 'No se puede eliminar el usuario administrador principal');
        }

        // Verificar que el usuario no se elimine a sí mismo
        if ($user->id === auth()->id()) {
            return back()->with('error', 'No puedes eliminar tu propia cuenta');
        }

        return $this->executeWithExceptionHandling(
            operation: function () use ($user) {
                $userName = $user->name;
                $user->delete();

                return back()->with('success', "Usuario '{$userName}' eliminado exitosamente");
            },
            context: 'eliminar',
            entity: 'usuario'
        );
    }
}
