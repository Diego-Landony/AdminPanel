<?php

namespace App\Models;

use App\Models\Menu\Combo;
use App\Models\Menu\Product;
use App\Models\Menu\ProductVariant;
use App\Models\Menu\SectionOption;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CartItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'cart_id',
        'product_id',
        'variant_id',
        'combo_id',
        'quantity',
        'unit_price',
        'subtotal',
        'selected_options',
        'combo_selections',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'cart_id' => 'integer',
            'product_id' => 'integer',
            'variant_id' => 'integer',
            'combo_id' => 'integer',
            'quantity' => 'integer',
            'unit_price' => 'decimal:2',
            'subtotal' => 'decimal:2',
            'selected_options' => 'array',
            'combo_selections' => 'array',
        ];
    }

    /**
     * Relación: Un item pertenece a un carrito
     */
    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    /**
     * Relación: Un item puede ser un producto
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Relación: Un item puede ser una variante
     */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }

    /**
     * Relación: Un item puede ser un combo
     */
    public function combo(): BelongsTo
    {
        return $this->belongsTo(Combo::class);
    }

    /**
     * Verifica si el item es un producto
     */
    public function isProduct(): bool
    {
        return $this->product_id !== null && $this->combo_id === null;
    }

    /**
     * Verifica si el item es un combo
     */
    public function isCombo(): bool
    {
        return $this->combo_id !== null;
    }

    /**
     * Calcula el total de las opciones seleccionadas (extras).
     * Obtiene los precios desde la DB usando SectionOption::getPriceModifier().
     * Solo los extras (is_extra = true) tienen precio adicional.
     */
    public function getOptionsTotal(): float
    {
        if (! $this->selected_options || ! is_array($this->selected_options)) {
            return 0.0;
        }

        $optionIds = collect($this->selected_options)
            ->pluck('option_id')
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        if (empty($optionIds)) {
            return 0.0;
        }

        // Batch load opciones para obtener precios reales
        $sectionOptions = SectionOption::whereIn('id', $optionIds)->get()->keyBy('id');

        $total = 0.0;
        foreach ($this->selected_options as $option) {
            $optionId = $option['option_id'] ?? null;
            if ($optionId) {
                $sectionOption = $sectionOptions[$optionId] ?? null;
                // getPriceModifier() retorna el precio solo si is_extra = true
                $total += $sectionOption?->getPriceModifier() ?? 0;
            }
        }

        return round($total, 2);
    }

    /**
     * Calcula el total de la línea (subtotal + opciones)
     */
    public function getLineTotal(): float
    {
        return (float) $this->subtotal + $this->getOptionsTotal();
    }

    /**
     * Obtiene el nombre del item
     */
    public function getName(): string
    {
        if ($this->isCombo()) {
            return $this->combo?->name ?? 'Combo';
        }

        if ($this->variant) {
            return "{$this->product?->name} - {$this->variant->name}";
        }

        return $this->product?->name ?? 'Producto';
    }
}
