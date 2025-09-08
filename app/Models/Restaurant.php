<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Restaurant extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'description', 
        'latitude',
        'longitude',
        'address',
        'is_active',
        'delivery_active',
        'pickup_active',
        'phone',
        'schedule',
        'minimum_order_amount',
        'delivery_area',
        'image',
        'email',
        'manager_name',
        'delivery_fee',
        'estimated_delivery_time',
        'rating',
        'total_reviews',
        'sort_order',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'is_active' => 'boolean',
        'delivery_active' => 'boolean',
        'pickup_active' => 'boolean',
        'schedule' => 'array',
        'delivery_area' => 'array',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'minimum_order_amount' => 'decimal:2',
        'delivery_fee' => 'decimal:2',
        'rating' => 'decimal:2',
        'estimated_delivery_time' => 'integer',
        'total_reviews' => 'integer',
        'sort_order' => 'integer',
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
     * Scope para ordenar por sort_order
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /**
     * Obtiene el estado como texto legible
     */
    public function getStatusTextAttribute(): string
    {
        if (!$this->is_active) {
            return 'Inactivo';
        }

        $statuses = [];
        if ($this->delivery_active) $statuses[] = 'Delivery';
        if ($this->pickup_active) $statuses[] = 'Pickup';

        return empty($statuses) ? 'Solo presencial' : implode(' + ', $statuses);
    }

    /**
     * Obtiene el horario como texto legible para hoy
     */
    public function getTodayScheduleAttribute(): ?string
    {
        if (!$this->schedule) {
            return null;
        }

        $today = strtolower(now()->format('l')); // monday, tuesday, etc.
        $todaySchedule = $this->schedule[$today] ?? null;

        if (!$todaySchedule || !$todaySchedule['is_open']) {
            return 'Cerrado hoy';
        }

        return "{$todaySchedule['open']} - {$todaySchedule['close']}";
    }

    /**
     * Verifica si el restaurante está abierto ahora
     */
    public function isOpenNow(): bool
    {
        if (!$this->is_active || !$this->schedule) {
            return false;
        }

        $today = strtolower(now()->format('l'));
        $todaySchedule = $this->schedule[$today] ?? null;

        if (!$todaySchedule || !$todaySchedule['is_open']) {
            return false;
        }

        $currentTime = now()->format('H:i');
        return $currentTime >= $todaySchedule['open'] && $currentTime <= $todaySchedule['close'];
    }

    /**
     * Obtiene la calificación como estrellas
     */
    public function getRatingStarsAttribute(): array
    {
        $rating = $this->rating;
        $stars = [];

        for ($i = 1; $i <= 5; $i++) {
            if ($rating >= $i) {
                $stars[] = 'full';
            } elseif ($rating >= $i - 0.5) {
                $stars[] = 'half';
            } else {
                $stars[] = 'empty';
            }
        }

        return $stars;
    }

    /**
     * Actualiza el rating basado en nuevas reseñas
     */
    public function updateRating(float $newRating): void
    {
        $totalReviews = $this->total_reviews;
        $currentRating = $this->rating;
        
        $newTotalRating = ($currentRating * $totalReviews) + $newRating;
        $newTotalReviews = $totalReviews + 1;
        
        $this->update([
            'rating' => $newTotalRating / $newTotalReviews,
            'total_reviews' => $newTotalReviews,
        ]);
    }
}