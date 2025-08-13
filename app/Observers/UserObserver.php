<?php

namespace App\Observers;

use App\Models\User;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;

/**
 * Observer para el modelo User
 * Registra automáticamente todos los cambios en usuarios para auditoría
 */
class UserObserver
{
    /**
     * Handle the User "created" event.
     */
    public function created(User $user): void
    {
        $this->logAuditEvent('user_created', $user, null, $user->toArray());
    }

    /**
     * Handle the User "updated" event.
     */
    public function updated(User $user): void
    {
        // Solo registrar si hay cambios reales (no timestamps automáticos)
        $changes = $user->getDirty();
        $ignoredFields = ['updated_at', 'last_activity_at'];
        $significantChanges = array_diff_key($changes, array_flip($ignoredFields));
        
        if (!empty($significantChanges)) {
            $oldValues = $user->getOriginal();
            $newValues = $user->toArray();
            
            $this->logAuditEvent('user_updated', $user, $oldValues, $newValues);
        }
    }

    /**
     * Handle the User "deleted" event.
     */
    public function deleted(User $user): void
    {
        $this->logAuditEvent('user_deleted', $user, $user->toArray(), null);
    }

    /**
     * Handle the User "restored" event.
     */
    public function restored(User $user): void
    {
        $this->logAuditEvent('user_restored', $user, null, $user->toArray());
    }

    /**
     * Handle the User "force deleted" event.
     */
    public function forceDeleted(User $user): void
    {
        $this->logAuditEvent('user_force_deleted', $user, $user->toArray(), null);
    }

    /**
     * Registra un evento de auditoría
     */
    private function logAuditEvent(string $eventType, User $user, ?array $oldValues, ?array $newValues): void
    {
        try {
            AuditLog::create([
                'user_id' => Auth::id(), // Usuario que realizó la acción
                'event_type' => $eventType,
                'target_model' => 'User',
                'target_id' => $user->id,
                'description' => $this->getEventDescription($eventType, $user),
                'old_values' => $oldValues,
                'new_values' => $newValues,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        } catch (\Exception $e) {
            // Log del error pero no fallar la operación principal
            \Log::error('Error al registrar auditoría: ' . $e->getMessage());
        }
    }

    /**
     * Genera descripción legible del evento
     */
    private function getEventDescription(string $eventType, User $user): string
    {
        return match ($eventType) {
            'user_created' => "Usuario '{$user->name}' ({$user->email}) fue creado",
            'user_updated' => "Usuario '{$user->name}' ({$user->email}) fue actualizado",
            'user_deleted' => "Usuario '{$user->name}' ({$user->email}) fue eliminado",
            'user_restored' => "Usuario '{$user->name}' ({$user->email}) fue restaurado",
            'user_force_deleted' => "Usuario '{$user->name}' ({$user->email}) fue eliminado permanentemente",
            default => "Evento {$eventType} en usuario '{$user->name}'",
        };
    }
}
