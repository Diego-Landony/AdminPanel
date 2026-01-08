<?php

namespace App\Models\Menu;

use App\Contracts\ActivityLoggable;
use App\Models\Concerns\HasBadges;
use App\Models\Concerns\HasReportingCategory;
use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Combo extends Model implements ActivityLoggable
{
    use HasBadges, HasFactory, HasReportingCategory, LogsActivity, SoftDeletes;

    public function getActivityLabelField(): string
    {
        return 'name';
    }

    public static function getActivityModelName(): string
    {
        return 'Combo';
    }

    protected $fillable = [
        'category_id',
        'name',
        'description',
        'image',
        'precio_pickup_capital',
        'precio_domicilio_capital',
        'precio_pickup_interior',
        'precio_domicilio_interior',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'category_id' => 'integer',
            'precio_pickup_capital' => 'decimal:2',
            'precio_domicilio_capital' => 'decimal:2',
            'precio_pickup_interior' => 'decimal:2',
            'precio_domicilio_interior' => 'decimal:2',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * Relación: Un combo pertenece a una categoría
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Relación: Un combo tiene muchos items
     */
    public function items(): HasMany
    {
        return $this->hasMany(ComboItem::class)->orderBy('sort_order');
    }

    /**
     * Relación: Un combo tiene muchos productos (via items)
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'combo_items')
            ->withPivot('quantity', 'variant_id', 'sort_order')
            ->withTimestamps()
            ->orderByPivot('sort_order');
    }

    /**
     * Scope: Combos activos
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Combos disponibles (activos + validación de disponibilidad de items)
     */
    public function scopeAvailable($query)
    {
        return $query->active()
            ->where(function ($q) {
                // Items fijos: todos activos
                $q->whereDoesntHave('items', function ($itemQuery) {
                    $itemQuery->where('is_choice_group', false)
                        ->whereHas('product', function ($productQuery) {
                            $productQuery->where('is_active', false);
                        });
                })
                // Grupos de elección: al menos 1 opción activa
                    ->whereDoesntHave('items', function ($itemQuery) {
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
     * Scope: Ordenar por configuración
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('is_active', 'desc')
            ->orderBy('sort_order', 'asc')
            ->orderBy('created_at', 'desc');
    }

    /**
     * Scope: Combos disponibles con relaciones cargadas para mostrar advertencias
     */
    public function scopeAvailableWithWarnings($query)
    {
        return $query->active()
            ->with([
                'items.options.product',
                'items.product',
            ]);
    }

    /**
     * Verifica si el combo está disponible
     * (activo + validación de disponibilidad de items)
     */
    public function isAvailable(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        // Validar cada item del combo
        foreach ($this->items as $item) {
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

    /**
     * Obtiene el número de opciones inactivas en grupos de elección
     */
    public function getInactiveOptionsCount(): int
    {
        return $this->items()
            ->where('is_choice_group', true)
            ->with('options.product')
            ->get()
            ->sum(function ($item) {
                return $item->options->filter(function ($option) {
                    return ! $option->product->is_active;
                })->count();
            });
    }

    /**
     * Obtiene el precio para una zona y tipo de servicio
     */
    public function getPriceForZone(string $zone, string $serviceType): float
    {
        $field = match ([$zone, $serviceType]) {
            ['capital', 'pickup'] => 'precio_pickup_capital',
            ['capital', 'delivery'] => 'precio_domicilio_capital',
            ['interior', 'pickup'] => 'precio_pickup_interior',
            ['interior', 'delivery'] => 'precio_domicilio_interior',
            default => 'precio_pickup_capital',
        };

        return (float) $this->$field;
    }

    /**
     * Accessor: Categoría de reportería (siempre "combos")
     */
    public function getReportingCategoryAttribute(): string
    {
        return 'combos';
    }

    /**
     * Scope: Filtrar por categoría de reportería
     */
    public function scopeByReportingCategory($query, string $category)
    {
        // Los combos siempre son "combos", si piden otra categoría retorna vacío
        if ($category !== 'combos') {
            return $query->whereRaw('1 = 0');
        }

        return $query;
    }
}
