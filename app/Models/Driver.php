<?php

namespace App\Models;

use App\Models\Concerns\TracksUserStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Driver extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\DriverFactory> */
    use HasFactory, TracksUserStatus;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'restaurant_id',
        'name',
        'email',
        'phone',
        'password',
        'is_active',
        'is_available',
        'current_latitude',
        'current_longitude',
        'last_location_update',
        'last_login_at',
        'last_activity_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
    ];

    /**
     * Attributes that should be appended to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = ['status', 'is_online'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_active' => 'boolean',
            'is_available' => 'boolean',
            'current_latitude' => 'decimal:8',
            'current_longitude' => 'decimal:8',
            'last_location_update' => 'datetime',
            'last_login_at' => 'datetime',
            'last_activity_at' => 'datetime',
        ];
    }

    /**
     * Relacion con el restaurante
     */
    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    /**
     * Relacion con las ordenes asignadas
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Relacion con la orden activa (en camino)
     */
    public function activeOrder(): HasOne
    {
        return $this->hasOne(Order::class)->where('status', Order::STATUS_OUT_FOR_DELIVERY);
    }

    /**
     * Verifica si el motorista esta disponible
     */
    public function isAvailable(): bool
    {
        return $this->is_active && $this->is_available;
    }

    /**
     * Pone al motorista en linea
     */
    public function goOnline(): void
    {
        $this->update([
            'is_available' => true,
            'last_activity_at' => now(),
        ]);
    }

    /**
     * Pone al motorista fuera de linea
     */
    public function goOffline(): void
    {
        $this->update([
            'is_available' => false,
            'last_activity_at' => now(),
        ]);
    }

    /**
     * Actualiza la ubicacion del motorista
     */
    public function updateLocation(float $lat, float $lng): void
    {
        $this->update([
            'current_latitude' => $lat,
            'current_longitude' => $lng,
            'last_location_update' => now(),
            'last_activity_at' => now(),
        ]);
    }

    /**
     * Scope para filtrar motoristas activos
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope para filtrar motoristas disponibles
     */
    public function scopeAvailable($query)
    {
        return $query->where('is_active', true)->where('is_available', true);
    }

    /**
     * Scope para filtrar por restaurante
     */
    public function scopeForRestaurant($query, int $restaurantId)
    {
        return $query->where('restaurant_id', $restaurantId);
    }
}
