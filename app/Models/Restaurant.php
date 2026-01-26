<?php

namespace App\Models;

use App\Contracts\ActivityLoggable;
use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Restaurant extends Model implements ActivityLoggable
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
        'price_location',
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
        'estimated_pickup_time',
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
        'estimated_pickup_time' => 'integer',
    ];

    /**
     * Scope para restaurantes activos
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope para restaurantes con delivery activo (requiere geofence)
     */
    public function scopeDeliveryActive($query)
    {
        return $query->where('delivery_active', true)
            ->where('is_active', true)
            ->whereNotNull('geofence_kml')
            ->where('geofence_kml', '!=', '');
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
     * Obtiene el horario como texto legible para hoy (formato 24h)
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
     * Verifica si el restaurante puede aceptar pedidos según el tipo de servicio.
     *
     * - Pickup: Puede pedir hasta (horario_cierre - tiempo_preparación)
     * - Delivery: Puede pedir hasta horario_cierre (el motorista puede entregar después)
     */
    public function canAcceptOrdersNow(string $serviceType = 'pickup'): bool
    {
        if (! $this->is_active || ! $this->schedule) {
            return false;
        }

        // Verificar que el servicio esté activo
        if ($serviceType === 'pickup' && ! $this->pickup_active) {
            return false;
        }
        if ($serviceType === 'delivery' && ! $this->delivery_active) {
            return false;
        }

        $today = strtolower(now()->format('l'));
        $todaySchedule = $this->schedule[$today] ?? null;

        if (! $todaySchedule || ! $todaySchedule['is_open']) {
            return false;
        }

        $currentTime = now()->format('H:i');
        $openTime = $todaySchedule['open'];
        $closeTime = $todaySchedule['close'];

        // Verificar que estemos después de la hora de apertura
        if ($currentTime < $openTime) {
            return false;
        }

        // Para pickup: último pedido = cierre - tiempo de preparación
        if ($serviceType === 'pickup') {
            $lastOrderTime = $this->calculateLastOrderTimeForPickup($closeTime);

            return $currentTime <= $lastOrderTime;
        }

        // Para delivery: puede pedir hasta el cierre
        return $currentTime <= $closeTime;
    }

    /**
     * Obtiene el último horario para realizar un pedido según el tipo de servicio (formato 24h).
     */
    public function getLastOrderTime(string $serviceType = 'pickup'): ?string
    {
        if (! $this->schedule) {
            return null;
        }

        $today = strtolower(now()->format('l'));
        $todaySchedule = $this->schedule[$today] ?? null;

        if (! $todaySchedule || ! $todaySchedule['is_open']) {
            return null;
        }

        $closeTime = $todaySchedule['close'];

        if ($serviceType === 'pickup') {
            return $this->calculateLastOrderTimeForPickup($closeTime);
        }

        // Para delivery, el último pedido es al cierre
        return $closeTime;
    }

    /**
     * Calcula el último horario para pedidos de pickup (formato 24h).
     */
    protected function calculateLastOrderTimeForPickup(string $closeTime): string
    {
        $preparationMinutes = $this->estimated_pickup_time ?? 15;

        $closeCarbon = \Carbon\Carbon::createFromFormat('H:i', $closeTime);
        $lastOrderCarbon = $closeCarbon->copy()->subMinutes($preparationMinutes);

        return $lastOrderCarbon->format('H:i');
    }

    /**
     * Obtiene el horario de cierre de hoy (formato 24h).
     */
    public function getClosingTimeToday(): ?string
    {
        if (! $this->schedule) {
            return null;
        }

        $today = strtolower(now()->format('l'));
        $todaySchedule = $this->schedule[$today] ?? null;

        if (! $todaySchedule || ! $todaySchedule['is_open']) {
            return null;
        }

        return $todaySchedule['close'];
    }

    /**
     * Obtiene el horario de apertura de hoy (formato 24h).
     */
    public function getOpeningTimeToday(): ?string
    {
        if (! $this->schedule) {
            return null;
        }

        $today = strtolower(now()->format('l'));
        $todaySchedule = $this->schedule[$today] ?? null;

        if (! $todaySchedule || ! $todaySchedule['is_open']) {
            return null;
        }

        return $todaySchedule['open'];
    }

    /**
     * Obtiene el próximo horario de apertura (hoy o mañana) (formato 24h).
     */
    public function getNextOpenTime(): ?array
    {
        if (! $this->schedule) {
            return null;
        }

        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        $todayIndex = array_search(strtolower(now()->format('l')), $days);
        $currentTime = now()->format('H:i');

        // Primero verificar si aún puede abrir hoy
        $today = $days[$todayIndex];
        $todaySchedule = $this->schedule[$today] ?? null;

        if ($todaySchedule && $todaySchedule['is_open'] && $currentTime < $todaySchedule['open']) {
            return [
                'day' => 'Hoy',
                'time' => $todaySchedule['open'],
            ];
        }

        // Buscar en los próximos días
        for ($i = 1; $i <= 7; $i++) {
            $nextDayIndex = ($todayIndex + $i) % 7;
            $nextDay = $days[$nextDayIndex];
            $nextSchedule = $this->schedule[$nextDay] ?? null;

            if ($nextSchedule && $nextSchedule['is_open']) {
                $dayName = $i === 1 ? 'Mañana' : ucfirst($nextDay);

                return [
                    'day' => $dayName,
                    'time' => $nextSchedule['open'],
                ];
            }
        }

        return null;
    }

    /**
     * Obtiene información completa de disponibilidad para el API (formato 24h).
     */
    public function getAvailabilityInfo(string $serviceType = 'pickup'): array
    {
        $isOpen = $this->isOpenNow();
        $canAcceptOrders = $this->canAcceptOrdersNow($serviceType);
        $closingTime = $this->getClosingTimeToday();
        $lastOrderTime = $this->getLastOrderTime($serviceType);
        $nextOpen = $this->getNextOpenTime();
        $openingTime = $this->getOpeningTimeToday();

        $preparationMinutes = $serviceType === 'pickup'
            ? ($this->estimated_pickup_time ?? 15)
            : ($this->estimated_delivery_time ?? 30);

        // Calcular ventana de tiempo para recogida/entrega
        $pickupWindow = $this->calculatePickupWindow($closingTime, $preparationMinutes, $canAcceptOrders);

        return [
            'is_open' => $isOpen,
            'can_accept_orders' => $canAcceptOrders,
            'service_type' => $serviceType,
            'opening_time' => $openingTime,
            'closing_time' => $closingTime,
            'last_order_time' => $lastOrderTime,
            'preparation_time_minutes' => $preparationMinutes,
            // Nuevos campos para facilitar cálculos en el cliente
            'pickup_window' => $pickupWindow,
            'next_open' => $nextOpen,
            'message' => $this->getAvailabilityMessage($serviceType, $canAcceptOrders, $closingTime, $lastOrderTime, $nextOpen),
        ];
    }

    /**
     * Calcula la ventana de tiempo disponible para recogida.
     *
     * @return array{earliest: string|null, latest: string|null, has_availability: bool}
     */
    protected function calculatePickupWindow(?string $closingTime, int $preparationMinutes, bool $canAcceptOrders): array
    {
        if (! $canAcceptOrders || ! $closingTime) {
            return [
                'earliest' => null,
                'latest' => null,
                'has_availability' => false,
            ];
        }

        $now = now();
        $earliest = $now->copy()->addMinutes($preparationMinutes);
        $latest = \Carbon\Carbon::createFromFormat('H:i', $closingTime);

        // Si la hora de cierre es menor que la hora actual, es del día siguiente
        if ($latest->lt($now->copy()->startOfDay()->addHours($latest->hour)->addMinutes($latest->minute))) {
            $latest = $latest->addDay();
        } else {
            $latest = $now->copy()->startOfDay()->addHours($latest->hour)->addMinutes($latest->minute);
        }

        // Verificar si hay disponibilidad (earliest debe ser menor que latest)
        $hasAvailability = $earliest->lt($latest);

        return [
            'earliest' => $earliest->format('H:i'),
            'latest' => $latest->format('H:i'),
            'has_availability' => $hasAvailability,
        ];
    }

    /**
     * Genera mensaje de disponibilidad legible.
     */
    protected function getAvailabilityMessage(
        string $serviceType,
        bool $canAcceptOrders,
        ?string $closingTime,
        ?string $lastOrderTime,
        ?array $nextOpen
    ): ?string {
        if ($canAcceptOrders) {
            if ($serviceType === 'pickup' && $lastOrderTime && $closingTime) {
                return sprintf(
                    'Puedes ordenar hasta las %s (cierre: %s)',
                    $lastOrderTime,
                    $closingTime
                );
            }
            if ($closingTime) {
                return sprintf('Abierto hasta las %s', $closingTime);
            }

            return 'Abierto';
        }

        // No puede aceptar pedidos
        if ($nextOpen) {
            return sprintf(
                'Cerrado. Abre %s a las %s',
                strtolower($nextOpen['day']),
                $nextOpen['time']
            );
        }

        return 'Cerrado';
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

    /**
     * Campo usado para identificar el modelo en los logs de actividad
     */
    public function getActivityLabelField(): string
    {
        return 'name';
    }

    /**
     * Nombre del modelo para los logs de actividad
     */
    public static function getActivityModelName(): string
    {
        return 'Restaurante';
    }

    /**
     * Relacion con los usuarios del restaurante
     */
    public function users(): HasMany
    {
        return $this->hasMany(RestaurantUser::class);
    }

    /**
     * Relacion con los motoristas del restaurante
     */
    public function drivers(): HasMany
    {
        return $this->hasMany(Driver::class);
    }

    /**
     * Relacion con el primer usuario del restaurante (considerado propietario)
     */
    public function owner(): HasOne
    {
        return $this->hasOne(RestaurantUser::class)->oldest();
    }
}
