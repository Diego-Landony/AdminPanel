<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Sanctum\PersonalAccessToken;

class CustomerDevice extends Model
{
    /** @use HasFactory<\Database\Factories\CustomerDeviceFactory> */
    use HasFactory, SoftDeletes;

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
        'device_type',
        'device_name',
        'device_model',
        'app_version',
        'os_version',
        'last_used_at',
        'is_active',
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
     * Scope para dispositivos que deben marcarse como inactivos (30+ días sin uso)
     */
    public function scopeShouldBeInactive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('last_used_at')
                    ->orWhere('last_used_at', '<=', now()->subDays(30));
            });
    }

    /**
     * Scope para dispositivos que deben eliminarse (360+ días sin uso)
     */
    public function scopeShouldBeDeleted($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('last_used_at')
                ->orWhere('last_used_at', '<=', now()->subDays(360));
        });
    }
}
