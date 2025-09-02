<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use App\Models\UserActivity;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Controlador para la gestión de actividad del sistema
 * Proporciona vistas y datos para el seguimiento de actividades y eventos
 */
class ActivityController extends Controller
{
    /**
     * Muestra la página principal de actividad con logs y actividades
     */
    public function index(Request $request): Response
    {
        // Obtener parámetros de filtrado
        $searchTerm = $request->get('search', '');
        $eventType = $request->get('event_type', '');
        $userId = $request->get('user_id', '');
        $startDate = $request->get('start_date', '');
        $endDate = $request->get('end_date', '');
        $perPage = $request->get('per_page', 10);

        // Query base para actividades de usuarios
        $activitiesQuery = UserActivity::with('user')
            ->when($startDate, fn ($query) => $query->whereDate('created_at', '>=', $startDate))
            ->when($endDate, fn ($query) => $query->whereDate('created_at', '<=', $endDate))
            ->whereNotIn('activity_type', ['heartbeat', 'page_view'])
            ->when($searchTerm && ! empty(trim($searchTerm)), function ($query) use ($searchTerm) {
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('description', 'like', "%{$searchTerm}%")
                        ->orWhere('activity_type', 'like', "%{$searchTerm}%")
                        ->orWhere('url', 'like', "%{$searchTerm}%")
                        ->orWhereHas('user', function ($userQuery) use ($searchTerm) {
                            $userQuery->where('name', 'like', "%{$searchTerm}%")
                                ->orWhere('email', 'like', "%{$searchTerm}%");
                        });
                });
            })
            ->when($eventType && ! empty(trim($eventType)), function ($query) use ($eventType) {
                $eventTypes = explode(',', $eventType);
                $query->whereIn('activity_type', $eventTypes);
            })
            ->when($userId && ! empty(trim($userId)), fn ($query) => $query->where('user_id', $userId));

        // Query base para logs de actividad
        $activityQuery = ActivityLog::with('user')
            ->when($startDate, fn ($query) => $query->whereDate('created_at', '>=', $startDate))
            ->when($endDate, fn ($query) => $query->whereDate('created_at', '<=', $endDate))
            ->whereNotIn('event_type', ['heartbeat', 'page_view'])
            ->when($searchTerm && ! empty(trim($searchTerm)), function ($query) use ($searchTerm) {
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('description', 'like', "%{$searchTerm}%")
                        ->orWhere('event_type', 'like', "%{$searchTerm}%")
                        ->orWhereHas('user', function ($userQuery) use ($searchTerm) {
                            $userQuery->where('name', 'like', "%{$searchTerm}%")
                                ->orWhere('email', 'like', "%{$searchTerm}%");
                        });
                });
            })
            ->when($eventType, function ($query) use ($eventType) {
                $types = explode(',', $eventType);
                $query->whereIn('event_type', $types);
            })
            ->when($userId, fn ($query) => $query->where('user_id', $userId));

        // Obtener actividades de usuarios
        $userActivities = $activitiesQuery->get()->map(function ($activity) {
            return [
                'id' => 'ua_'.$activity->id,
                'type' => 'activity',
                'user' => [
                    'name' => $activity->user?->name ?? 'Usuario eliminado',
                    'email' => $activity->user?->email ?? 'N/A',
                    'initials' => $this->getUserInitials($activity->user?->name ?? 'UD'),
                ],
                'event_type' => $activity->activity_type,
                'description' => $activity->description,
                'created_at' => $activity->created_at,

                'metadata' => $activity->metadata,
                'old_values' => null,
                'new_values' => null,
            ];
        });

        // Agregar logs de actividad
        $activityLogs = $activityQuery->get()->map(function ($log) {
            return [
                'id' => 'al_'.$log->id,
                'type' => 'activity_log',
                'user' => [
                    'name' => $log->user?->name ?? 'Usuario eliminado',
                    'email' => $log->user?->email ?? 'N/A',
                    'initials' => $this->getUserInitials($log->user?->name ?? 'UD'),
                ],
                'event_type' => $log->event_type,
                'description' => $log->description,
                'created_at' => $log->created_at,

                'metadata' => null,
                'old_values' => $log->old_values,
                'new_values' => $log->new_values,
            ];
        });

        // Combinar y ordenar por fecha
        $allActivities = $userActivities->concat($activityLogs)
            ->sortByDesc('created_at')
            ->values();

        // Paginación manual
        $perPage = (int) $perPage;
        $currentPage = request()->get('page', 1);
        $offset = ($currentPage - 1) * $perPage;
        $paginatedActivities = $allActivities->slice($offset, $perPage);

        $activities = new \Illuminate\Pagination\LengthAwarePaginator(
            $paginatedActivities->values(),
            $allActivities->count(),
            $perPage,
            $currentPage,
            [
                'path' => $request->url(),
                'pageName' => 'page',
            ]
        );

        // Preservar todos los filtros en la paginación
        $activities->appends($request->all());

        // Obtener estadísticas del TOTAL (sin filtros de búsqueda, solo rango de fechas)
        $totalStatsQuery = UserActivity::with('user')
            ->when($startDate, fn ($query) => $query->whereDate('created_at', '>=', $startDate))
            ->when($endDate, fn ($query) => $query->whereDate('created_at', '<=', $endDate))
            ->whereNotIn('activity_type', ['heartbeat', 'page_view']);

        $totalActivityStatsQuery = ActivityLog::with('user')
            ->when($startDate, fn ($query) => $query->whereDate('created_at', '>=', $startDate))
            ->when($endDate, fn ($query) => $query->whereDate('created_at', '<=', $endDate))
            ->whereNotIn('event_type', ['heartbeat', 'page_view']);

        // Si hay filtros aplicados, calcular estadísticas de los resultados filtrados
        if ($searchTerm || $eventType || $userId) {
            $totalEvents = $allActivities->count();
            $uniqueUsers = $allActivities->pluck('user.id')->unique()->count();
            $todayEvents = $allActivities->filter(function ($activity) {
                return \Carbon\Carbon::parse($activity['created_at'])->isToday();
            })->count();
        } else {
            // Sin filtros, usar estadísticas del total
            $totalEvents = $totalStatsQuery->count() + $totalActivityStatsQuery->count();
            $uniqueUsers = collect([
                $totalStatsQuery->distinct('user_id')->pluck('user_id'),
                $totalActivityStatsQuery->distinct('user_id')->pluck('user_id'),
            ])->flatten()->unique()->count();
            $todayEvents = $totalStatsQuery->whereDate('created_at', today())->count() + $totalActivityStatsQuery->whereDate('created_at', today())->count();
        }

        // Obtener tipos de eventos únicos para filtros (actividades + logs)
        $activityTypes = UserActivity::select('activity_type')
            ->distinct()
            ->pluck('activity_type');

        $activityLogTypes = ActivityLog::select('event_type')
            ->distinct()
            ->pluck('event_type');

        // Filtrar tipos de eventos no deseados
        $filteredActivityTypes = $activityTypes->filter(function ($type) {
            return ! in_array($type, ['heartbeat', 'page_view']);
        });

        $filteredActivityLogTypes = $activityLogTypes->filter(function ($type) {
            return ! in_array($type, ['heartbeat', 'page_view']);
        });

        $allEventTypes = $filteredActivityTypes->concat($filteredActivityLogTypes)->unique();

        $eventTypes = $allEventTypes->mapWithKeys(function ($type) {
            // Mapeo de tipos de eventos en español
            $eventTypeTranslations = [
                'login' => 'Inicio de sesión',
                'role_users_updated' => 'Usuarios de rol actualizados',
                'user_roles_updated' => 'Roles de usuario actualizados',
                'role_created' => 'Rol creado',
                'role_deleted' => 'Rol eliminado',
                'role_updated' => 'Rol actualizado',
                'user_created' => 'Usuario creado',
                'user_updated' => 'Usuario actualizado',
                'user_deleted' => 'Usuario eliminado',
                'user_restored' => 'Usuario restaurado',
                'user_force_deleted' => 'Usuario eliminado permanentemente',
                'role_restored' => 'Rol restaurado',
                'role_force_deleted' => 'Rol eliminado permanentemente',
            ];

            return [$type => $eventTypeTranslations[$type] ?? ucfirst(str_replace('_', ' ', $type))];
        });

        // Obtener usuarios para filtros
        $users = User::select('id', 'name', 'email')
            ->orderBy('name')
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ];
            });

        return Inertia::render('activity/index', [
            'activities' => $activities,
            'filters' => [
                'search' => $searchTerm,
                'event_type' => $eventType,
                'user_id' => $userId,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'per_page' => $perPage,
            ],
            'stats' => [
                'total_events' => $totalEvents,
                'unique_users' => $uniqueUsers,
                'today_events' => $todayEvents,
            ],
            'options' => [
                'event_types' => $eventTypes,
                'users' => $users,
                'per_page_options' => [10, 25, 50, 100],
            ],
        ]);
    }

    /**
     * Obtiene las iniciales de un nombre de usuario
     */
    private function getUserInitials(string $name): string
    {
        $parts = explode(' ', trim($name));
        $initials = '';

        foreach (array_slice($parts, 0, 2) as $part) {
            $initials .= strtoupper(substr($part, 0, 1));
        }

        return $initials ?: 'UD';
    }
}
