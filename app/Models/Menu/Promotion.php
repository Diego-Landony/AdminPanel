<?php

namespace App\Models\Menu;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Promotion extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'type',
        'is_active',
        'special_bundle_price_capital',
        'special_bundle_price_interior',
        'valid_from',
        'valid_until',
        'time_from',
        'time_until',
        'weekdays',
    ];

    protected $casts = [
        'type' => 'string',
        'is_active' => 'boolean',
        'special_bundle_price_capital' => 'decimal:2',
        'special_bundle_price_interior' => 'decimal:2',
        'weekdays' => 'array', // Array de enteros 1-7 (ISO-8601): 1=Lunes, 7=Domingo, null=todos los días
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
     * Relación: Una promoción tiene múltiples items
     */
    public function items(): HasMany
    {
        return $this->hasMany(PromotionItem::class);
    }

    /**
     * Relación: Una promoción se aplica a múltiples productos
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'promotion_items');
    }

    /**
     * Relación: Una promoción se aplica a múltiples variantes
     */
    public function variants(): BelongsToMany
    {
        return $this->belongsToMany(ProductVariant::class, 'promotion_items', 'promotion_id', 'variant_id');
    }

    /**
     * Relación: Una promoción se aplica a múltiples categorías
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'promotion_items');
    }

    /**
     * Relación: Una promoción de tipo bundle_special tiene múltiples items de combinado
     */
    public function bundleItems(): HasMany
    {
        return $this->hasMany(BundlePromotionItem::class, 'promotion_id')->orderBy('sort_order');
    }

    /**
     * Scope: Promociones activas
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Promociones activas en un momento específico
     *
     * NOTA: La lógica de vigencia temporal ahora está en PromotionItem::isValidToday()
     * Este scope solo verifica que la promoción esté activa.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  \Illuminate\Support\Carbon|null  $datetime
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActiveNow($query, $datetime = null)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Promociones para una variante específica
     */
    public function scopeForVariant($query, ProductVariant $variant)
    {
        return $query->where(function ($q) use ($variant) {
            // Aplica a la variante específica
            $q->whereHas('items', fn ($q2) => $q2->where('variant_id', $variant->id))
                // O al producto padre
                ->orWhereHas('items', fn ($q2) => $q2->where('product_id', $variant->product_id))
                // O a las categorías del producto
                ->orWhereHas('items', function ($q2) use ($variant) {
                    $q2->whereIn('category_id',
                        $variant->product->categories()->pluck('categories.id')
                    );
                });
        });
    }

    /**
     * Scope: Promociones para una categoría específica
     */
    public function scopeForCategory($query, Category $category)
    {
        return $query->whereHas('items', fn ($q) => $q->where('category_id', $category->id));
    }

    /**
     * Scope: Ordenar por fecha de creación
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('is_active', 'desc')
            ->orderBy('created_at', 'desc');
    }

    /**
     * Scope: Promociones de Sub del Día
     */
    public function scopeDailySpecial($query)
    {
        return $query->where('type', 'daily_special');
    }

    /**
     * Scope: Promociones tipo Combinados (bundle_special)
     */
    public function scopeCombinados($query)
    {
        return $query->where('type', 'bundle_special');
    }

    /**
     * Scope: Combinados válidos en este momento (fechas + horarios + días)
     */
    public function scopeValidNowCombinados($query, ?\Carbon\Carbon $datetime = null)
    {
        $datetime = $datetime ?? now();
        $currentDate = $datetime->format('Y-m-d');
        $currentTime = $datetime->format('H:i:s');
        $currentWeekday = $datetime->dayOfWeekIso; // 1 (lunes) a 7 (domingo)

        return $query->combinados()
            ->where('is_active', true)
            ->where(function ($q) use ($currentDate) {
                // valid_from: null = sin límite inferior
                $q->whereNull('valid_from')
                    ->orWhere('valid_from', '<=', $currentDate);
            })
            ->where(function ($q) use ($currentDate) {
                // valid_until: null = sin límite superior
                $q->whereNull('valid_until')
                    ->orWhere('valid_until', '>=', $currentDate);
            })
            ->where(function ($q) use ($currentTime) {
                // time_from: null = todo el día
                $q->whereNull('time_from')
                    ->orWhere('time_from', '<=', $currentTime);
            })
            ->where(function ($q) use ($currentTime) {
                // time_until: null = todo el día
                $q->whereNull('time_until')
                    ->orWhere('time_until', '>=', $currentTime);
            })
            ->where(function ($q) use ($currentWeekday) {
                // weekdays: null = todos los días, o debe contener el día actual
                $q->whereNull('weekdays')
                    ->orWhereRaw('JSON_CONTAINS(weekdays, ?)', [json_encode($currentWeekday)]);
            });
    }

    /**
     * Scope: Combinados que aún no han iniciado
     */
    public function scopeUpcoming($query, ?\Carbon\Carbon $datetime = null)
    {
        $datetime = $datetime ?? now();
        $currentDate = $datetime->format('Y-m-d');

        return $query->combinados()
            ->where('is_active', true)
            ->whereNotNull('valid_from')
            ->where('valid_from', '>', $currentDate);
    }

    /**
     * Scope: Combinados que ya expiraron
     */
    public function scopeExpired($query, ?\Carbon\Carbon $datetime = null)
    {
        $datetime = $datetime ?? now();
        $currentDate = $datetime->format('Y-m-d');

        return $query->combinados()
            ->whereNotNull('valid_until')
            ->where('valid_until', '<', $currentDate);
    }

    /**
     * Scope: Combinados disponibles (activos + validación de disponibilidad de items)
     * Similar a Combo::scopeAvailable() pero para bundle_special
     */
    public function scopeAvailable($query)
    {
        return $query->combinados()
            ->where('is_active', true)
            ->where(function ($q) {
                // Items fijos: todos activos
                $q->whereDoesntHave('bundleItems', function ($itemQuery) {
                    $itemQuery->where('is_choice_group', false)
                        ->whereHas('product', function ($productQuery) {
                            $productQuery->where('is_active', false);
                        });
                })
                // Grupos de elección: al menos 1 opción activa
                    ->whereDoesntHave('bundleItems', function ($itemQuery) {
                        $itemQuery->where('is_choice_group', true)
                            ->whereDoesntHave('options', function ($optionQuery) {
                                $optionQuery->whereHas('product', function ($productQuery) {
                                    $productQuery->where('is_active', true);
                                });
                            });
                    });
            });
    }

    /**
     * Scope: Combinados disponibles con relaciones cargadas para mostrar advertencias
     */
    public function scopeAvailableWithWarnings($query)
    {
        return $query->combinados()
            ->where('is_active', true)
            ->with([
                'bundleItems.options.product',
                'bundleItems.product',
            ]);
    }

    /**
     * Verifica si la promoción es válida en este momento
     *
     * NOTA: La vigencia temporal específica ahora está en los items individuales.
     * Este método solo verifica que la promoción esté activa y que al menos
     * un item sea válido en este momento.
     */
    public function isValidNow(?\Carbon\Carbon $datetime = null): bool
    {
        $datetime = $datetime ?? now();

        // Debe estar activa
        if (! $this->is_active) {
            return false;
        }

        // Verificar que al menos un item sea válido ahora
        return $this->items()->get()->contains(function ($item) use ($datetime) {
            return $item->isValidToday($datetime);
        });
    }

    /**
     * Verifica si es una promoción de tipo Combinado
     */
    public function isCombinado(): bool
    {
        return $this->type === 'bundle_special';
    }

    /**
     * Verifica si un Combinado es válido en este momento (fechas + horarios + días)
     * Solo aplica para bundle_special
     */
    public function isValidNowCombinado(?\Carbon\Carbon $datetime = null): bool
    {
        if (! $this->isCombinado()) {
            return false;
        }

        $datetime = $datetime ?? now();
        $currentDate = $datetime->toDateString();
        $currentTime = $datetime->toTimeString();
        $currentWeekday = $datetime->dayOfWeekIso; // 1-7

        // Debe estar activa
        if (! $this->is_active) {
            return false;
        }

        // Verificar rango de fechas
        if ($this->valid_from && $currentDate < $this->valid_from->toDateString()) {
            return false;
        }

        if ($this->valid_until && $currentDate > $this->valid_until->toDateString()) {
            return false;
        }

        // Verificar rango de horarios
        if ($this->time_from && $currentTime < $this->time_from) {
            return false;
        }

        if ($this->time_until && $currentTime > $this->time_until) {
            return false;
        }

        // Verificar días de la semana
        if ($this->weekdays && ! in_array($currentWeekday, $this->weekdays)) {
            return false;
        }

        return true;
    }

    /**
     * Obtiene el precio del Combinado para una zona específica
     * Solo aplica para bundle_special
     */
    public function getPriceForZoneCombinado(string $zone): ?float
    {
        if (! $this->isCombinado()) {
            return null;
        }

        return match ($zone) {
            'capital' => $this->special_bundle_price_capital ? (float) $this->special_bundle_price_capital : null,
            'interior' => $this->special_bundle_price_interior ? (float) $this->special_bundle_price_interior : null,
            default => null,
        };
    }

    /**
     * Verifica si el Combinado tiene items activos disponibles
     * Similar a Combo::isAvailable()
     */
    public function hasActiveItemsCombinado(): bool
    {
        if (! $this->isCombinado() || ! $this->is_active) {
            return false;
        }

        // Validar cada item del combinado
        foreach ($this->bundleItems as $item) {
            if ($item->isChoiceGroup()) {
                // Para grupos de elección: debe tener al menos una opción con producto activo
                $hasActiveOption = $item->options()
                    ->whereHas('product', fn ($q) => $q->where('is_active', true))
                    ->exists();

                if (! $hasActiveOption) {
                    return false;
                }
            } else {
                // Para items fijos: el producto debe estar activo
                if (! $item->product || ! $item->product->is_active) {
                    return false;
                }
            }
        }

        return true;
    }
}
