<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Carbon\Carbon;

/**
 * Controlador para la gestión de usuarios del sistema
 * Proporciona funcionalidades para listar y gestionar usuarios
 */
class UserController extends Controller
{
    /**
     * Muestra la lista de todos los usuarios del sistema
     * 
     * @param Request $request - Request actual
     * @return \Inertia\Response - Vista de Inertia con la lista de usuarios
     */
    public function index(Request $request): Response
    {
        // Obtener parámetros de paginación
        $perPage = $request->get('per_page', 10);
        $search = $request->get('search', '');
        
        // Query base
        $query = User::with('roles')
            ->select([
                'id',
                'name',
                'email',
                'email_verified_at',
                'created_at',
                'updated_at',
                'last_activity_at'
            ]);
        
        // Aplicar búsqueda si existe
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }
        
        // Paginar y obtener usuarios
        $users = $query->orderBy('created_at', 'desc')
            ->paginate($perPage)
            ->appends($request->all()) // ✅ SOLUCIÓN: Preservar filtros en paginación
            ->through(function ($user) {
            $isOnline = $this->isUserOnline($user->last_activity_at);
            $status = $this->getUserStatus($user->last_activity_at);
            
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'email_verified_at' => $user->email_verified_at,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
                'last_activity' => $user->last_activity_at,
                'is_online' => $isOnline,
                'status' => $status,
                'roles' => $user->roles->map(function ($role) {
                    return [
                        'id' => $role->id,
                        'name' => $role->name,
                        'display_name' => $role->display_name,
                        'is_system' => $role->is_system,
                    ];
                }),
            ];
        });

        // Obtener estadísticas del total (sin paginación)
        $totalStats = User::select([
            'id',
            'email_verified_at',
            'last_activity_at'
        ])->get();

        return Inertia::render('users/index', [
            'users' => $users,
            'total_users' => $totalStats->count(),
            'verified_users' => $totalStats->where('email_verified_at', '!=', null)->count(),
            'online_users' => $totalStats->filter(function ($user) {
                return $this->isUserOnline($user->last_activity_at);
            })->count(),
            'filters' => [
                'search' => $search,
                'per_page' => $perPage,
            ],
        ]);
    }

    /**
     * Mantiene la sesión del usuario activa
     * Actualiza el last_activity_at cada 30 segundos
     * 
     * @param Request $request
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
     * Determina si un usuario está en línea
     * En línea: Última actividad < 5 minutos
     */
    private function isUserOnline($lastActivityAt): bool
    {
        if (!$lastActivityAt) {
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
        if (!$lastActivityAt) {
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
