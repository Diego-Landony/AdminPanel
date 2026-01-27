<?php

namespace App\Models;

use App\Notifications\DriverAssignedNotification;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use HasFactory, SoftDeletes;

    const STATUS_PENDING = 'pending';

    const STATUS_PREPARING = 'preparing';

    const STATUS_READY = 'ready';

    const STATUS_OUT_FOR_DELIVERY = 'out_for_delivery';

    const STATUS_DELIVERED = 'delivered';

    const STATUS_COMPLETED = 'completed';

    const STATUS_CANCELLED = 'cancelled';

    const STATUS_REFUNDED = 'refunded';

    protected $fillable = [
        'order_number',
        'customer_id',
        'restaurant_id',
        'driver_id',
        'service_type',
        'zone',
        'delivery_address_id',
        'delivery_address_snapshot',
        'subtotal',
        'discount_total',
        'total',
        'status',
        'payment_method',
        'payment_status',
        'paid_at',
        'estimated_ready_at',
        'ready_at',
        'delivered_at',
        'assigned_to_driver_at',
        'picked_up_at',
        'points_earned',
        'nit_id',
        'nit_snapshot',
        'notes',
        'cancellation_reason',
        'scheduled_for',
        'scheduled_pickup_time',
        'delivery_person_rating',
        'delivery_person_comment',
    ];

    protected function casts(): array
    {
        return [
            'delivery_address_snapshot' => 'array',
            'nit_snapshot' => 'array',
            'paid_at' => 'datetime',
            'estimated_ready_at' => 'datetime',
            'ready_at' => 'datetime',
            'delivered_at' => 'datetime',
            'assigned_to_driver_at' => 'datetime',
            'picked_up_at' => 'datetime',
            'subtotal' => 'decimal:2',
            'discount_total' => 'decimal:2',
            'total' => 'decimal:2',
            'points_earned' => 'integer',
            'scheduled_for' => 'datetime',
            'scheduled_pickup_time' => 'datetime',
            'delivery_person_rating' => 'integer',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    public function deliveryAddress(): BelongsTo
    {
        return $this->belongsTo(CustomerAddress::class, 'delivery_address_id');
    }

    public function nit(): BelongsTo
    {
        return $this->belongsTo(CustomerNit::class, 'nit_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function promotions(): HasMany
    {
        return $this->hasMany(OrderPromotion::class);
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class);
    }

    public function review(): HasOne
    {
        return $this->hasOne(OrderReview::class);
    }

    public function scopeActive($query)
    {
        return $query->whereNotIn('status', [self::STATUS_COMPLETED, self::STATUS_CANCELLED, self::STATUS_REFUNDED]);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', self::STATUS_CANCELLED);
    }

    public function scopeForCustomer($query, int $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    /**
     * Verifica si la orden puede ser cancelada por el cliente.
     * Los clientes solo pueden cancelar órdenes que aún no han sido aceptadas por el restaurante.
     */
    public function canBeCancelled(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Verifica si la orden puede ser cancelada por un administrador.
     * Los administradores pueden cancelar órdenes en cualquier estado activo.
     */
    public function canBeCancelledByAdmin(): bool
    {
        return ! in_array($this->status, [
            self::STATUS_COMPLETED,
            self::STATUS_CANCELLED,
            self::STATUS_REFUNDED,
        ]);
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function isDelivery(): bool
    {
        return $this->service_type === 'delivery';
    }

    public function isPickup(): bool
    {
        return $this->service_type === 'pickup';
    }

    /**
     * Verifica si la orden tiene un motorista asignado
     */
    public function hasDriver(): bool
    {
        return $this->driver_id !== null;
    }

    /**
     * Verifica si la orden puede ser asignada a un motorista
     * Solo ordenes de delivery en estado 'ready'
     */
    public function canBeAssignedToDriver(): bool
    {
        return $this->status === self::STATUS_READY && $this->service_type === 'delivery';
    }

    /**
     * Asigna un motorista a la orden
     */
    public function assignDriver(Driver $driver): void
    {
        $this->update([
            'driver_id' => $driver->id,
            'assigned_to_driver_at' => now(),
        ]);

        // Notificar al cliente
        if ($this->customer) {
            $this->customer->notify(new DriverAssignedNotification($this));
        }
    }
}
