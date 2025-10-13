<?php

namespace App\Models\Menu;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Combo extends Model
{
    use HasFactory, SoftDeletes;

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
     * Scope: Combos disponibles (activos + todos productos activos)
     */
    public function scopeAvailable($query)
    {
        return $query->active()
            ->whereDoesntHave('products', function ($q) {
                $q->where('is_active', false);
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
     * Verifica si el combo está disponible
     * (activo + todos los productos activos)
     */
    public function isAvailable(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        // Verificar que TODOS los productos estén activos
        return $this->products()->where('is_active', false)->doesntExist();
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
}
