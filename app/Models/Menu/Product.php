<?php

namespace App\Models\Menu;

use App\Models\Concerns\HasBadges;
use App\Models\Concerns\HasReportingCategory;
use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Modelo de Producto Base
 *
 * Representa la información general de un producto.
 * Los precios se definen en las variantes (ProductVariant) o directamente en el producto.
 * La relación con categoría es N:1 (products.category_id).
 */
class Product extends Model
{
    use HasBadges, HasFactory, HasReportingCategory, LogsActivity;

    /**
     * Los atributos que se pueden asignar masivamente
     *
     * @var array<string>
     */
    protected $fillable = [
        'category_id',
        'name',
        'description',
        'image',
        'is_active',
        'has_variants',
        'precio_pickup_capital',
        'precio_domicilio_capital',
        'precio_pickup_interior',
        'precio_domicilio_interior',
        'sort_order',
    ];

    /**
     * Los atributos que deben ser casteados
     *
     * @var array<string, string>
     */
    protected $casts = [
        'category_id' => 'integer',
        'is_active' => 'boolean',
        'has_variants' => 'boolean',
        'precio_pickup_capital' => 'decimal:2',
        'precio_domicilio_capital' => 'decimal:2',
        'precio_pickup_interior' => 'decimal:2',
        'precio_domicilio_interior' => 'decimal:2',
        'sort_order' => 'integer',
    ];

    /**
     * Los atributos que deben ser agregados al array/JSON del modelo
     *
     * @var array<string>
     */
    protected $appends = [
        'is_customizable',
    ];

    /**
     * Relación: Un producto pertenece a una categoría
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Relación 1:N: Un producto tiene múltiples variantes
     */
    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class)->orderBy('sort_order');
    }

    /**
     * Relación: Un producto pertenece a múltiples secciones de personalización
     */
    public function sections(): BelongsToMany
    {
        return $this->belongsToMany(Section::class, 'product_sections')
            ->withPivot('sort_order')
            ->withTimestamps()
            ->orderByPivot('sort_order');
    }

    /**
     * Relación: Un producto puede estar en múltiples promociones
     */
    public function promotions(): BelongsToMany
    {
        return $this->belongsToMany(Promotion::class, 'promotion_items')
            ->where('is_active', true);
    }

    /**
     * Relación inversa: Un producto puede estar en muchos combos
     */
    public function combos(): BelongsToMany
    {
        return $this->belongsToMany(Combo::class, 'combo_items')
            ->withPivot('quantity', 'variant_id', 'sort_order')
            ->withTimestamps();
    }

    /**
     * Scope: Productos activos
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Productos ordenados por nombre
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('name');
    }

    /**
     * Accessor: Un producto es personalizable si tiene secciones
     */
    public function getIsCustomizableAttribute(): bool
    {
        return $this->sections()->count() > 0;
    }

    /**
     * Accessor: has_variants se calcula automáticamente desde la categoría
     * Si el producto tiene categoría, usa uses_variants de la categoría
     * Si no tiene categoría, usa el valor directo de has_variants (legacy)
     */
    public function getHasVariantsAttribute($value): bool
    {
        if ($this->category_id && $this->relationLoaded('category') && $this->category) {
            return (bool) ($this->category->uses_variants ?? false);
        }

        return (bool) ($value ?? false);
    }

    /**
     * Verifica si el producto está en algún combo activo
     */
    public function isInActiveCombos(): bool
    {
        return $this->combos()->where('is_active', true)->exists();
    }

    /**
     * Accessor: Categoría de reportería derivada de la categoría de menú
     */
    public function getReportingCategoryAttribute(): string
    {
        $category = $this->relationLoaded('category') ? $this->category : $this->category()->first();

        return $this->deriveReportingCategoryFromMenuCategory($category?->name);
    }

    /**
     * Scope: Filtrar por categoría de reportería
     */
    public function scopeByReportingCategory($query, string $category)
    {
        $map = array_flip(static::getReportingCategoryMap());
        $menuCategoryName = $map[$category] ?? null;

        if ($menuCategoryName) {
            return $query->whereHas('category', function ($q) use ($menuCategoryName) {
                $q->where('name', $menuCategoryName);
            });
        }

        return $query;
    }
}
