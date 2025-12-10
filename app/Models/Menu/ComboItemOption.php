<?php

namespace App\Models\Menu;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ComboItemOption extends Model
{
    protected $fillable = [
        'combo_item_id',
        'product_id',
        'variant_id',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'combo_item_id' => 'integer',
            'product_id' => 'integer',
            'variant_id' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function comboItem(): BelongsTo
    {
        return $this->belongsTo(ComboItem::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * La variante asociada con esta opción de grupo de elección.
     *
     * Cuando variant_id es NULL:
     * - El producto asociado NO tiene variantes (has_variants = false).
     *   Ejemplo: En un grupo de elección de bebidas, donde cada opción es un producto simple.
     *   Los precios se toman del producto directamente.
     *
     * Cuando variant_id NO es NULL:
     * - El producto asociado tiene variantes (has_variants = true).
     *   Ejemplo: En un grupo de elección de subs, donde todas las opciones deben ser de la misma variante (15cm o 30cm).
     *   Los precios se toman de product_variants.
     *
     * Validación importante: Todas las opciones de un mismo grupo deben ser consistentes:
     * - Todas con variant_id NULL, o todas con variant_id NOT NULL.
     * - Si tienen variantes, todas deben ser de la misma variante (mismo tamaño).
     */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }
}
