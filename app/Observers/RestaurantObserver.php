<?php

namespace App\Observers;

use App\Models\ActivityLog;
use App\Models\Restaurant;

class RestaurantObserver
{
    /**
     * Handle the Restaurant "created" event.
     */
    public function created(Restaurant $restaurant): void
    {
        $this->logActivityEvent('restaurant_created', $restaurant, null, $restaurant->toArray());
    }

    /**
     * Handle the Restaurant "updated" event.
     */
    public function updated(Restaurant $restaurant): void
    {
        $oldValues = $restaurant->getOriginal();
        $newValues = $restaurant->getChanges();

        if ($this->isOnlyTimestampUpdate($newValues)) {
            return;
        }

        $this->logActivityEvent('restaurant_updated', $restaurant, $oldValues, $newValues);
    }

    /**
     * Handle the Restaurant "deleted" event.
     */
    public function deleted(Restaurant $restaurant): void
    {
        $this->logActivityEvent('restaurant_deleted', $restaurant, $restaurant->toArray(), null);
    }

    /**
     * Handle the Restaurant "restored" event.
     */
    public function restored(Restaurant $restaurant): void
    {
        $this->logActivityEvent('restaurant_restored', $restaurant, null, $restaurant->toArray());
    }

    /**
     * Handle the Restaurant "force deleted" event.
     */
    public function forceDeleted(Restaurant $restaurant): void
    {
        $this->logActivityEvent('restaurant_force_deleted', $restaurant, $restaurant->toArray(), null);
    }

    private function logActivityEvent(string $eventType, Restaurant $restaurant, ?array $oldValues, ?array $newValues): void
    {
        try {
            if (! auth()->check() || ! $this->isWebUserAction()) {
                return;
            }

            $user = auth()->user();

            ActivityLog::create([
                'user_id' => $user->id,
                'event_type' => $eventType,
                'target_model' => 'Restaurant',
                'target_id' => $restaurant->id,
                'description' => $this->generateDescription($eventType, $restaurant, $oldValues, $newValues),
                'old_values' => $oldValues,
                'new_values' => $newValues,
                'user_agent' => request()->userAgent(),
            ]);

            \Log::info("Actividad de restaurante registrada: {$eventType} para restaurante '{$restaurant->name}' por usuario {$user->email}");
        } catch (\Exception $e) {
            \Log::error('Error al registrar actividad de restaurante: '.$e->getMessage());
        }
    }

    private function isWebUserAction(): bool
    {
        $request = request();

        if (! $request) {
            return false;
        }
        if (! $request->userAgent()) {
            return false;
        }
        if (app()->runningInConsole() && ! app()->runningUnitTests()) {
            return false;
        }
        if (! in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            return false;
        }

        return true;
    }

    private function generateDescription(string $eventType, Restaurant $restaurant, ?array $oldValues, ?array $newValues): string
    {
        $restaurantName = $restaurant->name ?? 'Restaurante sin nombre';
        $restaurantAddress = $restaurant->address ?? 'Sin dirección';

        switch ($eventType) {
            case 'restaurant_created':
                return "Restaurante '{$restaurantName}' ({$restaurantAddress}) fue creado";

            case 'restaurant_updated':
                $changes = [];
                if ($newValues) {
                    foreach ($newValues as $field => $newValue) {
                        if (in_array($field, ['updated_at'])) {
                            continue;
                        }

                        $oldValue = $oldValues[$field] ?? null;

                        $fieldNames = [
                            'name' => 'nombre',
                            'description' => 'descripción',
                            'address' => 'dirección',
                            'is_active' => 'estado activo',
                            'delivery_active' => 'delivery activo',
                            'pickup_active' => 'pickup activo',
                            'phone' => 'teléfono',
                            'email' => 'email',
                            'manager_name' => 'encargado',
                            'minimum_order_amount' => 'monto mínimo',
                            'delivery_fee' => 'tarifa delivery',
                            'estimated_delivery_time' => 'tiempo estimado',
                            'sort_order' => 'orden',
                            'schedule' => 'horario',
                        ];

                        $fieldName = $fieldNames[$field] ?? $field;

                        if ($field === 'is_active') {
                            $oldValue = $oldValue ? 'Activo' : 'Inactivo';
                            $newValue = $newValue ? 'Activo' : 'Inactivo';
                        } elseif (in_array($field, ['delivery_active', 'pickup_active'])) {
                            $oldValue = $oldValue ? 'Activo' : 'Inactivo';
                            $newValue = $newValue ? 'Activo' : 'Inactivo';
                        }

                        $changes[] = "{$fieldName}: '{$oldValue}' → '{$newValue}'";
                    }
                }

                $changesText = ! empty($changes) ? ' - '.implode(', ', $changes) : '';

                return "Restaurante '{$restaurantName}' ({$restaurantAddress}) fue actualizado{$changesText}";

            case 'restaurant_deleted':
                return "Restaurante '{$restaurantName}' ({$restaurantAddress}) fue eliminado";

            case 'restaurant_restored':
                return "Restaurante '{$restaurantName}' ({$restaurantAddress}) fue restaurado";

            case 'restaurant_force_deleted':
                return "Restaurante '{$restaurantName}' ({$restaurantAddress}) fue eliminado permanentemente";

            default:
                return "Evento {$eventType} en restaurante '{$restaurantName}'";
        }
    }

    private function isOnlyTimestampUpdate(array $changes): bool
    {
        $timestampFields = ['updated_at'];

        foreach ($changes as $field => $value) {
            if (! in_array($field, $timestampFields)) {
                return false;
            }
        }

        return true;
    }
}
