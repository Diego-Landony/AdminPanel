<?php

namespace App\Models\Menu;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'is_active',
        'is_combo_category',
        'sort_order',
        'uses_variants',
        'variant_definitions',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_combo_category' => 'boolean',
        'sort_order' => 'integer',
        'uses_variants' => 'boolean',
        'variant_definitions' => 'array',
    ];

    /**
     * Relación N:N: Una categoría puede tener múltiples productos
     *
     * Si uses_variants = false: Los precios están en el pivot
     * Si uses_variants = true: Los precios están en product_variants
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'category_product')
            ->withPivot([
                'sort_order',
                'precio_pickup_capital',
                'precio_domicilio_capital',
                'precio_pickup_interior',
                'precio_domicilio_interior',
            ])
            ->withTimestamps()
            ->orderByPivot('sort_order');
    }

    /**
     * Scope para filtrar solo categorías activas
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope para ordenar categorías por sort_order y luego por nombre
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /**
     * Scope para filtrar solo categorías de combos
     */
    public function scopeComboCategories($query)
    {
        return $query->where('is_combo_category', true);
    }

    /**
     * Relación 1:N: Una categoría puede tener múltiples combos
     */
    public function combos(): HasMany
    {
        return $this->hasMany(Combo::class);
    }
}
