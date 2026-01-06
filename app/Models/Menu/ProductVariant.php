<?php

namespace App\Models\Menu;

use App\Models\Concerns\HasReportingCategory;
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
class ProductVariant extends Model
{
    use HasFactory, HasReportingCategory, SoftDeletes;

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
        'is_redeemable',
        'points_cost',
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
        'is_redeemable' => 'boolean',
        'points_cost' => 'integer',
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
}
