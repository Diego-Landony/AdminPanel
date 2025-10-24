<?php

namespace App\Observers;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

/**
 * Observer genérico para registrar actividades de cualquier modelo
 * Se registra automáticamente via el trait LogsActivity
 */
class ActivityObserver
{
    public function created(Model $model): void
    {
        $this->logActivity('created', $model, null, $model->toArray());
    }

    public function updated(Model $model): void
    {
        $oldValues = $model->getOriginal();
        $newValues = $model->getChanges();

        // Ignorar si solo se actualizaron timestamps
        if ($this->isOnlyTimestampUpdate($newValues, $model)) {
            return;
        }

        $this->logActivity('updated', $model, $oldValues, $newValues);
    }

    public function deleted(Model $model): void
    {
        $this->logActivity('deleted', $model, $model->toArray(), null);
    }

    public function restored(Model $model): void
    {
        $this->logActivity('restored', $model, null, $model->toArray());
    }

    public function forceDeleted(Model $model): void
    {
        $this->logActivity('force_deleted', $model, $model->toArray(), null);
    }

    /**
     * Registra un evento de actividad
     */
    private function logActivity(string $eventType, Model $model, ?array $oldValues, ?array $newValues): void
    {
        try {
            // Solo registrar si es una acción web de usuario autenticado
            if (! $this->isWebUserAction()) {
                return;
            }

            $user = auth()->user();
            $config = $this->getModelConfig($model);

            ActivityLog::create([
                'user_id' => $user->id,
                'event_type' => $eventType,
                'target_model' => get_class($model),
                'target_id' => $model->id,
                'description' => $this->generateDescription($eventType, $model, $config, $oldValues, $newValues),
                'old_values' => $oldValues,
                'new_values' => $newValues,
                'user_agent' => request()->userAgent(),
            ]);

            Log::info("Activity logged: {$eventType} for {$config['model_name']} '{$model->getActivityLabel()}' by {$user->email}");
        } catch (\Exception $e) {
            Log::error('Error logging activity: '.$e->getMessage(), [
                'event_type' => $eventType,
                'model' => get_class($model),
                'model_id' => $model->id ?? null,
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Verifica si es una acción de usuario en la web (no automatizada)
     */
    private function isWebUserAction(): bool
    {
        // Verificar usuario autenticado
        if (! auth()->check()) {
            return false;
        }

        $request = request();

        // Verificar request HTTP
        if (! $request) {
            return false;
        }

        // Verificar user agent (navegador web)
        if (! $request->userAgent()) {
            return false;
        }

        // Verificar que no es comando artisan
        if (app()->runningInConsole()) {
            return false;
        }

        // Verificar que es petición de cambio
        if (! in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            return false;
        }

        return true;
    }

    /**
     * Verifica si solo se actualizaron timestamps
     */
    private function isOnlyTimestampUpdate(array $changes, Model $model): bool
    {
        $config = $this->getModelConfig($model);
        $ignoredFields = $config['ignored_fields'];

        foreach ($changes as $field => $value) {
            if (! in_array($field, $ignoredFields)) {
                return false; // Hay cambios en otros campos
            }
        }

        return true; // Solo cambios en campos ignorados
    }

    /**
     * Obtiene la configuración del modelo
     */
    private function getModelConfig(Model $model): array
    {
        if (method_exists($model, 'getActivityConfig')) {
            return $model->getActivityConfig();
        }

        // Configuración por defecto
        return [
            'label_field' => 'name',
            'model_name' => class_basename($model),
            'ignored_fields' => ['updated_at'],
        ];
    }

    /**
     * Genera descripción detallada del evento
     */
    private function generateDescription(
        string $eventType,
        Model $model,
        array $config,
        ?array $oldValues,
        ?array $newValues
    ): string {
        $modelName = config('activity.models.'.get_class($model).'.name', $config['model_name']);
        $label = $model->getActivityLabel();

        $action = match ($eventType) {
            'created' => 'creado',
            'updated' => 'actualizado',
            'deleted' => 'eliminado',
            'restored' => 'restaurado',
            'force_deleted' => 'eliminado permanentemente',
            default => $eventType,
        };

        $description = "{$modelName} '{$label}' {$action}";

        // Agregar detalles de cambios para updates
        if ($eventType === 'updated' && $newValues) {
            $changes = $this->formatChanges($newValues, $oldValues ?? [], $config['ignored_fields']);
            if (! empty($changes)) {
                $description .= ' - '.implode(', ', $changes);
            }
        }

        return $description;
    }

    /**
     * Formatea los cambios de manera legible
     */
    private function formatChanges(array $newValues, array $oldValues, array $ignoredFields): array
    {
        $changes = [];
        $fieldTranslations = config('activity.field_translations', []);

        foreach ($newValues as $field => $newValue) {
            if (in_array($field, $ignoredFields)) {
                continue;
            }

            $oldValue = $oldValues[$field] ?? null;

            // Traducir nombre de campo
            $fieldName = $fieldTranslations[$field] ?? str_replace('_', ' ', $field);

            // Formatear valores booleanos
            if (is_bool($oldValue) || is_bool($newValue)) {
                $oldValue = $oldValue ? 'Sí' : 'No';
                $newValue = $newValue ? 'Sí' : 'No';
            }

            // Formatear valores null
            $oldValue = $oldValue ?? '(vacío)';
            $newValue = $newValue ?? '(vacío)';

            // Truncar valores largos
            if (is_string($oldValue) && strlen($oldValue) > 50) {
                $oldValue = substr($oldValue, 0, 47).'...';
            }
            if (is_string($newValue) && strlen($newValue) > 50) {
                $newValue = substr($newValue, 0, 47).'...';
            }

            $changes[] = "{$fieldName}: '{$oldValue}' → '{$newValue}'";
        }

        return $changes;
    }
}
