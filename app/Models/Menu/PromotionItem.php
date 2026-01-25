<?php

namespace App\Models\Menu;

use App\Traits\InvalidatesMenuVersion;
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
    use HasFactory, InvalidatesMenuVersion;

    protected $fillable = [
        'promotion_id',
        'product_id',
        'variant_id',
        'category_id',
        'special_price_pickup_capital',
        'special_price_delivery_capital',
        'special_price_pickup_interior',
        'special_price_delivery_interior',
        'discount_percentage',
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
        'special_price_pickup_capital' => 'decimal:2',
        'special_price_delivery_capital' => 'decimal:2',
        'special_price_pickup_interior' => 'decimal:2',
        'special_price_delivery_interior' => 'decimal:2',
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
     * Verifica si este item es válido para la fecha/hora actual.
     *
     * Soporta las siguientes combinaciones de vigencia:
     * 1. Permanente (sin restricciones)
     * 2. Solo días de la semana (ej: todos los Martes siempre)
     * 3. Días + rango de fechas (ej: Martes del 1 al 31 de Enero)
     * 4. Días + rango de horarios (ej: Martes de 11:00 a 15:00)
     * 5. Días + fechas + horarios (ej: Martes de 11:00 a 15:00, del 1 al 31 de Enero)
     * 6. Solo rango de fechas (sin días específicos)
     * 7. Solo rango de horarios (sin días específicos)
     * 8. Fechas + horarios (sin días específicos)
     *
     * weekdays SIEMPRE se evalúa si está definido, independiente del validity_type.
     */
    public function isValidToday(?\Carbon\Carbon $datetime = null): bool
    {
        $datetime = $datetime ?? now();

        // PASO 1: Verificar weekdays (si está definido, SIEMPRE debe cumplirse)
        if ($this->weekdays && ! empty($this->weekdays)) {
            $currentWeekday = (int) $datetime->format('N'); // 1=Lunes, 7=Domingo
            if (! in_array($currentWeekday, $this->weekdays)) {
                return false; // No es un día válido
            }
        }

        // PASO 2: Verificar restricciones adicionales según validity_type
        // Si no hay validity_type, solo weekdays importaba (ya verificado arriba)
        if (! $this->validity_type || $this->validity_type === 'permanent' || $this->validity_type === 'weekdays') {
            return true;
        }

        // PASO 3: Verificar rango de fechas (si aplica)
        $dateValid = true;
        if (in_array($this->validity_type, ['date_range', 'date_time_range'])) {
            if (! $this->valid_from || ! $this->valid_until) {
                return false;
            }
            $dateValid = $datetime->between($this->valid_from, $this->valid_until);
        }

        // PASO 4: Verificar rango de horarios (si aplica)
        $timeValid = true;
        if (in_array($this->validity_type, ['time_range', 'date_time_range'])) {
            if (! $this->time_from || ! $this->time_until) {
                return false;
            }
            $currentTime = $datetime->format('H:i:s');
            $timeValid = $currentTime >= $this->time_from && $currentTime <= $this->time_until;
        }

        return $dateValid && $timeValid;
    }

    /**
     * Obtiene el precio especial para una combinación de servicio y zona.
     *
     * @param  string  $serviceType  'pickup' o 'delivery'
     * @param  string  $zone  'capital' o 'interior'
     */
    public function getSpecialPrice(string $serviceType, string $zone): ?float
    {
        $column = "special_price_{$serviceType}_{$zone}";

        return $this->{$column};
    }

    /**
     * Obtiene todos los precios especiales como array estandarizado.
     *
     * @return array<string, float|null>
     */
    public function getSpecialPrices(): array
    {
        return [
            'pickup_capital' => $this->special_price_pickup_capital ? (float) $this->special_price_pickup_capital : null,
            'delivery_capital' => $this->special_price_delivery_capital ? (float) $this->special_price_delivery_capital : null,
            'pickup_interior' => $this->special_price_pickup_interior ? (float) $this->special_price_pickup_interior : null,
            'delivery_interior' => $this->special_price_delivery_interior ? (float) $this->special_price_delivery_interior : null,
        ];
    }

    /**
     * Verifica si este item puede aplicarse ahora mismo
     * Combina verificaciones de: entidad activa, fecha/hora válida y tipo de servicio
     */
    public function canApplyNow(?\Carbon\Carbon $datetime = null): bool
    {
        // 1. Verificar que el producto/combo esté activo
        if (! $this->isApplicable()) {
            return false;
        }

        // 2. Verificar validez de fecha/hora
        if (! $this->isValidToday($datetime)) {
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
