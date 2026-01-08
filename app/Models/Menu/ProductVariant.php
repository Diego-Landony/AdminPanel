<?php

namespace App\Models\Menu;

use App\Contracts\ActivityLoggable;
use App\Models\Concerns\HasReportingCategory;
use App\Models\Concerns\LogsActivity;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Modelo de Variante de Producto
 *
 * Representa una variante específica de un producto con SKU, tamaño y precios propios.
 * Ejemplo: "Subway Pollo 15cm", "Subway Pollo 30cm"
 * Cada variante tiene 4 precios: pickup_capital, domicilio_capital, pickup_interior, domicilio_interior
 */
class ProductVariant extends Model implements ActivityLoggable
{
    use HasFactory, HasReportingCategory, LogsActivity, SoftDeletes;

    public function getActivityLabelField(): string
    {
        return 'name';
    }

    public static function getActivityModelName(): string
    {
        return 'Variante de producto';
    }

    /**
     * Nombre de la tabla
     *
     * @var string
     */
    protected $table = 'product_variants';

    /**
     * Los atributos que se pueden asignar masivamente
     *
     * @var array<string>
     */
    protected $fillable = [
        'product_id',
        'sku',
        'name',
        'size',
        'precio_pickup_capital',
        'precio_domicilio_capital',
        'precio_pickup_interior',
        'precio_domicilio_interior',
        'is_daily_special',
        'daily_special_days',
        'daily_special_precio_pickup_capital',
        'daily_special_precio_domicilio_capital',
        'daily_special_precio_pickup_interior',
        'daily_special_precio_domicilio_interior',
        'is_active',
        'sort_order',
    ];

