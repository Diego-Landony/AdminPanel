<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cart extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'restaurant_id',
        'service_type',
        'zone',
        'status',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'customer_id' => 'integer',
            'restaurant_id' => 'integer',
            'expires_at' => 'datetime',
        ];
    }

    /**
     * Relación: Un carrito pertenece a un cliente
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Relación: Un carrito pertenece a un restaurante (opcional)
     */
    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    /**
     * Relación: Un carrito tiene múltiples items
     */
    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    /**
     * Scope: Carritos activos
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope: Carritos abandonados
     */
    public function scopeAbandoned($query)
    {
        return $query->where('status', 'abandoned');
    }

    /**
     * Scope: Carritos convertidos
     */
    public function scopeConverted($query)
    {
        return $query->where('status', 'converted');
    }

    /**
     * Scope: Carritos no expirados
     */
    public function scopeNotExpired($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
                ->orWhere('expires_at', '>', now());
        });
    }

    /**
     * Verifica si el carrito está expirado
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Verifica si el carrito está vacío
     */
    public function isEmpty(): bool
    {
        return $this->items()->count() === 0;
    }

    /**
     * Scope: Carritos de un cliente específico
     */
    public function scopeForCustomer($query, $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    /**
     * Calcula el subtotal del carrito
     */
    public function getSubtotal(): float
    {
        return (float) $this->items->sum(function ($item) {
            return $item->subtotal;
        });
    }

    /**
     * Obtiene el tipo de precio según zona y servicio
     */
    public function getPriceType(): string
    {
        return match ([$this->zone, $this->service_type]) {
            ['capital', 'pickup'] => 'precio_pickup_capital',
            ['capital', 'delivery'] => 'precio_domicilio_capital',
            ['interior', 'pickup'] => 'precio_pickup_interior',
            ['interior', 'delivery'] => 'precio_domicilio_interior',
            default => 'precio_pickup_capital',
        };
    }
}
