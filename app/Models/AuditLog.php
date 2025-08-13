<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo para los logs de auditoría del sistema
 * Registra eventos importantes y cambios en el sistema
 */
class AuditLog extends Model
{
    /**
     * Indicates if the model should be timestamped.
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'event_type',
        'target_model',
        'target_id',
        'description',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'created_at' => 'datetime',
    ];

    /**
     * Relación con el modelo User
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Tipos de eventos de auditoría
     */
    public static function getEventTypes(): array
    {
        return [
            'user_created' => 'Usuario creado',
            'user_updated' => 'Usuario actualizado',
            'user_deleted' => 'Usuario eliminado',
            'login' => 'Inicio de sesión',
            'logout' => 'Cierre de sesión',
            'password_changed' => 'Contraseña cambiada',
            'profile_updated' => 'Perfil actualizado',
            'settings_changed' => 'Configuración cambiada',
            'file_uploaded' => 'Archivo subido',
            'file_deleted' => 'Archivo eliminado',
            'permission_granted' => 'Permiso otorgado',
            'permission_revoked' => 'Permiso revocado',
        ];
    }
}
