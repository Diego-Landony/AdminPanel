<?php

namespace App\Observers;

use App\Models\ActivityLog;
use App\Models\Customer;

/**
 * Observer para el modelo Customer
 * Registra solo las acciones deliberadas del usuario en la interfaz web
 */
class CustomerObserver
{
    /**
     * Handle the Customer "created" event.
     */
    public function created(Customer $customer): void
    {
        $this->logActivityEvent('customer_created', $customer, null, $customer->toArray());
    }

    /**
     * Handle the Customer "updated" event.
     */
    public function updated(Customer $customer): void
    {
        $oldValues = $customer->getOriginal();
        $newValues = $customer->getChanges();

        // No registrar si solo se actualiza last_activity_at o last_login_at (son actualizaciones automáticas)
        if ($this->isOnlyTimestampUpdate($newValues)) {
            return;
        }

        $this->logActivityEvent('customer_updated', $customer, $oldValues, $newValues);
    }

    /**
     * Handle the Customer "deleted" event.
     */
    public function deleted(Customer $customer): void
    {
        $this->logActivityEvent('customer_deleted', $customer, $customer->toArray(), null);
    }

    /**
     * Handle the Customer "restored" event.
     */
    public function restored(Customer $customer): void
    {
        $this->logActivityEvent('customer_restored', $customer, null, $customer->toArray());
    }

    /**
     * Handle the Customer "force deleted" event.
     */
    public function forceDeleted(Customer $customer): void
    {
        $this->logActivityEvent('customer_force_deleted', $customer, $customer->toArray(), null);
    }

    /**
     * Registra un evento de actividad solo si es una acción deliberada del usuario
     */
    private function logActivityEvent(string $eventType, Customer $customer, ?array $oldValues, ?array $newValues): void
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
                'target_model' => 'Customer',
                'target_id' => $customer->id,
                'description' => $this->generateDescription($eventType, $customer, $oldValues, $newValues),
                'old_values' => $oldValues,
                'new_values' => $newValues,
                'user_agent' => request()->userAgent(),
            ]);

            \Log::info("Actividad de cliente registrada: {$eventType} para cliente '{$customer->full_name}' por usuario {$user->email}");
        } catch (\Exception $e) {
            \Log::error('Error al registrar actividad de cliente: '.$e->getMessage());
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
    private function generateDescription(string $eventType, Customer $customer, ?array $oldValues, ?array $newValues): string
    {
        $customerName = $customer->full_name ?? 'Cliente sin nombre';
        $customerEmail = $customer->email ?? 'Sin email';

        switch ($eventType) {
            case 'customer_created':
                return "Cliente '{$customerName}' ({$customerEmail}) fue creado";

            case 'customer_updated':
                $changes = [];
                if ($newValues) {
                    foreach ($newValues as $field => $newValue) {
                        if (in_array($field, ['updated_at', 'last_activity_at', 'last_login_at'])) {
                            continue;
                        }

                        $oldValue = $oldValues[$field] ?? null;

                        $fieldNames = [
                            'full_name' => 'nombre completo',
                            'email' => 'email',
                            'subway_card' => 'tarjeta subway',
                            'phone' => 'teléfono',
                            'address' => 'dirección',
                            'puntos' => 'puntos',
                            'customer_type_id' => 'tipo de cliente',
                            'client_type' => 'tipo de cliente',
                        ];

                        $fieldName = $fieldNames[$field] ?? $field;
                        $changes[] = "{$fieldName}: '{$oldValue}' → '{$newValue}'";
                    }
                }

                $changesText = ! empty($changes) ? ' - '.implode(', ', $changes) : '';

                return "Cliente '{$customerName}' ({$customerEmail}) fue actualizado{$changesText}";

            case 'customer_deleted':
                return "Cliente '{$customerName}' ({$customerEmail}) fue eliminado";

            case 'customer_restored':
                return "Cliente '{$customerName}' ({$customerEmail}) fue restaurado";

            case 'customer_force_deleted':
                return "Cliente '{$customerName}' ({$customerEmail}) fue eliminado permanentemente";

            default:
                return "Evento {$eventType} en cliente '{$customerName}'";
        }
    }

    /**
     * Verifica si solo se actualizaron timestamps automáticos
     */
    private function isOnlyTimestampUpdate(array $changes): bool
    {
        $timestampFields = ['updated_at', 'last_activity_at', 'last_login_at', 'puntos_updated_at'];

        foreach ($changes as $field => $value) {
            if (! in_array($field, $timestampFields)) {
                return false; // Hay cambios en otros campos
            }
        }

        return true; // Solo cambios en timestamps
    }
}
