<?php

namespace App\Observers;

use App\Contracts\ActivityLoggable;
use App\Jobs\LogActivityJob;
use App\Models\ActivityLog;
use App\Support\ActivityLogging;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;

/**
 * Observer generico para registrar actividades de cualquier modelo.
 * Se registra automaticamente via el trait LogsActivity.
 */
class ActivityObserver
{
    public function created(Model $model): void
    {
        $this->logActivity('created', $model, newValues: $model->getAttributes());
    }

    public function updated(Model $model): void
    {
        $changes = $model->getChanges();
        $original = $model->getOriginal();

        // Ignorar si solo cambiaron campos ignorados
        if ($this->isOnlyIgnoredFieldsUpdate($changes, $model)) {
            return;
        }

        $this->logActivity(
            'updated',
            $model,
            oldValues: array_intersect_key($original, $changes),
            newValues: $changes
        );
    }

    public function deleted(Model $model): void
    {
        $this->logActivity('deleted', $model, oldValues: $model->getAttributes());
    }

    public function restored(Model $model): void
    {
        $this->logActivity('restored', $model, newValues: $model->getAttributes());
    }

    public function forceDeleted(Model $model): void
    {
        $this->logActivity('force_deleted', $model, oldValues: $model->getAttributes());
    }

    /**
     * Log an activity event.
     */
    protected function logActivity(
        string $eventType,
        Model $model,
        ?array $oldValues = null,
        ?array $newValues = null
    ): void {
        // Check if logging is enabled via toggle
        if (! ActivityLogging::isEnabled()) {
            return;
        }

        // Only log web user actions
        if (! $this->isWebUserAction()) {
            return;
        }

        $ignoredFields = $this->getIgnoredFields($model);
        $data = $this->prepareLogData($eventType, $model, $oldValues, $newValues, $ignoredFields);

        if (ActivityLogging::isAsync()) {
            LogActivityJob::dispatch($data);
        } else {
            $this->createLogSync($data);
        }
    }

    /**
     * Prepare log data array.
     *
     * @return array{user_id: int|null, event_type: string, target_model: string, target_id: int|string|null, description: string, old_values: array|null, new_values: array|null, user_agent: string|null}
     */
    protected function prepareLogData(
        string $eventType,
        Model $model,
        ?array $oldValues,
        ?array $newValues,
        array $ignoredFields
    ): array {
        return [
            'user_id' => auth()->id(),
            'event_type' => $eventType,
            'target_model' => get_class($model),
            'target_id' => $model->id,
            'description' => $this->generateDescription($eventType, $model, $oldValues, $newValues, $ignoredFields),
            'old_values' => $oldValues ? $this->filterValues($oldValues, $ignoredFields) : null,
            'new_values' => $newValues ? $this->filterValues($newValues, $ignoredFields) : null,
            'user_agent' => request()->userAgent(),
        ];
    }

    /**
     * Create activity log synchronously (for tests or specific cases).
     */
    protected function createLogSync(array $data): ?ActivityLog
    {
        try {
            return ActivityLog::create($data);
        } catch (QueryException $e) {
            Log::error('Failed to create activity log', [
                'error' => $e->getMessage(),
                'sql_code' => $e->getCode(),
                'data' => $data,
            ]);

            return null;
        }
    }

    /**
     * Check if this is a web user action that should be logged.
     */
    protected function isWebUserAction(): bool
    {
        // Allow logging during tests if explicitly enabled
        if (app()->runningInConsole() && ! app()->runningUnitTests()) {
            return false;
        }

        if (! auth()->check()) {
            return false;
        }

        $request = request();

        if (! $request->userAgent()) {
            return false;
        }

        return in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE']);
    }

    /**
     * Check if only ignored fields were updated.
     */
    protected function isOnlyIgnoredFieldsUpdate(array $changes, Model $model): bool
    {
        $ignoredFields = $this->getIgnoredFields($model);

        return empty(array_diff(array_keys($changes), $ignoredFields));
    }

    /**
     * Get ignored fields for a model.
     *
     * @return array<int, string>
     */
    protected function getIgnoredFields(Model $model): array
    {
        if ($model instanceof ActivityLoggable) {
            return $model->getActivityIgnoredFields();
        }

        // Default ignored fields
        return ['updated_at', 'created_at', 'remember_token', 'password'];
    }

    /**
     * Filter out ignored fields from values array.
     *
     * @param  array<string, mixed>  $values
     * @param  array<int, string>  $ignoredFields
     * @return array<string, mixed>
     */
    protected function filterValues(array $values, array $ignoredFields): array
    {
        return array_diff_key($values, array_flip($ignoredFields));
    }

    /**
     * Generate human-readable description for the event.
     */
    protected function generateDescription(
        string $eventType,
        Model $model,
        ?array $oldValues,
        ?array $newValues,
        array $ignoredFields
    ): string {
        $modelName = $model instanceof ActivityLoggable
            ? $model::getActivityModelName()
            : class_basename($model);

        $label = $model instanceof ActivityLoggable
            ? $model->getActivityLabel()
            : ($model->name ?? $model->title ?? $model->id);

        $base = match ($eventType) {
            'created' => "{$modelName} '{$label}' creado",
            'updated' => "{$modelName} '{$label}' actualizado",
            'deleted' => "{$modelName} '{$label}' eliminado",
            'restored' => "{$modelName} '{$label}' restaurado",
            'force_deleted' => "{$modelName} '{$label}' eliminado permanentemente",
            default => "{$modelName} '{$label}' - {$eventType}",
        };

        if ($eventType === 'updated' && $oldValues && $newValues) {
            $changes = $this->formatChanges($oldValues, $newValues, $ignoredFields);
            if ($changes) {
                $base .= " - {$changes}";
            }
        }

        return $base;
    }

    /**
     * Format changes for display in description.
     */
    protected function formatChanges(array $oldValues, array $newValues, array $ignoredFields): string
    {
        $translations = config('activity.field_translations', []);
        $changes = [];

        foreach ($newValues as $field => $newValue) {
            if (in_array($field, $ignoredFields)) {
                continue;
            }

            $oldValue = $oldValues[$field] ?? null;
            $fieldName = $translations[$field] ?? str_replace('_', ' ', $field);

            $oldFormatted = $this->formatValue($oldValue);
            $newFormatted = $this->formatValue($newValue);

            $changes[] = "{$fieldName}: '{$oldFormatted}' -> '{$newFormatted}'";
        }

        // Limit to 3 changes to keep description readable
        return implode(', ', array_slice($changes, 0, 3));
    }

    /**
     * Format a single value for display.
     */
    protected function formatValue(mixed $value): string
    {
        if (is_null($value)) {
            return '(vacio)';
        }

        if (is_bool($value)) {
            return $value ? 'Si' : 'No';
        }

        if (is_array($value)) {
            return json_encode($value);
        }

        $str = (string) $value;

        return strlen($str) > 50 ? substr($str, 0, 47).'...' : $str;
    }
}
