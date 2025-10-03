<?php

namespace App\Observers;

use App\Models\ActivityLog;
use App\Models\CustomerType;

/**
 * Observer para el modelo CustomerType
 * Registra solo las acciones deliberadas del usuario en la interfaz web
 */
class CustomerTypeObserver
{
    /**
     * Handle the CustomerType "created" event.
     */
    public function created(CustomerType $customerType): void
    {
        $this->logActivityEvent('customer_type_created', $customerType, null, $customerType->toArray());
    }

    /**
     * Handle the CustomerType "updated" event.
     */
    public function updated(CustomerType $customerType): void
    {
        $oldValues = $customerType->getOriginal();
        $newValues = $customerType->getChanges();

        // No registrar si solo se actualiza updated_at
        if (count($newValues) === 1 && isset($newValues['updated_at'])) {
            return;
        }

        $this->logActivityEvent('customer_type_updated', $customerType, $oldValues, $newValues);
    }

    /**
     * Handle the CustomerType "deleted" event.
     */
    public function deleted(CustomerType $customerType): void
    {
        $this->logActivityEvent('customer_type_deleted', $customerType, $customerType->toArray(), null);
    }

    /**
     * Handle the CustomerType "restored" event.
     */
    public function restored(CustomerType $customerType): void
    {
        $this->logActivityEvent('customer_type_restored', $customerType, null, $customerType->toArray());
    }

    /**
     * Handle the CustomerType "force deleted" event.
     */
    public function forceDeleted(CustomerType $customerType): void
    {
        $this->logActivityEvent('customer_type_force_deleted', $customerType, $customerType->toArray(), null);
    }

    /**
     * Registra un evento de actividad solo si es una acción deliberada del usuario
     */
    private function logActivityEvent(string $eventType, CustomerType $customerType, ?array $oldValues, ?array $newValues): void
    {
        try {
            // Solo registrar si hay un usuario autenticado Y es una sesión web
            if (! auth()->check() || ! $this->isWebUserAction()) {
                return;
            }

            $user = auth()->user();

            ActivityLog::create([
                'user_id' => $user->id,
                'event_type' => $eventType,
                'target_model' => 'CustomerType',
                'target_id' => $customerType->id,
                'description' => $this->generateDescription($eventType, $customerType, $oldValues, $newValues),
                'old_values' => $oldValues,
                'new_values' => $newValues,
                'user_agent' => request()->userAgent(),
            ]);

            \Log::info("Actividad de tipo de cliente registrada: {$eventType} para tipo '{$customerType->display_name}' por usuario {$user->email}");
        } catch (\Exception $e) {
            \Log::error('Error al registrar actividad de tipo de cliente: '.$e->getMessage());
        }
    }

    /**
     * Verifica si es una acción del usuario en la web (no automatizada)
     */
    private function isWebUserAction(): bool
    {
        $request = request();

        // Verificar que hay una request HTTP
        if (! $request) {
            return false;
        }

        // Verificar que tiene user agent (navegador web)
        if (! $request->userAgent()) {
            return false;
        }

        // Verificar que no es una tarea automática (artisan, queue, etc.)
        if (app()->runningInConsole() && ! app()->runningUnitTests()) {
            return false;
        }

        // Verificar que es una petición POST/PUT/DELETE (acciones de cambio)
        if (! in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            return false;
        }

        return true;
    }

    /**
     * Genera descripción detallada del evento
     */
    private function generateDescription(string $eventType, CustomerType $customerType, ?array $oldValues, ?array $newValues): string
    {
        $typeName = $customerType->display_name ?? $customerType->name ?? 'Tipo sin nombre';

        switch ($eventType) {
            case 'customer_type_created':
                $pointsText = $customerType->points_required ? " (requiere {$customerType->points_required} puntos)" : '';

                return "Tipo de cliente '{$typeName}'{$pointsText} fue creado";

            case 'customer_type_updated':
                $changes = [];
                if ($newValues) {
                    foreach ($newValues as $field => $newValue) {
                        if ($field === 'updated_at') {
                            continue;
                        }

                        $oldValue = $oldValues[$field] ?? null;

                        $fieldNames = [
                            'name' => 'nombre',
                            'display_name' => 'nombre mostrado',
                            'points_required' => 'puntos requeridos',
                            'multiplier' => 'multiplicador',
                            'color' => 'color',
                            'is_active' => 'estado',
                            'sort_order' => 'orden',
                        ];

                        $fieldName = $fieldNames[$field] ?? $field;

                        if ($field === 'is_active') {
                            $oldValue = $oldValue ? 'Activo' : 'Inactivo';
                            $newValue = $newValue ? 'Activo' : 'Inactivo';
                        }

                        $changes[] = "{$fieldName}: '{$oldValue}' → '{$newValue}'";
                    }
                }

                $changesText = ! empty($changes) ? ' - '.implode(', ', $changes) : '';

                return "Tipo de cliente '{$typeName}' fue actualizado{$changesText}";

            case 'customer_type_deleted':
                return "Tipo de cliente '{$typeName}' fue eliminado";

            case 'customer_type_restored':
                return "Tipo de cliente '{$typeName}' fue restaurado";

            case 'customer_type_force_deleted':
                return "Tipo de cliente '{$typeName}' fue eliminado permanentemente";

            default:
                return "Evento {$eventType} en tipo de cliente '{$typeName}'";
        }
    }
}
