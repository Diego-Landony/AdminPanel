<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo para los logs de actividad del sistema
 * Registra todas las acciones y cambios realizados por los usuarios
 */
class ActivityLog extends Model
{
    use HasFactory;

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
     * Scope: Filtrar por modelo específico
     */
    public function scopeForModel($query, string $modelClass, ?int $modelId = null)
    {
        $query->where('target_model', $modelClass);

        if ($modelId !== null) {
            $query->where('target_id', $modelId);
        }

        return $query;
    }

    /**
     * Scope: Filtrar por usuario
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope: Filtrar por tipo de evento
     */
    public function scopeOfType($query, string|array $types)
    {
        if (is_array($types)) {
            return $query->whereIn('event_type', $types);
        }

        return $query->where('event_type', $types);
    }

    /**
     * Scope: Solo eventos recientes
     */
    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Accessor: Descripción formateada con valores de configuración
     */
    public function getFormattedDescriptionAttribute(): string
    {
        return $this->description;
    }
}
