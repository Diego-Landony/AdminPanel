<?php

namespace App\Models;

use App\Contracts\ActivityLoggable;
use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Sanctum\PersonalAccessToken;

class CustomerDevice extends Model implements ActivityLoggable
{
    /** @use HasFactory<\Database\Factories\CustomerDeviceFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'customer_id',
        'sanctum_token_id',
        'fcm_token',
        'device_identifier',
        'device_fingerprint',
        'device_type',
        'device_name',
        'device_model',
        'app_version',
        'os_version',
        'last_used_at',
        'is_active',
        'login_count',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'last_used_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the field to use as the activity label.
     */
    public function getActivityLabelField(): string
    {
        return 'device_name';
    }

    /**
     * Get the human-readable model name for activity logs.
     */
    public static function getActivityModelName(): string
    {
        return 'Dispositivo de cliente';
    }

    /**
     * Accessor para determinar si este dispositivo es el dispositivo actual
     * (basado en el token Sanctum activo en la request)
     */
    public function getIsCurrentDeviceAttribute(): bool
    {
        if (! $this->sanctum_token_id) {
            return false;
        }

        $currentToken = request()->user()?->currentAccessToken();

        if (! $currentToken) {
            return false;
        }

        return $this->sanctum_token_id === $currentToken->id;
    }

    /**
     * Relación con el cliente
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Relación con el token de Sanctum (opcional)
     */
    public function token(): BelongsTo
    {
        return $this->belongsTo(PersonalAccessToken::class, 'sanctum_token_id');
    }

    /**
     * Actualiza el timestamp de último uso del dispositivo
     */
    public function updateLastUsed(): void
    {
        $this->last_used_at = now();
        $this->save();
    }

    /**
     * Marca el dispositivo como activo
     */
    public function markAsActive(): void
    {
        $this->is_active = true;
        $this->last_used_at = now();
        $this->save();
    }

    /**
     * Marca el dispositivo como inactivo
     */
    public function markAsInactive(): void
    {
        $this->is_active = false;
        $this->save();
    }

    /**
     * Scope para obtener solo dispositivos activos (columna is_active = true)
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope para dispositivos inactivos (is_active = false)
     */
    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    /**
     * Scope para dispositivos que deben marcarse como inactivos (365+ días sin uso)
     *
     * Lifecycle: 0-365 días (1 año) = Activo, recibe push notifications
     */
    public function scopeShouldBeInactive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('last_used_at')
                    ->orWhere('last_used_at', '<=', now()->subDays(365));
            });
    }

    /**
     * Scope para dispositivos que deben eliminarse (548+ días sin uso)
     *
     * Lifecycle: 548+ días (1.5 años) = Soft deleted
     */
    public function scopeShouldBeDeleted($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('last_used_at')
                ->orWhere('last_used_at', '<=', now()->subDays(548));
        });
    }
}
