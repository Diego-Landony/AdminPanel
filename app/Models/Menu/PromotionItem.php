<?php

namespace App\Models\Menu;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo de Item de Promoción
 *
 * Tabla pivot entre promociones y productos/categorías.
 * Solo uno de product_id o category_id debe tener valor (XOR).
 */
class PromotionItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'promotion_id',
        'product_id',
        'variant_id',
        'category_id',
        'special_price_capital',
        'special_price_interior',
        'discount_percentage',
        'service_type',
        'validity_type',
        'valid_from',
        'valid_until',
        'time_from',
        'time_until',
        'weekdays',
    ];

    protected $casts = [
        'promotion_id' => 'integer',
        'product_id' => 'integer',
        'variant_id' => 'integer',
        'category_id' => 'integer',
        'special_price_capital' => 'decimal:2',
        'special_price_interior' => 'decimal:2',
        'discount_percentage' => 'decimal:2',
        'weekdays' => 'array',
    ];

    /**
     * Get valid_from formatted as Y-m-d for HTML date inputs
     */
    protected function validFrom(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(
            get: fn ($value) => $value ? \Carbon\Carbon::parse($value)->format('Y-m-d') : null,
        );
    }

    /**
     * Get valid_until formatted as Y-m-d for HTML date inputs
     */
    protected function validUntil(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(
            get: fn ($value) => $value ? \Carbon\Carbon::parse($value)->format('Y-m-d') : null,
        );
    }

    /**
     * Get time_from formatted as H:i
     */
    protected function timeFrom(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(
            get: fn ($value) => $value ? substr($value, 0, 5) : null,
        );
    }

    /**
     * Get time_until formatted as H:i
     */
    protected function timeUntil(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(
            get: fn ($value) => $value ? substr($value, 0, 5) : null,
        );
    }

    /**
     * Relación: Un item pertenece a una promoción
     */
    public function promotion(): BelongsTo
    {
        return $this->belongsTo(Promotion::class);
    }

    /**
     * Relación: Un item puede pertenecer a un producto
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Relación: Un item puede pertenecer a una variante
     */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    /**
     * Relación: Un item puede pertenecer a una categoría
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Relación: Un item puede pertenecer a un combo
     * Nota: Se usa 'product_id' como FK porque combos y productos
     * se distinguen por category.is_combo_category
     */
    public function combo(): BelongsTo
    {
        return $this->belongsTo(Combo::class, 'product_id');
    }

    /**
     * Determina si este item referencia a un combo o a un producto
     * basándose en si la categoría es una categoría de combos
     */
    public function isCombo(): bool
    {
        return $this->category?->is_combo_category ?? false;
    }

    /**
     * Obtiene la entidad asociada (Producto o Combo) según el tipo
     * Retorna el producto si es categoría normal, o el combo si es categoría de combos
     */
    public function item(): Product|Combo|null
    {
        if ($this->isCombo()) {
            return $this->combo;
        }

        return $this->product;
    }

    /**
     * Verifica si el producto/combo asociado está activo
     * Usado para determinar si la promoción puede aplicarse
     */
    public function isApplicable(): bool
    {
        $entity = $this->isCombo() ? $this->combo : $this->product;

        // Si no hay entidad asociada, no es aplicable
        if (! $entity) {
            return false;
        }

        // Verificar que la entidad esté activa
        return $entity->is_active ?? false;
    }

    /**
     * Verifica si este item aplica al tipo de servicio especificado
     */
    public function appliesToServiceType(string $serviceType): bool
    {
        if (! $this->service_type) {
            return true; // Si no hay restricción, aplica a todos
        }

        return match ($this->service_type) {
            'both' => true,
            'delivery_only' => $serviceType === 'delivery',
            'pickup_only' => $serviceType === 'pickup',
            default => false,
        };
    }

    /**
     * Verifica si este item es válido para la fecha/hora actual
     */
    public function isValidToday(?\Carbon\Carbon $datetime = null): bool
    {
        $datetime = $datetime ?? now();

        // Si no hay validity_type definido, usar 'weekdays' como default
        if (! $this->validity_type) {
            return true;
        }

        switch ($this->validity_type) {
            case 'permanent':
                return true;

            case 'weekdays':
                // Si no hay días definidos, no es válido
                if (! $this->weekdays || empty($this->weekdays)) {
                    return false;
                }
                // Obtener día de la semana (1 = Lunes, 7 = Domingo)
                $currentWeekday = (int) $datetime->format('N');

                return in_array($currentWeekday, $this->weekdays);

            case 'date_range':
                if (! $this->valid_from || ! $this->valid_until) {
                    return false;
                }

                return $datetime->between($this->valid_from, $this->valid_until);

            case 'time_range':
                if (! $this->time_from || ! $this->time_until) {
                    return false;
                }
                $currentTime = $datetime->format('H:i:s');

                return $currentTime >= $this->time_from && $currentTime <= $this->time_until;

            case 'date_time_range':
                if (! $this->valid_from || ! $this->valid_until || ! $this->time_from || ! $this->time_until) {
                    return false;
                }
                $dateValid = $datetime->between($this->valid_from, $this->valid_until);
                $timeValid = $datetime->format('H:i:s') >= $this->time_from
                          && $datetime->format('H:i:s') <= $this->time_until;

                return $dateValid && $timeValid;

            default:
                return false;
        }
    }

    /**
     * Obtiene el precio especial para una zona específica
     */
    public function getSpecialPriceForZone(string $zone): ?float
    {
        return $zone === 'capital'
            ? $this->special_price_capital
            : $this->special_price_interior;
    }

    /**
     * Verifica si este item puede aplicarse ahora mismo
     * Combina verificaciones de: entidad activa, fecha/hora válida y tipo de servicio
     */
    public function canApplyNow(?string $serviceType = null, ?\Carbon\Carbon $datetime = null): bool
    {
        // 1. Verificar que el producto/combo esté activo
        if (! $this->isApplicable()) {
            return false;
        }

        // 2. Verificar validez de fecha/hora
        if (! $this->isValidToday($datetime)) {
            return false;
        }

        // 3. Verificar tipo de servicio (si se proporciona)
        if ($serviceType && ! $this->appliesToServiceType($serviceType)) {
            return false;
        }

        return true;
    }

    /**
     * Scope: Filtra solo items con productos/combos activos
     *
     * Uso:
     *   $activeItems = PromotionItem::applicable()->get();
     *   $promotion->items()->applicable()->get();
     */
    public function scopeApplicable($query)
    {
        return $query->where(function ($q) {
            // Items donde el producto asociado está activo
            $q->whereHas('product', function ($productQuery) {
                $productQuery->where('is_active', true);
            })
            // O items donde el combo asociado está activo
                ->orWhereHas('combo', function ($comboQuery) {
                    $comboQuery->where('is_active', true);
                });
        });
    }
}
