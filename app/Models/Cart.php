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
        'delivery_address_id',
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
     * Relación con la dirección de entrega
     */
    public function deliveryAddress(): BelongsTo
    {
        return $this->belongsTo(CustomerAddress::class, 'delivery_address_id');
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

    /**
     * Calcula el resumen del carrito (subtotal, descuentos, total).
     *
     * @return array{subtotal: float, discounts: float, promotions_applied: array, total: float, items_count: int, discount_total: float, total_discount: float}
     */
    public function calculateSummary(): array
    {
        $cartService = app(\App\Services\CartService::class);
        $summary = $cartService->getCartSummary($this);

        // Normalizar los campos para compatibilidad
        $summary['discount_total'] = $summary['discounts'] ?? 0;
        $summary['total_discount'] = $summary['discounts'] ?? 0;

        return $summary;
    }

    /**
     * Verifica si el carrito puede proceder al checkout.
     */
    public function canCheckout(): bool
    {
        $messages = $this->getValidationMessages();

        return empty($messages);
    }

    /**
     * Obtiene los mensajes de validación que impiden el checkout.
     *
     * @return array<string>
     */
    public function getValidationMessages(): array
    {
        $messages = [];

        // Verificar que el carrito tenga items
        if ($this->isEmpty()) {
            $messages[] = 'El carrito está vacío.';

            return $messages;
        }

        // Verificar que tenga restaurante asignado
        if (! $this->restaurant_id) {
            $messages[] = 'No hay restaurante seleccionado.';

            return $messages;
        }

        // Cargar restaurante si no está cargado
        $restaurant = $this->relationLoaded('restaurant')
            ? $this->restaurant
            : $this->restaurant()->first();

        if (! $restaurant) {
            $messages[] = 'El restaurante no existe.';

            return $messages;
        }

        // Verificar que el restaurante esté activo
        if (! $restaurant->is_active) {
            $messages[] = 'El restaurante no está disponible actualmente.';
        }

        // Verificar disponibilidad según tipo de servicio
        if ($this->service_type === 'pickup' && ! $restaurant->pickup_active) {
            $messages[] = 'El restaurante no acepta pedidos para recoger en este momento.';
        }

        if ($this->service_type === 'delivery' && ! $restaurant->delivery_active) {
            $messages[] = 'El restaurante no acepta pedidos a domicilio en este momento.';
        }

        // Verificar si el restaurante está abierto
        if (method_exists($restaurant, 'isOpenNow') && ! $restaurant->isOpenNow()) {
            $messages[] = 'Este tipo de servicio no está disponible en este momento';
        }

        // Verificar monto mínimo de orden
        $summary = $this->calculateSummary();
        $total = (float) ($summary['total'] ?? 0);
        $minimumOrder = (float) ($restaurant->minimum_order_amount ?? 0);

        if ($minimumOrder > 0 && $total < $minimumOrder) {
            $messages[] = "El monto mínimo de orden es Q{$minimumOrder}. Tu carrito tiene Q{$total}.";
        }

        // Verificar dirección para delivery
        if ($this->service_type === 'delivery' && ! $this->delivery_address_id) {
            $messages[] = 'Selecciona una dirección de entrega.';
        }

        return $messages;
    }
}
