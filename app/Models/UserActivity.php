<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo para el tracking de actividad de usuarios
 * Registra todas las acciones que realizan los usuarios en el sistema
 */
class UserActivity extends Model
{
    use HasFactory;

    /**
     * Indicates if the model should be timestamped.
     */
    public $timestamps = true;

    /**
     * The name of the "updated at" column.
     * Since this model only has created_at, we disable updated_at
     */
    const UPDATED_AT = null;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'activity_type',
        'description',
        'user_agent',
        'url',
        'method',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'metadata' => 'array',
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
     * Tipos de actividad disponibles
     */
    public static function getActivityTypes(): array
    {
        return [
            'login' => 'Inicio de sesión',
            'logout' => 'Cierre de sesión',
            'page_view' => 'Vista de página',
            'action' => 'Acción del usuario',
            'api_call' => 'Llamada a API',
            'file_upload' => 'Subida de archivo',
            'file_download' => 'Descarga de archivo',
            'settings_change' => 'Cambio de configuración',
            'password_change' => 'Cambio de contraseña',
            'profile_update' => 'Actualización de perfil',
        ];
    }
}
