<?php

namespace App\Models\Menu;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Promotion extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'type',
        'discount_value',
        'applies_to',
        'service_type',
        'validity_type',
        'is_permanent',
        'valid_from',
        'valid_until',
        'has_time_restriction',
        'time_from',
        'time_until',
        'active_days',
        'is_active',
    ];

    protected $casts = [
        'type' => 'string',
        'discount_value' => 'decimal:2',
        'applies_to' => 'string',
        'service_type' => 'string',
        'validity_type' => 'string',
        'is_permanent' => 'boolean',
        'valid_from' => 'datetime',
        'valid_until' => 'datetime',
        'has_time_restriction' => 'boolean',
        'active_days' => 'array',
        'is_active' => 'boolean',
    ];

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
     * Scope: Promociones activas
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Promociones activas en un momento específico
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  \Illuminate\Support\Carbon|null  $datetime
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActiveNow($query, $datetime = null)
    {
        $datetime = $datetime ?? now();
        $dayOfWeek = $datetime->dayOfWeek; // 0=Domingo, 1=Lunes, ...
        $currentDate = $datetime->toDateString();
        $currentTime = $datetime->format('H:i:s');

        return $query->where('is_active', true)
            // Día de la semana
            ->where(function ($q) use ($dayOfWeek) {
                $q->whereNull('active_days')
                    ->orWhereJsonContains('active_days', $dayOfWeek);
            })
            // Vigencia de fechas
            ->where(function ($q) use ($currentDate) {
                $q->where('is_permanent', true)
                    ->orWhere(function ($q2) use ($currentDate) {
                        $q2->whereDate('valid_from', '<=', $currentDate)
                            ->whereDate('valid_until', '>=', $currentDate);
                    });
            })
            // Restricción de horas
            ->where(function ($q) use ($currentTime) {
                $q->where('has_time_restriction', false)
                    ->orWhere(function ($q2) use ($currentTime) {
                        $q2->whereTime('time_from', '<=', $currentTime)
                            ->whereTime('time_until', '>=', $currentTime);
                    });
            });
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
     * Verifica si la promoción es válida en este momento
     */
    public function isValidNow(?\Carbon\Carbon $datetime = null): bool
    {
        $datetime = $datetime ?? now();

        // Debe estar activa
        if (! $this->is_active) {
            return false;
        }

        // Verificar vigencia de fechas
        if (! $this->is_permanent) {
            if ($this->valid_from && $datetime->lt($this->valid_from)) {
                return false;
            }
            if ($this->valid_until && $datetime->gt($this->valid_until)) {
                return false;
            }
        }

        // Verificar restricción de horario
        if ($this->has_time_restriction) {
            $currentTime = $datetime->format('H:i:s');
            if ($this->time_from && $this->time_until) {
                if ($currentTime < $this->time_from || $currentTime > $this->time_until) {
                    return false;
                }
            }
        }

        // Verificar días activos
        if ($this->active_days && count($this->active_days) > 0) {
            $dayOfWeek = $datetime->dayOfWeek; // 0=Domingo, 1=Lunes, ...
            if (! in_array($dayOfWeek, $this->active_days)) {
                return false;
            }
        }

        return true;
    }
}
