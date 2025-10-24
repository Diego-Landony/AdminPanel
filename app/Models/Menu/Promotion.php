<?php

namespace App\Models\Menu;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Promotion extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'type',
        'is_active',
    ];

    protected $casts = [
        'type' => 'string',
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
     * NOTA: La lógica de vigencia temporal ahora está en PromotionItem::isValidToday()
     * Este scope solo verifica que la promoción esté activa.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  \Illuminate\Support\Carbon|null  $datetime
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActiveNow($query, $datetime = null)
    {
        return $query->where('is_active', true);
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
     *
     * NOTA: La vigencia temporal específica ahora está en los items individuales.
     * Este método solo verifica que la promoción esté activa y que al menos
     * un item sea válido en este momento.
     */
    public function isValidNow(?\Carbon\Carbon $datetime = null): bool
    {
        $datetime = $datetime ?? now();

        // Debe estar activa
        if (! $this->is_active) {
            return false;
        }

        // Verificar que al menos un item sea válido ahora
        return $this->items()->get()->contains(function ($item) use ($datetime) {
            return $item->isValidToday($datetime);
        });
    }
}
