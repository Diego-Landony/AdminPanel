<?php

namespace App\Models\Menu;

use App\Contracts\ActivityLoggable;
use App\Models\Concerns\HasBadges;
use App\Models\Concerns\HasReportingCategory;
use App\Models\Concerns\LogsActivity;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Modelo de Producto Base
 *
 * Representa la información general de un producto.
 * Los precios se definen en las variantes (ProductVariant) o directamente en el producto.
 * La relación con categoría es N:1 (products.category_id).
 */
class Product extends Model implements ActivityLoggable
{
    use HasBadges, HasFactory, HasReportingCategory, LogsActivity, SoftDeletes;

    public function getActivityLabelField(): string
    {
        return 'name';
    }

    public static function getActivityModelName(): string
    {
        return 'Producto';
    }

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
        'is_redeemable',
        'points_cost',
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
        'is_redeemable' => 'boolean',
        'points_cost' => 'integer',
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
     * Scope: Productos ordenados por sort_order, luego por nombre
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /**
     * Accessor: Un producto es personalizable si tiene secciones
     */
    public function getIsCustomizableAttribute(): bool
    {
        return $this->sections()->count() > 0;
    }

    /**
     * Obtiene la URL completa de la imagen del producto
     * Maneja correctamente las rutas que ya contienen /storage/
     */
    public function getImageUrl(): ?string
    {
        if (! $this->image) {
            return null;
        }

        // Si la imagen ya es una URL completa, devolverla tal cual
        if (str_starts_with($this->image, 'http://') || str_starts_with($this->image, 'https://')) {
            return $this->image;
        }

        // Si ya tiene /storage/ al inicio, construir URL sin duplicar
        if (str_starts_with($this->image, '/storage/')) {
            return $this->image;
        }

        // Si tiene storage/ sin slash inicial
        if (str_starts_with($this->image, 'storage/')) {
            return '/'.$this->image;
        }

        // Caso normal: usar Storage::url()
        return \Illuminate\Support\Facades\Storage::url($this->image);
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

    /**
     * Obtiene la promoción activa aplicable a este producto en este momento.
     * Considera: fecha, hora, días de la semana, tipo de servicio.
     *
     * @param  string|null  $serviceType  'pickup', 'delivery' o null para cualquiera
     */
    public function getActivePromotion(?string $serviceType = null, ?Carbon $datetime = null): ?Promotion
    {
        $datetime = $datetime ?? now();
        $currentDate = $datetime->toDateString();
        $currentTime = $datetime->format('H:i:s');
        $currentWeekday = $datetime->dayOfWeekIso; // 1=Lunes, 7=Domingo (ISO-8601)

        $query = Promotion::query()
            ->where('is_active', true)
            // Buscar en promotion_items donde product_id = $this->id O category_id = $this->category_id
            ->whereHas('items', function ($q) use ($serviceType) {
                $q->where(function ($q2) {
                    $q2->where('product_id', $this->id)
                        ->orWhere('category_id', $this->category_id);
                });

                // Filtrar por service_type si se proporciona
                if ($serviceType) {
                    $q->where(function ($q2) use ($serviceType) {
                        $q2->whereNull('service_type')
                            ->orWhere('service_type', 'both')
                            ->orWhere('service_type', $serviceType === 'pickup' ? 'pickup_only' : 'delivery_only');
                    });
                }
            })
            // Validar fechas: valid_from <= now
            ->where(function ($q) use ($currentDate) {
                $q->whereNull('valid_from')
                    ->orWhereDate('valid_from', '<=', $currentDate);
            })
            // Validar fechas: now <= valid_until
            ->where(function ($q) use ($currentDate) {
                $q->whereNull('valid_until')
                    ->orWhereDate('valid_until', '>=', $currentDate);
            })
            // Validar horas: time_from <= now
            ->where(function ($q) use ($currentTime) {
                $q->whereNull('time_from')
                    ->orWhereTime('time_from', '<=', $currentTime);
            })
            // Validar horas: now <= time_until
            ->where(function ($q) use ($currentTime) {
                $q->whereNull('time_until')
                    ->orWhereTime('time_until', '>=', $currentTime);
            })
            // Validar días: weekday actual está en array weekdays
            ->where(function ($q) use ($currentWeekday) {
                $q->whereNull('weekdays')
                    ->orWhereJsonContains('weekdays', $currentWeekday);
            })
            // Ordenar por sort_order y retornar la primera
            ->orderBy('sort_order')
            // Cargar relaciones
            ->with(['items']);

        return $query->first();
    }

    /**
     * Obtiene el PromotionItem correspondiente a este producto de una promoción
     */
    public function getPromotionItem(Promotion $promotion): ?PromotionItem
    {
        // Primero buscar por product_id específico
        $item = $promotion->items()
            ->where('product_id', $this->id)
            ->first();

        if ($item) {
            return $item;
        }

        // Si no hay item específico, buscar por category_id
        return $promotion->items()
            ->where('category_id', $this->category_id)
            ->whereNull('product_id')
            ->first();
    }
}
