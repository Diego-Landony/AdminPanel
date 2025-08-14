<?php

namespace App\Observers;

use App\Models\Role;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;

/**
 * Observer para el modelo Role
 * Registra automáticamente todas las operaciones de roles para auditoría
 */
class RoleObserver
{
    /**
     * Handle the Role "created" event.
     */
    public function created(Role $role): void
    {
        $this->logAuditEvent('role_created', $role, null, $role->toArray());
    }

    /**
     * Handle the Role "updated" event.
     */
    public function updated(Role $role): void
    {
        // Solo registrar si hay cambios reales (no timestamps automáticos)
        $changes = $role->getDirty();
        $ignoredFields = ['updated_at'];
        $significantChanges = array_diff_key($changes, array_flip($ignoredFields));
        
        if (!empty($significantChanges)) {
            $oldValues = $role->getOriginal();
            $newValues = $role->toArray();
            
            $this->logAuditEvent('role_updated', $role, $oldValues, $newValues);
        }
    }

    /**
     * Handle the Role "deleted" event.
     */
    public function deleted(Role $role): void
    {
        $this->logAuditEvent('role_deleted', $role, $role->toArray(), null);
    }

    /**
     * Handle the Role "restored" event.
     */
    public function restored(Role $role): void
    {
        $this->logAuditEvent('role_restored', $role, null, $role->toArray());
    }

    /**
     * Handle the Role "force deleted" event.
     */
    public function forceDeleted(Role $role): void
    {
        $this->logAuditEvent('role_force_deleted', $role, $role->toArray(), null);
    }

    /**
     * Registra un evento de auditoría
     */
    private function logAuditEvent(string $eventType, Role $role, ?array $oldValues, ?array $newValues): void
    {
        try {
            AuditLog::create([
                'user_id' => Auth::id(), // Usuario que realizó la acción
                'event_type' => $eventType,
                'target_model' => 'Role',
                'target_id' => $role->id,
                'description' => $this->getEventDescription($eventType, $role),
                'old_values' => $oldValues,
                'new_values' => $newValues,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        } catch (\Exception $e) {
            // Log del error pero no fallar la operación principal
            \Log::error('Error al registrar auditoría de rol: ' . $e->getMessage());
        }
    }

    /**
     * Genera descripción legible del evento
     */
    private function getEventDescription(string $eventType, Role $role): string
    {
        return match ($eventType) {
            'role_created' => "Rol '{$role->name}' fue creado",
            'role_updated' => "Rol '{$role->name}' fue actualizado",
            'role_deleted' => "Rol '{$role->name}' fue eliminado",
            'role_restored' => "Rol '{$role->name}' fue restaurado",
            'role_force_deleted' => "Rol '{$role->name}' fue eliminado permanentemente",
            default => "Evento {$eventType} en rol '{$role->name}'",
        };
    }
}
