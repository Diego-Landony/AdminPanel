<?php

namespace App\Models\Menu;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BundlePromotionItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'promotion_id',
        'product_id',
        'variant_id',
        'quantity',
        'sort_order',
        'is_choice_group',
        'choice_label',
    ];

    protected function casts(): array
    {
        return [
            'promotion_id' => 'integer',
            'product_id' => 'integer',
            'variant_id' => 'integer',
            'quantity' => 'integer',
            'sort_order' => 'integer',
            'is_choice_group' => 'boolean',
        ];
    }

    /**
     * Relación: Un item pertenece a una promoción
     */
    public function promotion(): BelongsTo
    {
        return $this->belongsTo(Promotion::class);
    }

    /**
     * Relación: Un item referencia a un producto
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Relación: Un item puede referenciar una variante específica
     */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    /**
     * Relación: Un item de tipo choice group tiene múltiples opciones
     */
    public function options(): HasMany
    {
        return $this->hasMany(BundlePromotionItemOption::class, 'bundle_item_id')->orderBy('sort_order');
    }

    /**
     * Verifica si este item es un grupo de elección
     */
    public function isChoiceGroup(): bool
    {
        return $this->is_choice_group === true;
    }

    /**
     * Obtiene el producto con todas sus secciones cargadas
     */
    public function getProductWithSections(): ?Product
    {
        if ($this->isChoiceGroup()) {
            return null;
        }

        return $this->product()->with('sections.options')->first();
    }
}
