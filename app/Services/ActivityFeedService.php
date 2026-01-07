<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\UserActivity;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Service para obtener el feed de actividad combinado de forma eficiente.
 * Usa UNION en BD para combinar UserActivity + ActivityLog con paginacion correcta.
 */
class ActivityFeedService
{
    /**
     * Event types to exclude from the feed.
     */
    private const EXCLUDED_EVENTS = ['heartbeat', 'page_view'];

    /**
     * Cache TTL for stats in seconds.
     */
    private const STATS_CACHE_TTL = 60;

    /**
     * Get paginated activity feed combining UserActivity and ActivityLog.
     */
    public function getFeed(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        // Subquery para UserActivity
        $userActivitiesQuery = DB::table('user_activities')
            ->select([
                DB::raw("CONCAT('ua_', id) as id"),
                'user_id',
                'activity_type as event_type',
                'description',
                'created_at',
                DB::raw("'user_activity' as source"),
                DB::raw('NULL as old_values'),
                DB::raw('NULL as new_values'),
                'metadata',
            ])
            ->whereNotIn('activity_type', self::EXCLUDED_EVENTS);

        // Subquery para ActivityLog
        $activityLogsQuery = DB::table('activity_logs')
            ->select([
                DB::raw("CONCAT('al_', id) as id"),
                'user_id',
                'event_type',
                'description',
                'created_at',
                DB::raw("'activity_log' as source"),
                'old_values',
                'new_values',
                DB::raw('NULL as metadata'),
            ])
            ->whereNotIn('event_type', self::EXCLUDED_EVENTS);

        // Aplicar filtros a ambas queries
        $this->applyFilters($userActivitiesQuery, $filters, 'activity_type');
        $this->applyFilters($activityLogsQuery, $filters, 'event_type');

        // Combinar con UNION
        $combinedQuery = $userActivitiesQuery->unionAll($activityLogsQuery);

        // Paginar el resultado combinado
        $results = DB::table(DB::raw("({$combinedQuery->toSql()}) as combined"))
            ->mergeBindings($combinedQuery)
            ->orderByDesc('created_at')
            ->paginate($perPage);

        // Cargar usuarios de forma eficiente
        $this->loadUsers($results);

        return $results;
    }

    /**
     * Apply filters to a query.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     */
    protected function applyFilters($query, array $filters, string $eventTypeColumn): void
    {
        if (! empty($filters['start_date'])) {
            $query->whereDate('created_at', '>=', $filters['start_date']);
        }

        if (! empty($filters['end_date'])) {
            $query->whereDate('created_at', '<=', $filters['end_date']);
        }

        if (! empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (! empty($filters['event_type'])) {
            $eventTypes = is_array($filters['event_type'])
                ? $filters['event_type']
                : explode(',', $filters['event_type']);
            $query->whereIn($eventTypeColumn, $eventTypes);
        }

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%");
            });
        }
    }

    /**
     * Load users for the paginated results.
     */
    protected function loadUsers(LengthAwarePaginator $results): void
    {
        $userIds = collect($results->items())->pluck('user_id')->unique()->filter();

        if ($userIds->isEmpty()) {
            return;
        }

        $users = DB::table('users')
            ->whereIn('id', $userIds)
            ->select('id', 'name', 'email')
            ->get()
            ->keyBy('id');

        foreach ($results->items() as $item) {
            $user = $users[$item->user_id] ?? null;
            $item->user = $user ? [
                'name' => $user->name,
                'email' => $user->email,
                'initials' => $this->getInitials($user->name),
            ] : [
                'name' => 'Usuario eliminado',
                'email' => 'N/A',
                'initials' => 'UD',
            ];
        }
    }

    /**
     * Get statistics for the activity feed.
     *
     * @return array{total_events: int, unique_users: int, today_events: int}
     */
    public function getStats(array $filters = []): array
    {
        $cacheKey = 'activity_stats_'.md5(json_encode($filters));

        return Cache::remember($cacheKey, self::STATS_CACHE_TTL, function () use ($filters) {
            $userActivityCount = UserActivity::query()
                ->when(! empty($filters['start_date']), fn ($q) => $q->whereDate('created_at', '>=', $filters['start_date']))
                ->when(! empty($filters['end_date']), fn ($q) => $q->whereDate('created_at', '<=', $filters['end_date']))
                ->whereNotIn('activity_type', self::EXCLUDED_EVENTS)
                ->count();

            $activityLogCount = ActivityLog::query()
                ->when(! empty($filters['start_date']), fn ($q) => $q->whereDate('created_at', '>=', $filters['start_date']))
                ->when(! empty($filters['end_date']), fn ($q) => $q->whereDate('created_at', '<=', $filters['end_date']))
                ->whereNotIn('event_type', self::EXCLUDED_EVENTS)
                ->count();

            $todayUserActivity = UserActivity::whereDate('created_at', today())
                ->whereNotIn('activity_type', self::EXCLUDED_EVENTS)
                ->count();

            $todayActivityLog = ActivityLog::whereDate('created_at', today())
                ->whereNotIn('event_type', self::EXCLUDED_EVENTS)
                ->count();

            // Unique users using UNION
            $uniqueUsers = DB::table(DB::raw("(
                SELECT DISTINCT user_id FROM user_activities WHERE activity_type NOT IN ('heartbeat', 'page_view')
                UNION
                SELECT DISTINCT user_id FROM activity_logs WHERE event_type NOT IN ('heartbeat', 'page_view')
            ) as combined"))
                ->count();

            return [
                'total_events' => $userActivityCount + $activityLogCount,
                'unique_users' => $uniqueUsers,
                'today_events' => $todayUserActivity + $todayActivityLog,
            ];
        });
    }

    /**
     * Get user initials from name.
     */
    protected function getInitials(string $name): string
    {
        $parts = explode(' ', trim($name));
        $initials = '';
        foreach (array_slice($parts, 0, 2) as $part) {
            $initials .= strtoupper(substr($part, 0, 1));
        }

        return $initials ?: 'UD';
    }
}
