<?php

namespace App\Models;

use App\Contracts\ActivityLoggable;
use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Modelo para los roles del sistema
 */
class Role extends Model implements ActivityLoggable
{
    use LogsActivity;

    protected $fillable = [
        'name',
        'description',
        'is_system',
    ];

    protected $casts = [
        'is_system' => 'boolean',
    ];

    /**
     * Relación con usuarios que tienen este rol
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    /**
     * Relación con permisos asignados a este rol
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class);
    }

    /**
     * Verifica si el rol tiene un permiso específico
     */
    public function hasPermission(string $permission): bool
    {
        return $this->permissions()->where('name', $permission)->exists();
    }

    /**
     * Verifica si el rol es del sistema (no se puede eliminar)
     */
    public function isSystemRole(): bool
    {
        return $this->is_system;
    }

    /**
     * Campo usado para identificar el modelo en los logs de actividad
     */
    public function getActivityLabelField(): string
    {
        return 'name';
    }

    /**
     * Nombre del modelo para los logs de actividad
     */
    public static function getActivityModelName(): string
    {
        return 'Rol';
    }
}
