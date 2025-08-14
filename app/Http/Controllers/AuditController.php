<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use App\Models\UserActivity;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Carbon\Carbon;

/**
 * Controlador para la gestión de actividad y logs del sistema
 * Proporciona vistas y datos para el seguimiento de actividades y eventos
 */
class AuditController extends Controller
{
    /**
     * Muestra la página principal de actividad con logs y actividades
     * 
     * @param Request $request
     * @return Response
     */
    public function index(Request $request): Response
    {
        // Obtener parámetros de filtrado
        $searchTerm = $request->get('search', '');
        $eventType = $request->get('event_type', '');
        $userId = $request->get('user_id', '');
        $dateRange = $request->get('date_range', 'last_14_days');
        $perPage = $request->get('per_page', 10);

        // Calcular rango de fechas
        $dateFrom = $this->getDateFromRange($dateRange);

        // Query base para actividades de usuarios
        $activitiesQuery = UserActivity::with('user')
            ->when($dateFrom, fn($query) => $query->where('created_at', '>=', $dateFrom))
            ->when($searchTerm && !empty(trim($searchTerm)), function ($query) use ($searchTerm) {
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
            ->when($eventType && !empty(trim($eventType)), fn($query) => $query->where('activity_type', $eventType))
            ->when($userId && !empty(trim($userId)), fn($query) => $query->where('user_id', $userId));

        // Query base para logs de actividad
        $auditQuery = AuditLog::with('user')
            ->when($dateFrom, fn($query) => $query->where('created_at', '>=', $dateFrom))
            ->when($searchTerm && !empty(trim($searchTerm)), function ($query) use ($searchTerm) {
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('description', 'like', "%{$searchTerm}%")
                      ->orWhere('event_type', 'like', "%{$searchTerm}%")
                      ->orWhere('target_model', 'like', "%{$searchTerm}%")
                      ->orWhereHas('user', function ($userQuery) use ($searchTerm) {
                          $userQuery->where('name', 'like', "%{$searchTerm}%")
                                   ->orWhere('email', 'like', "%{$searchTerm}%");
                      });
                });
            })
            ->when($eventType && !empty(trim($eventType)), fn($query) => $query->where('event_type', $eventType))
            ->when($userId && !empty(trim($userId)), fn($query) => $query->where('user_id', $userId));

        // Combinar y ordenar ambas consultas
        $allActivities = collect();
        
        // Agregar actividades de usuario
        $userActivities = $activitiesQuery->get()->map(function ($activity) {
            return [
                'id' => 'ua_' . $activity->id,
                'type' => 'activity',
                'user' => [
                    'name' => $activity->user?->name ?? 'Usuario eliminado',
                    'email' => $activity->user?->email ?? 'N/A',
                    'initials' => $this->getUserInitials($activity->user?->name ?? 'UD'),
                ],
                'event_type' => $activity->activity_type,
                'description' => $activity->description,
                'created_at' => $activity->created_at,
                'ip_address' => $activity->ip_address,
                'metadata' => $activity->metadata,
                'old_values' => null,
                'new_values' => null,
            ];
        });
        
        // Agregar logs de auditoría
        $auditLogs = $auditQuery->get()->map(function ($log) {
            return [
                'id' => 'al_' . $log->id,
                'type' => 'audit',
                'user' => [
                    'name' => $log->user?->name ?? 'Usuario eliminado',
                    'email' => $log->user?->email ?? 'N/A',
                    'initials' => $this->getUserInitials($log->user?->name ?? 'UD'),
                ],
                'event_type' => $log->event_type,
                'description' => $log->description,
                'created_at' => $log->created_at,
                'ip_address' => $log->ip_address,
                'metadata' => null,
                'old_values' => $log->old_values,
                'new_values' => $log->new_values,
            ];
        });
        
        // Combinar y ordenar por fecha
        $allActivities = $userActivities->concat($auditLogs)
            ->sortByDesc('created_at')
            ->values();
        
        // Paginación manual
        $perPage = (int) $perPage;
        $currentPage = request()->get('page', 1);
        $offset = ($currentPage - 1) * $perPage;
        $paginatedActivities = $allActivities->slice($offset, $perPage);
        
        $activities = new \Illuminate\Pagination\LengthAwarePaginator(
            $paginatedActivities->values(), // ✅ Convertir a array con valores indexados
            $allActivities->count(),
            $perPage,
            $currentPage,
            [
                'path' => $request->url(), 
                'pageName' => 'page'
            ]
        );
        
        // ✅ SOLUCIÓN: Preservar todos los filtros en la paginación
        $activities->appends($request->all());

        // Obtener estadísticas del TOTAL (sin filtros de búsqueda, solo rango de fechas)
        $totalStatsQuery = UserActivity::with('user')
            ->when($dateFrom, fn($query) => $query->where('created_at', '>=', $dateFrom));
        
        $totalAuditStatsQuery = AuditLog::with('user')
            ->when($dateFrom, fn($query) => $query->where('created_at', '>=', $dateFrom));
        
        // Si hay filtros aplicados, calcular estadísticas de los resultados filtrados
        if ($searchTerm || $eventType || $userId) {
            $totalEvents = $allActivities->count();
            $uniqueUsers = $allActivities->pluck('user.id')->unique()->count();
            $todayEvents = $allActivities->filter(function($activity) {
                return \Carbon\Carbon::parse($activity['created_at'])->isToday();
            })->count();
        } else {
            // Sin filtros, usar estadísticas del total
            $totalEvents = $totalStatsQuery->count() + $totalAuditStatsQuery->count();
            $uniqueUsers = collect([
                $totalStatsQuery->distinct('user_id')->pluck('user_id'), 
                $totalAuditStatsQuery->distinct('user_id')->pluck('user_id')
            ])->flatten()->unique()->count();
            $todayEvents = $totalStatsQuery->whereDate('created_at', today())->count() + $totalAuditStatsQuery->whereDate('created_at', today())->count();
        }

        // Obtener tipos de eventos únicos para filtros (actividades + auditoría)
        $activityTypes = UserActivity::select('activity_type')
            ->distinct()
            ->pluck('activity_type');
        
        $auditTypes = AuditLog::select('event_type')
            ->distinct()
            ->pluck('event_type');
        
        $allEventTypes = $activityTypes->concat($auditTypes)->unique();
        
        $eventTypes = $allEventTypes->mapWithKeys(function ($type) {
            return [$type => ucfirst(str_replace('_', ' ', $type))];
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

        return Inertia::render('audit/index', [
            'activities' => $activities,
            'filters' => [
                'search' => $searchTerm,
                'event_type' => $eventType,
                'user_id' => $userId,
                'date_range' => $dateRange,
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
                'date_ranges' => $this->getDateRangeOptions(),
                'per_page_options' => [10, 25, 50, 100],
            ],
        ]);
    }



    /**
     * Obtiene las iniciales de un nombre de usuario
     * 
     * @param string $name
     * @return string
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

    /**
     * Obtiene la fecha de inicio basada en el rango seleccionado
     * 
     * @param string $range
     * @return Carbon|null
     */
    private function getDateFromRange(string $range): ?Carbon
    {
        return match ($range) {
            'today' => Carbon::today(),
            'yesterday' => Carbon::yesterday(),
            'last_7_days' => Carbon::now()->subDays(7),
            'last_14_days' => Carbon::now()->subDays(14),
            'last_30_days' => Carbon::now()->subDays(30),
            'last_90_days' => Carbon::now()->subDays(90),
            default => null,
        };
    }

    /**
     * Obtiene las opciones de rango de fechas
     * 
     * @return array
     */
    private function getDateRangeOptions(): array
    {
        return [
            'today' => 'Hoy',
            'yesterday' => 'Ayer',
            'last_7_days' => 'Últimos 7 días',
            'last_14_days' => 'Últimos 14 días',
            'last_30_days' => 'Últimos 30 días',
            'last_90_days' => 'Últimos 90 días',
            'all' => 'Todo el tiempo',
        ];
    }
}