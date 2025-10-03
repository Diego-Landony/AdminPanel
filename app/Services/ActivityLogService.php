<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * Servicio centralizado para el manejo de Activity Logs
 * Proporciona métodos consistentes para registrar actividades del sistema
 */
class ActivityLogService
{
    /**
     * Registra la creación de un modelo
     *
     * @param  mixed  $model
     */
    public function logCreated($model, ?string $description = null): ?ActivityLog
    {
        $modelName = class_basename($model);
        $defaultDescription = "{$modelName} creado";

        return $this->createLog(
            event_type: 'created',
            target_model: get_class($model),
            target_id: $model->id,
            description: $description ?? $defaultDescription,
            new_values: $model->getAttributes()
        );
    }

    /**
     * Registra la actualización de un modelo
     *
     * @param  mixed  $model
     */
    public function logUpdated(
        $model,
        array $oldValues,
        array $newValues,
        ?string $description = null
    ): ?ActivityLog {
        $modelName = class_basename($model);

        // Construir descripción con cambios detectados
        if (! $description) {
            $changes = $this->detectChanges($oldValues, $newValues);
            $description = $changes
                ? "{$modelName} actualizado - ".implode(', ', $changes)
                : "{$modelName} actualizado";
        }

        return $this->createLog(
            event_type: 'updated',
            target_model: get_class($model),
            target_id: $model->id,
            description: $description,
            old_values: $oldValues,
            new_values: $newValues
        );
    }

    /**
     * Registra la eliminación de un modelo
     *
     * @param  mixed  $model
     */
    public function logDeleted($model, ?string $description = null): ?ActivityLog
    {
        $modelName = class_basename($model);
        $defaultDescription = "{$modelName} eliminado";

        return $this->createLog(
            event_type: 'deleted',
            target_model: get_class($model),
            target_id: $model->id,
            description: $description ?? $defaultDescription,
            old_values: $model->getAttributes()
        );
    }

    /**
     * Registra la actualización de usuarios de un rol
     *
     * @param  \App\Models\Role  $role
     */
    public function logRoleUsersUpdate($role, array $oldUserIds, array $newUserIds): ?ActivityLog
    {
        $addedUsers = array_diff($newUserIds, $oldUserIds);
        $removedUsers = array_diff($oldUserIds, $newUserIds);

        $description = "Usuarios actualizados para el rol '{$role->name}'";

        if (! empty($addedUsers)) {
            $addedUserNames = User::whereIn('id', $addedUsers)->pluck('name')->join(', ');
            $description .= " - Agregados: {$addedUserNames}";
        }

        if (! empty($removedUsers)) {
            $removedUserNames = User::whereIn('id', $removedUsers)->pluck('name')->join(', ');
            $description .= " - Removidos: {$removedUserNames}";
        }

        return $this->createLog(
            event_type: 'role_users_updated',
            target_model: 'App\\Models\\Role',
            target_id: $role->id,
            description: $description,
            old_values: ['user_ids' => $oldUserIds],
            new_values: ['user_ids' => $newUserIds]
        );
    }

    /**
     * Registra un evento personalizado
     */
    public function logCustomEvent(
        string $eventType,
        string $targetModel,
        ?int $targetId,
        string $description,
        array $oldValues = [],
        array $newValues = []
    ): ?ActivityLog {
        return $this->createLog(
            event_type: $eventType,
            target_model: $targetModel,
            target_id: $targetId,
            description: $description,
            old_values: $oldValues,
            new_values: $newValues
        );
    }

    /**
     * Crea un registro de actividad en la base de datos
     */
    protected function createLog(
        string $event_type,
        string $target_model,
        ?int $target_id,
        string $description,
        array $old_values = [],
        array $new_values = []
    ): ?ActivityLog {
        try {
            return ActivityLog::create([
                'user_id' => auth()->id(),
                'event_type' => $event_type,
                'target_model' => $target_model,
                'target_id' => $target_id,
                'description' => $description,
                'old_values' => $old_values,
                'new_values' => $new_values,
                'user_agent' => request()->userAgent(),
            ]);
        } catch (\Exception $e) {
            Log::error('Error al crear activity log: '.$e->getMessage(), [
                'event_type' => $event_type,
                'target_model' => $target_model,
                'target_id' => $target_id,
            ]);

            return null;
        }
    }

    /**
     * Detecta cambios entre valores antiguos y nuevos
     */
    protected function detectChanges(array $oldValues, array $newValues): array
    {
        $changes = [];
        $ignoredFields = ['updated_at', 'password', 'remember_token'];

        foreach ($newValues as $key => $newValue) {
            // Ignorar campos sensibles o automáticos
            if (in_array($key, $ignoredFields)) {
                continue;
            }

            $oldValue = $oldValues[$key] ?? null;

            // Detectar cambio
            if ($oldValue != $newValue) {
                $changes[] = $this->formatChange($key, $oldValue, $newValue);
            }
        }

        return $changes;
    }

    /**
     * Formatea un cambio de forma legible
     *
     * @param  mixed  $oldValue
     * @param  mixed  $newValue
     */
    protected function formatChange(string $field, $oldValue, $newValue): string
    {
        $fieldLabel = str_replace('_', ' ', $field);

        // Valores booleanos
        if (is_bool($oldValue) || is_bool($newValue)) {
            $oldValue = $oldValue ? 'Sí' : 'No';
            $newValue = $newValue ? 'Sí' : 'No';
        }

        // Valores null
        $oldValue = $oldValue ?? '(vacío)';
        $newValue = $newValue ?? '(vacío)';

        // Truncar valores largos
        if (is_string($oldValue) && strlen($oldValue) > 50) {
            $oldValue = substr($oldValue, 0, 47).'...';
        }
        if (is_string($newValue) && strlen($newValue) > 50) {
            $newValue = substr($newValue, 0, 47).'...';
        }

        return "{$fieldLabel}: '{$oldValue}' → '{$newValue}'";
    }

    /**
     * Obtiene el log de actividades de un modelo específico
     *
     * @param  mixed  $model
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getModelActivityLog($model, int $limit = 50)
    {
        return ActivityLog::where('target_model', get_class($model))
            ->where('target_id', $model->id)
            ->with('user:id,name,email')
            ->latest()
            ->limit($limit)
            ->get();
    }

    /**
     * Obtiene el log de actividades de un usuario específico
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getUserActivityLog(int $userId, int $limit = 50)
    {
        return ActivityLog::where('user_id', $userId)
            ->with('user:id,name,email')
            ->latest()
            ->limit($limit)
            ->get();
    }
}
