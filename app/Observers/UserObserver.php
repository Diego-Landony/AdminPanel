<?php

namespace App\Observers;

use App\Models\ActivityLog;
use App\Models\User;

/**
 * Observer para el modelo User
 * Registra automáticamente todos los cambios en usuarios
 */
class UserObserver
{
    /**
     * Handle the User "created" event.
     */
    public function created(User $user): void
    {
        $this->logActivityEvent('user_created', $user, null, $user->toArray());
    }

    /**
     * Handle the User "updated" event.
     */
    public function updated(User $user): void
    {
        $oldValues = $user->getOriginal();
        $newValues = $user->getChanges();

        $this->logActivityEvent('user_updated', $user, $oldValues, $newValues);
    }

    /**
     * Handle the User "deleted" event.
     */
    public function deleted(User $user): void
    {
        $this->logActivityEvent('user_deleted', $user, $user->toArray(), null);
    }

    /**
     * Handle the User "restored" event.
     */
    public function restored(User $user): void
    {
        $this->logActivityEvent('user_restored', $user, null, $user->toArray());
    }

    /**
     * Handle the User "force deleted" event.
     */
    public function forceDeleted(User $user): void
    {
        $this->logActivityEvent('user_force_deleted', $user, $user->toArray(), null);
    }

    /**
     * Registra un evento de actividad
     */
    private function logActivityEvent(string $eventType, User $user, ?array $oldValues, ?array $newValues): void
    {
        try {
            // Solo registrar si hay un usuario autenticado
            if (! auth()->check()) {
                \Log::warning('No se pudo registrar actividad de usuario: Usuario no autenticado');

                return;
            }

            $currentUser = auth()->user();

            ActivityLog::create([
                'user_id' => $currentUser->id,
                'event_type' => $eventType,
                'target_model' => 'User',
                'target_id' => $user->id,
                'description' => "Usuario '{$user->name}' ({$user->email}) fue ".$this->getEventDescription($eventType, $user),
                'old_values' => $oldValues,
                'new_values' => $newValues,
                'user_agent' => request()->userAgent(),
            ]);

            \Log::info("Actividad de usuario registrada: {$eventType} para usuario '{$user->name}' por usuario {$currentUser->email}");
        } catch (\Exception $e) {
            \Log::error('Error al registrar actividad: '.$e->getMessage());
            \Log::error('Stack trace: '.$e->getTraceAsString());
        }
    }

    /**
     * Genera descripción legible del evento
     */
    private function getEventDescription(string $eventType, User $user): string
    {
        return match ($eventType) {
            'user_created' => 'creado',
            'user_updated' => 'actualizado',
            'user_deleted' => 'eliminado',
            'user_restored' => 'restaurado',
            'user_force_deleted' => 'eliminado permanentemente',
            default => "Evento {$eventType} en usuario '{$user->name}'",
        };
    }
}
