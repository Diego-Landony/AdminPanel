<?php

namespace App\Models\Menu;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Modelo de Producto Base
 *
 * Representa la información general de un producto SIN precios.
 * Los precios se definen en las variantes (ProductVariant).
 * La relación con categorías es N:N a través de la tabla pivot category_product.
 */
class Product extends Model
{
    use HasFactory;

    /**
     * Los atributos que se pueden asignar masivamente
     *
     * @var array<string>
     */
    protected $fillable = [
        'category_id',
        'name',
        'slug',
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
     * Boot del modelo
     */
    protected static function boot(): void
    {
        parent::boot();

        // Generar slug automáticamente al crear
        static::creating(function ($product) {
            if (empty($product->slug)) {
                $product->slug = Str::slug($product->name);
            }
        });
    }

    /**
     * Relación: Un producto pertenece a una categoría
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Relación N:N: Un producto puede estar en múltiples categorías (legacy)
     *
     * El pivot contiene precios si la categoría NO usa variantes
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'category_product')
            ->withPivot(['sort_order'])
            ->withTimestamps()
            ->orderByPivot('sort_order');
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
}
