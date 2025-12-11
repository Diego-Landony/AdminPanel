<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Restaurant extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'latitude',
        'longitude',
        'geofence_kml',
        'address',
        'is_active',
        'delivery_active',
        'pickup_active',
        'phone',
        'schedule',
        'minimum_order_amount',
        'email',
        'estimated_delivery_time',
        'ip',
        'franchise_number',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'is_active' => 'boolean',
        'delivery_active' => 'boolean',
        'pickup_active' => 'boolean',
        'schedule' => 'array',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'minimum_order_amount' => 'decimal:2',
        'estimated_delivery_time' => 'integer',
    ];

    /**
     * Scope para restaurantes activos
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope para restaurantes con delivery activo
     */
    public function scopeDeliveryActive($query)
    {
        return $query->where('delivery_active', true)->where('is_active', true);
    }

    /**
     * Scope para restaurantes con pickup activo
     */
    public function scopePickupActive($query)
    {
        return $query->where('pickup_active', true)->where('is_active', true);
    }

    /**
     * Scope para restaurantes con geofence definido
     */
    public function scopeWithGeofence($query)
    {
        return $query->whereNotNull('geofence_kml');
    }

    /**
     * Scope para restaurantes sin geofence
     */
    public function scopeWithoutGeofence($query)
    {
        return $query->whereNull('geofence_kml');
    }

    /**
     * Scope para restaurantes con coordenadas definidas
     */
    public function scopeWithCoordinates($query)
    {
        return $query->whereNotNull('latitude')->whereNotNull('longitude');
    }

    /**
     * Scope para ordenar por nombre
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('name');
    }

    /**
     * Obtiene el estado como texto legible
     */
    public function getStatusTextAttribute(): string
    {
        if (! $this->is_active) {
            return 'Inactivo';
        }

        $statuses = [];
        if ($this->delivery_active) {
            $statuses[] = 'Delivery';
        }
        if ($this->pickup_active) {
            $statuses[] = 'Pickup';
        }

        return empty($statuses) ? 'Solo presencial' : implode(' + ', $statuses);
    }

    /**
     * Obtiene el horario como texto legible para hoy
     */
    public function getTodayScheduleAttribute(): ?string
    {
        if (! $this->schedule) {
            return null;
        }

        $today = strtolower(now()->format('l')); // monday, tuesday, etc.
        $todaySchedule = $this->schedule[$today] ?? null;

        if (! $todaySchedule || ! $todaySchedule['is_open']) {
            return 'Cerrado hoy';
        }

        return "{$todaySchedule['open']} - {$todaySchedule['close']}";
    }

    /**
     * Verifica si el restaurante está abierto ahora
     */
    public function isOpenNow(): bool
    {
        if (! $this->is_active || ! $this->schedule) {
            return false;
        }

        $today = strtolower(now()->format('l'));
        $todaySchedule = $this->schedule[$today] ?? null;

        if (! $todaySchedule || ! $todaySchedule['is_open']) {
            return false;
        }

        $currentTime = now()->format('H:i');

        return $currentTime >= $todaySchedule['open'] && $currentTime <= $todaySchedule['close'];
    }

    /**
     * Verifica si el restaurante tiene geofence KML definido
     */
    public function hasGeofence(): bool
    {
        return ! empty($this->geofence_kml);
    }

    /**
     * Obtiene las coordenadas como array para mapas
     */
    public function getCoordinatesAttribute(): ?array
    {
        if (! $this->latitude || ! $this->longitude) {
            return null;
        }

        return [
            'lat' => (float) $this->latitude,
            'lng' => (float) $this->longitude,
        ];
    }
}
