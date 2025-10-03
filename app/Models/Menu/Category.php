<?php

namespace App\Models\Menu;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'is_active',
        'sort_order',
        'uses_variants',
        'variant_definitions',
    ];

    protected $casts = [
        'is_active' => 'boolean',
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
}
