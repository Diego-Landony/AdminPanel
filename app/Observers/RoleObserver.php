<?php

namespace App\Observers;

use App\Models\ActivityLog;
use App\Models\Role;

/**
 * Observer para el modelo Role
 * Registra automáticamente todas las operaciones de roles
 */
class RoleObserver
{
    /**
     * Handle the Role "created" event.
     */
    public function created(Role $role): void
    {
        $this->logActivityEvent('role_created', $role, null, $role->toArray());
    }

    /**
     * Handle the Role "updated" event.
     */
    public function updated(Role $role): void
    {
        $oldValues = $role->getOriginal();
        $newValues = $role->getChanges();

        $this->logActivityEvent('role_updated', $role, $oldValues, $newValues);
    }

    /**
     * Handle the Role "deleted" event.
     */
    public function deleted(Role $role): void
    {
        $this->logActivityEvent('role_deleted', $role, $role->toArray(), null);
    }

    /**
     * Handle the Role "restored" event.
     */
    public function restored(Role $role): void
    {
        $this->logActivityEvent('role_restored', $role, null, $role->toArray());
    }

    /**
     * Handle the Role "force deleted" event.
     */
    public function forceDeleted(Role $role): void
    {
        $this->logActivityEvent('role_force_deleted', $role, $role->toArray(), null);
    }

    /**
     * Registra un evento de actividad
     */
    private function logActivityEvent(string $eventType, Role $role, ?array $oldValues, ?array $newValues): void
    {
        try {
            // Solo registrar si hay un usuario autenticado
            if (! auth()->check()) {
                \Log::warning('No se pudo registrar actividad de rol: Usuario no autenticado');

                return;
            }

            $user = auth()->user();

            ActivityLog::create([
                'user_id' => $user->id,
                'event_type' => $eventType,
                'target_model' => 'Role',
                'target_id' => $role->id,
                'description' => "Rol '{$role->name}' fue ".$this->getEventDescription($eventType, $role),
                'old_values' => $oldValues,
                'new_values' => $newValues,
                'user_agent' => request()->userAgent(),
            ]);

            \Log::info("Actividad de rol registrada: {$eventType} para rol '{$role->name}' por usuario {$user->email}");
        } catch (\Exception $e) {
            \Log::error('Error al registrar actividad de rol: '.$e->getMessage());
            \Log::error('Stack trace: '.$e->getTraceAsString());
        }
    }

    /**
     * Genera descripción legible del evento
     */
    private function getEventDescription(string $eventType, Role $role): string
    {
        return match ($eventType) {
            'role_created' => 'creado',
            'role_updated' => 'actualizado',
            'role_deleted' => 'eliminado',
            'role_restored' => 'restaurado',
            'role_force_deleted' => 'eliminado permanentemente',
            default => "Evento {$eventType} en rol '{$role->name}'",
        };
    }
}
