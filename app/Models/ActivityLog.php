<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo para los logs de actividad del sistema
 * Registra todas las acciones y cambios realizados por los usuarios
 */
class ActivityLog extends Model
{
    /**
     * Indicates if the model should be timestamped.
     */
    public $timestamps = true;

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
     * Tipos de eventos de actividad
     */
    public static function getEventTypes(): array
    {
        return [
            // Eventos de usuarios
            'user_created' => 'Usuario creado',
            'user_updated' => 'Usuario actualizado',
            'user_deleted' => 'Usuario eliminado',
            'user_restored' => 'Usuario restaurado',
            'user_force_deleted' => 'Usuario eliminado permanentemente',

            // Eventos de autenticación
            'login' => 'Inicio de sesión',
            'logout' => 'Cierre de sesión',
            'password_changed' => 'Contraseña cambiada',
            'profile_updated' => 'Perfil actualizado',

            // Eventos de configuración
            'settings_changed' => 'Configuración cambiada',
            'theme_changed' => 'Tema cambiado',

            // Eventos de archivos
            'file_uploaded' => 'Archivo subido',
            'file_deleted' => 'Archivo eliminado',

            // Eventos de permisos
            'permission_granted' => 'Permiso otorgado',
            'permission_revoked' => 'Permiso revocado',

            // Eventos de roles
            'role_created' => 'Rol creado',
            'role_updated' => 'Rol actualizado',
            'role_deleted' => 'Rol eliminado',
            'role_restored' => 'Rol restaurado',
            'role_force_deleted' => 'Rol eliminado permanentemente',
            'role_users_updated' => 'Usuarios de rol actualizados',
        ];
    }
}
