<?php

namespace App\Models\Menu;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo de Item de Promoción
 *
 * Tabla pivot entre promociones y productos/categorías.
 * Solo uno de product_id o category_id debe tener valor (XOR).
 */
class PromotionItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'promotion_id',
        'product_id',
        'category_id',
    ];

    protected $casts = [
        'promotion_id' => 'integer',
        'product_id' => 'integer',
        'category_id' => 'integer',
    ];

    /**
     * Relación: Un item pertenece a una promoción
     */
    public function promotion(): BelongsTo
    {
        return $this->belongsTo(Promotion::class);
    }

    /**
     * Relación: Un item puede pertenecer a un producto
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Relación: Un item puede pertenecer a una categoría
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