    /**
     * Los atributos que deben ser casteados
     *
     * @var array<string, string>
     */
    protected $casts = [
        'product_id' => 'integer',
        'precio_pickup_capital' => 'decimal:2',
        'precio_domicilio_capital' => 'decimal:2',
        'precio_pickup_interior' => 'decimal:2',
        'precio_domicilio_interior' => 'decimal:2',
        'is_daily_special' => 'boolean',
        'daily_special_days' => 'array',
        'daily_special_precio_pickup_capital' => 'decimal:2',
        'daily_special_precio_domicilio_capital' => 'decimal:2',
        'daily_special_precio_pickup_interior' => 'decimal:2',
        'daily_special_precio_domicilio_interior' => 'decimal:2',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Relación: Una variante pertenece a un producto
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Relación: Una variante puede estar en múltiples promociones
     */
    public function promotions(): BelongsToMany
    {
        return $this->belongsToMany(
            Promotion::class,
            'promotion_items',
            'variant_id',
            'promotion_id'
        )->where('is_active', true);
    }

    /**
     * Scope: Variantes activas
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Variantes ordenadas
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }

    /**
     * Scope: Variantes que son Sub del Día para un día específico
     *
     * @param  int  $dayOfWeek  0=Domingo, 1=Lunes, ..., 6=Sábado
     */
    public function scopeDailySpecialForDay($query, int $dayOfWeek)
    {
        return $query->where('is_daily_special', true)
            ->whereJsonContains('daily_special_days', $dayOfWeek);
    }

    /**
     * Obtiene el precio especial según el tipo de precio
     *
     * @param  string  $priceType  'precio_pickup_capital', 'precio_domicilio_capital', etc.
     */
    public function getDailySpecialPrice(string $priceType): ?float
    {
        return match ($priceType) {
            'precio_pickup_capital' => $this->daily_special_precio_pickup_capital,
            'precio_domicilio_capital' => $this->daily_special_precio_domicilio_capital,
            'precio_pickup_interior' => $this->daily_special_precio_pickup_interior,
            'precio_domicilio_interior' => $this->daily_special_precio_domicilio_interior,
            default => null,
        };
    }

    /**
     * Obtiene el precio normal según el tipo de precio
     *
     * @param  string  $priceType  'precio_pickup_capital', 'precio_domicilio_capital', etc.
     */
    public function getPrice(string $priceType): float
    {
        return match ($priceType) {
            'precio_pickup_capital' => $this->precio_pickup_capital,
            'precio_domicilio_capital' => $this->precio_domicilio_capital,
            'precio_pickup_interior' => $this->precio_pickup_interior,
            'precio_domicilio_interior' => $this->precio_domicilio_interior,
            default => 0.0,
        };
    }

    /**
     * Accessor: Categoría de reportería derivada
     *
     * Para variantes de subs: usa el size (30cm, 15cm)
     * Para otras variantes: hereda de la categoría del producto
     */
    public function getReportingCategoryAttribute(): string
    {
        // Si tiene size y es 30cm/15cm, usar directamente
        if ($this->size && in_array($this->size, ['30cm', '15cm'])) {
            return $this->size;
        }

        // Heredar de la categoría del producto
        $product = $this->relationLoaded('product') ? $this->product : $this->product()->with('category')->first();

        return $this->deriveReportingCategoryFromMenuCategory($product?->category?->name);
    }

    /**
     * Scope: Filtrar por categoría de reportería
     */
    public function scopeByReportingCategory($query, string $category)
    {
        if (in_array($category, ['30cm', '15cm'])) {
            return $query->where('size', $category);
        }

        // Para otras categorías, filtrar por categoría del producto
        $map = array_flip(static::getReportingCategoryMap());
        $menuCategoryName = $map[$category] ?? null;

        if ($menuCategoryName) {
            return $query->whereHas('product.category', function ($q) use ($menuCategoryName) {
                $q->where('name', $menuCategoryName);
            });
        }

        return $query;
    }

    /**
     * Obtiene la promoción activa aplicable a esta variante en este momento.
     * Busca en orden de prioridad: variante específica > producto padre > categoría
     *
     * @param  string|null  $serviceType  'pickup', 'delivery' o null para cualquier tipo
     * @param  Carbon|null  $datetime  Fecha/hora para validar, null para ahora
     */
    public function getActivePromotion(?string $serviceType = null, ?Carbon $datetime = null): ?Promotion
    {
        $datetime = $datetime ?? now();
        $currentDate = $datetime->toDateString();
        $currentTime = $datetime->format('H:i:s');
        $currentWeekday = $datetime->dayOfWeekIso; // 1 (lunes) a 7 (domingo)

        // Cargar producto con categoría si no está cargado
        $product = $this->relationLoaded('product')
            ? $this->product
            : $this->product()->with('category')->first();

        $categoryId = $product?->category_id;

        // Buscar promociones activas que apliquen a esta variante
        $promotions = Promotion::query()
            ->where('is_active', true)
            // Filtrar por fechas
            ->where(function ($q) use ($currentDate) {
                $q->whereNull('valid_from')
                    ->orWhere('valid_from', '<=', $currentDate);
            })
            ->where(function ($q) use ($currentDate) {
                $q->whereNull('valid_until')
                    ->orWhere('valid_until', '>=', $currentDate);
            })
            // Filtrar por horarios
            ->where(function ($q) use ($currentTime) {
                $q->whereNull('time_from')
                    ->orWhere('time_from', '<=', $currentTime);
            })
            ->where(function ($q) use ($currentTime) {
                $q->whereNull('time_until')
                    ->orWhere('time_until', '>=', $currentTime);
            })
            // Filtrar por días de la semana
            ->where(function ($q) use ($currentWeekday) {
                $q->whereNull('weekdays')
                    ->orWhereJsonContains('weekdays', $currentWeekday);
            })
            // Buscar items que apliquen a: variante, producto o categoría
            ->where(function ($q) use ($categoryId) {
                $q->whereHas('items', fn ($q2) => $q2->where('variant_id', $this->id))
                    ->orWhereHas('items', fn ($q2) => $q2->where('product_id', $this->product_id))
                    ->orWhereHas('items', fn ($q2) => $q2->where('category_id', $categoryId));
            })
            // Cargar relaciones necesarias
            ->with(['items' => function ($q) use ($categoryId) {
                $q->where(function ($q2) use ($categoryId) {
                    $q2->where('variant_id', $this->id)
                        ->orWhere('product_id', $this->product_id)
                        ->orWhere('category_id', $categoryId);
                });
            }])
            ->orderBy('sort_order')
            ->get();

        // Evaluar cada promoción y sus items en orden de prioridad
        foreach ($promotions as $promotion) {
            $applicableItem = $this->findApplicablePromotionItem($promotion, $serviceType, $datetime);

            if ($applicableItem) {
                return $promotion;
            }
        }

        return null;
    }

    /**
     * Obtiene el PromotionItem correspondiente a esta variante dentro de una promoción.
     * Busca en orden de prioridad: variante específica > producto padre > categoría
     */
    public function getPromotionItem(Promotion $promotion): ?PromotionItem
    {
        // Cargar producto con categoría si no está cargado
        $product = $this->relationLoaded('product')
            ? $this->product
            : $this->product()->with('category')->first();

        $categoryId = $product?->category_id;

        // Cargar items si no están cargados
        $items = $promotion->relationLoaded('items')
            ? $promotion->items
            : $promotion->items()->get();

        // Prioridad 1: Item específico para esta variante
        $variantItem = $items->firstWhere('variant_id', $this->id);
        if ($variantItem) {
            return $variantItem;
        }

        // Prioridad 2: Item para el producto padre
        $productItem = $items->first(function ($item) {
            return $item->product_id === $this->product_id && $item->variant_id === null;
        });
        if ($productItem) {
            return $productItem;
        }

        // Prioridad 3: Item para la categoría
        $categoryItem = $items->first(function ($item) use ($categoryId) {
            return $item->category_id === $categoryId
                && $item->product_id === null
                && $item->variant_id === null;
        });

        return $categoryItem;
    }

    /**
     * Busca el PromotionItem aplicable dentro de una promoción,
     * validando tipo de servicio y vigencia temporal.
     */
    protected function findApplicablePromotionItem(
        Promotion $promotion,
        ?string $serviceType,
        ?Carbon $datetime
    ): ?PromotionItem {
        $datetime = $datetime ?? now();

        // Cargar producto con categoría si no está cargado
        $product = $this->relationLoaded('product')
            ? $this->product
            : $this->product()->with('category')->first();

        $categoryId = $product?->category_id;

        // Obtener items relevantes
        $items = $promotion->relationLoaded('items')
            ? $promotion->items
            : $promotion->items()->get();

        // Filtrar items que aplican a esta variante/producto/categoría
        $relevantItems = $items->filter(function ($item) use ($categoryId) {
            return $item->variant_id === $this->id
                || ($item->product_id === $this->product_id && $item->variant_id === null)
                || ($item->category_id === $categoryId && $item->product_id === null && $item->variant_id === null);
        });

        // Ordenar por prioridad: variante > producto > categoría
        $sortedItems = $relevantItems->sortBy(function ($item) use ($categoryId) {
            if ($item->variant_id === $this->id) {
                return 1;
            }
            if ($item->product_id === $this->product_id && $item->variant_id === null) {
                return 2;
            }
            if ($item->category_id === $categoryId) {
                return 3;
            }

            return 4;
        });

        // Buscar el primer item válido
        foreach ($sortedItems as $item) {
            // Validar tipo de servicio
            if ($serviceType && ! $item->appliesToServiceType($serviceType)) {
                continue;
            }

            // Validar vigencia temporal del item
            if (! $item->isValidToday($datetime)) {
                continue;
            }

            return $item;
        }

        return null;
    }
}
