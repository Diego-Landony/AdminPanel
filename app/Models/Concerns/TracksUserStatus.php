<?php

namespace App\Models\Concerns;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

/**
 * Trait para modelos que rastrean estado de conexión del usuario
 * Usado en User y Customer para determinar si están online, offline, etc.
 */
trait TracksUserStatus
{
    /**
     * Determina si el usuario/cliente está en línea basado en su última actividad
     * En línea: Última actividad dentro de los últimos 5 minutos
     */
    public function isOnline(): bool
    {
        if (! $this->last_activity_at) {
            return false;
        }

        $lastActivity = Carbon::parse($this->last_activity_at)->utc();
        $now = Carbon::now()->utc();

        return $lastActivity->diffInMinutes($now) < 5;
    }

    /**
     * Accessor para el atributo is_online
     * Permite acceder a $model->is_online
     */
    public function getIsOnlineAttribute(): bool
    {
        return $this->isOnline();
    }

    /**
     * Obtiene el estado del usuario basado en su última actividad
     * Estados posibles:
     * - 'never': Sin registro de actividad
     * - 'online': Última actividad < 5 minutos
     * - 'recent': Última actividad entre 5-15 minutos
     * - 'offline': Última actividad > 15 minutos
     */
    public function getStatusAttribute(): string
    {
        if (! $this->last_activity_at) {
            return 'never';
        }

        $lastActivity = Carbon::parse($this->last_activity_at)->utc();
        $now = Carbon::now()->utc();
        $minutes = $lastActivity->diffInMinutes($now);

        return match (true) {
            $minutes < 5 => 'online',
            $minutes < 15 => 'recent',
            default => 'offline'
        };
    }

    /**
     * Actualiza el timestamp de la última actividad del usuario
     * Usa saveQuietly() para evitar disparar eventos
     */
    public function updateLastActivity(): void
    {
        $this->last_activity_at = now();
        $this->saveQuietly();
    }

    /**
     * Actualiza el timestamp del último acceso/login del usuario
     */
    public function updateLastLogin(): void
    {
        $this->update(['last_login_at' => now()]);
    }

    /**
     * Scope para filtrar usuarios/clientes que están en línea
     * En línea = última actividad en los últimos 5 minutos
     */
    public function scopeOnline(Builder $query): Builder
    {
        return $query->where('last_activity_at', '>=', now()->subMinutes(5));
    }

    /**
     * Scope para filtrar usuarios/clientes por estado específico
     *
     * @param  string  $status  - 'never', 'online', 'recent', 'offline'
     */
    public function scopeWithStatus(Builder $query, string $status): Builder
    {
        return match ($status) {
            'never' => $query->whereNull('last_activity_at'),
            'online' => $query->where('last_activity_at', '>=', now()->subMinutes(5)),
            'recent' => $query->whereBetween('last_activity_at', [
                now()->subMinutes(15),
                now()->subMinutes(5),
            ]),
            'offline' => $query->where('last_activity_at', '<', now()->subMinutes(15))
                ->whereNotNull('last_activity_at'),
            default => $query
        };
    }

    /**
     * Scope para filtrar usuarios/clientes activos recientemente
     * Activos = última actividad en la última hora
     */
    public function scopeRecentlyActive(Builder $query): Builder
    {
        return $query->where('last_activity_at', '>=', now()->subHour());
    }

    /**
     * Scope para filtrar usuarios/clientes inactivos
     * Inactivos = sin actividad en los últimos X días
     *
     * @param  int  $days  - Días de inactividad (default: 30)
     */
    public function scopeInactive(Builder $query, int $days = 30): Builder
    {
        return $query->where(function ($q) use ($days) {
            $q->where('last_activity_at', '<', now()->subDays($days))
                ->orWhereNull('last_activity_at');
        });
    }
}
