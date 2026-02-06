<?php

namespace App\Models;

use App\Models\Concerns\TracksUserStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Driver extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\DriverFactory> */
    use HasApiTokens, HasFactory, Notifiable, TracksUserStatus;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'restaurant_id',
        'name',
        'email',
        'password',
        'fcm_token',
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
        'fcm_token',
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
     * Relacion con órdenes pendientes de aceptar (status 'ready' asignadas a este driver)
     */
    public function pendingOrders(): HasMany
    {
        return $this->hasMany(Order::class)->where('status', Order::STATUS_READY);
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

    /**
     * Scope para filtrar drivers sin orden activa (out_for_delivery)
     */
    public function scopeWithoutActiveOrder(Builder $query): Builder
    {
        return $query->whereDoesntHave('orders', function (Builder $q) {
            $q->where('status', Order::STATUS_OUT_FOR_DELIVERY);
        });
    }

    /**
     * Verifica si el motorista tiene una orden activa (en camino)
     */
    public function hasActiveOrder(): bool
    {
        return $this->orders()
            ->where('status', Order::STATUS_OUT_FOR_DELIVERY)
            ->exists();
    }

    /**
     * Verifica si el motorista puede aceptar más órdenes
     * (no tiene orden activa en out_for_delivery)
     */
    public function canAcceptMoreOrders(): bool
    {
        return ! $this->hasActiveOrder();
    }

    /**
     * Calcula el promedio de calificaciones del motorista
     * basado en delivery_person_rating de órdenes completadas
     */
    public function getRatingAttribute(): ?float
    {
        $average = $this->orders()
            ->whereIn('status', [Order::STATUS_DELIVERED, Order::STATUS_COMPLETED])
            ->whereNotNull('delivery_person_rating')
            ->avg('delivery_person_rating');

        return $average !== null ? round((float) $average, 2) : null;
    }

    /**
     * Cuenta el total de entregas completadas del motorista
     */
    public function getTotalDeliveriesAttribute(): int
    {
        return $this->orders()
            ->whereIn('status', [Order::STATUS_DELIVERED, Order::STATUS_COMPLETED])
            ->count();
    }

    /**
     * Cuenta las entregas completadas del día actual
     */
    public function getDeliveriesTodayAttribute(): int
    {
        return $this->orders()
            ->whereIn('status', [Order::STATUS_DELIVERED, Order::STATUS_COMPLETED])
            ->whereDate('delivered_at', today())
            ->count();
    }

    /**
     * Calcula el promedio de tiempo de entrega en minutos
     * Tiempo = diferencia entre accepted_by_driver_at y delivered_at
     *
     * @return float|null Promedio en minutos o null si no hay datos
     */
    public function getAverageDeliveryTimeAttribute(): ?float
    {
        $orders = $this->orders()
            ->whereIn('status', [Order::STATUS_DELIVERED, Order::STATUS_COMPLETED])
            ->whereNotNull('accepted_by_driver_at')
            ->whereNotNull('delivered_at')
            ->get(['accepted_by_driver_at', 'delivered_at']);

        if ($orders->isEmpty()) {
            return null;
        }

        $totalMinutes = $orders->sum(function ($order) {
            return $order->accepted_by_driver_at->diffInMinutes($order->delivered_at);
        });

        return round($totalMinutes / $orders->count(), 2);
    }

    /**
     * Obtiene estadísticas detalladas del motorista para un período específico
     *
     * @param  string  $period  Período: today, week, month, year
     * @return array{total_deliveries: int, completed: int, average_time: float|null, rating: float|null}
     */
    public function getDetailedStats(string $period = 'month'): array
    {
        $query = $this->orders()
            ->whereIn('status', [Order::STATUS_DELIVERED, Order::STATUS_COMPLETED]);

        $query = match ($period) {
            'today' => $query->whereDate('delivered_at', today()),
            'week' => $query->whereBetween('delivered_at', [now()->startOfWeek(), now()->endOfWeek()]),
            'month' => $query->whereBetween('delivered_at', [now()->startOfMonth(), now()->endOfMonth()]),
            'year' => $query->whereBetween('delivered_at', [now()->startOfYear(), now()->endOfYear()]),
            default => $query->whereBetween('delivered_at', [now()->startOfMonth(), now()->endOfMonth()]),
        };

        $orders = $query->get();

        $completed = $orders->count();

        $ordersWithTimes = $orders->filter(function ($order) {
            return $order->accepted_by_driver_at !== null && $order->delivered_at !== null;
        });

        $averageTime = null;
        if ($ordersWithTimes->isNotEmpty()) {
            $totalMinutes = $ordersWithTimes->sum(function ($order) {
                return $order->accepted_by_driver_at->diffInMinutes($order->delivered_at);
            });
            $averageTime = round($totalMinutes / $ordersWithTimes->count(), 2);
        }

        $ordersWithRating = $orders->filter(function ($order) {
            return $order->delivery_person_rating !== null;
        });

        $rating = null;
        if ($ordersWithRating->isNotEmpty()) {
            $rating = round($ordersWithRating->avg('delivery_person_rating'), 2);
        }

        return [
            'total_deliveries' => $completed,
            'completed' => $completed,
            'average_time' => $averageTime,
            'rating' => $rating,
        ];
    }
}
