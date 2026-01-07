<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Service para CONSULTAR logs de actividad.
 * La ESCRITURA se hace via Observer + Job async.
 */
class ActivityLogService
{
    /**
     * Get activity history for a specific model instance.
     */
    public function getModelActivityLog(Model $model, int $limit = 50): Collection
    {
        return ActivityLog::query()
            ->where('target_model', get_class($model))
            ->where('target_id', $model->id)
            ->with('user:id,name,email')
            ->latest()
            ->limit($limit)
            ->get();
    }

    /**
     * Get all activity logs for a specific user.
     */
    public function getUserActivityLog(int $userId, int $limit = 50): Collection
    {
        return ActivityLog::query()
            ->where('user_id', $userId)
            ->with('user:id,name,email')
            ->latest()
            ->limit($limit)
            ->get();
    }

    /**
     * Get recent activity across all models.
     */
    public function getRecentActivity(int $days = 7, int $limit = 100): Collection
    {
        return ActivityLog::query()
            ->recent($days)
            ->with('user:id,name,email')
            ->latest()
            ->limit($limit)
            ->get();
    }

    /**
     * Get activity statistics.
     *
     * @return array{total_events: int, unique_users: int, today_events: int, by_event_type: array<string, int>}
     */
    public function getStats(?\DateTime $startDate = null, ?\DateTime $endDate = null): array
    {
        $query = ActivityLog::query();

        if ($startDate) {
            $query->where('created_at', '>=', $startDate);
        }
        if ($endDate) {
            $query->where('created_at', '<=', $endDate);
        }

        return [
            'total_events' => $query->count(),
            'unique_users' => $query->distinct('user_id')->count('user_id'),
            'today_events' => ActivityLog::whereDate('created_at', today())->count(),
            'by_event_type' => ActivityLog::query()
                ->selectRaw('event_type, count(*) as count')
                ->groupBy('event_type')
                ->pluck('count', 'event_type')
                ->toArray(),
        ];
    }
}
