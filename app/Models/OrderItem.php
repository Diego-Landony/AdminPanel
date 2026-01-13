<?php

namespace App\Models;

use App\Models\Menu\Combo;
use App\Models\Menu\Product;
use App\Models\Menu\ProductVariant;
use App\Models\Menu\Promotion;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'product_id',
        'variant_id',
        'combo_id',
        'promotion_id',
        'promotion_snapshot',
        'product_snapshot',
        'quantity',
        'unit_price',
        'options_price',
        'subtotal',
        'selected_options',
        'combo_selections',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'product_snapshot' => 'array',
            'promotion_snapshot' => 'array',
            'selected_options' => 'array',
            'combo_selections' => 'array',
            'unit_price' => 'decimal:2',
            'options_price' => 'decimal:2',
            'subtotal' => 'decimal:2',
            'quantity' => 'integer',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function combo(): BelongsTo
    {
        return $this->belongsTo(Combo::class);
    }

    public function promotion(): BelongsTo
    {
        return $this->belongsTo(Promotion::class)->withTrashed();
    }

    public function isProduct(): bool
    {
        return ! is_null($this->product_id) && is_null($this->combo_id);
    }

    public function isCombo(): bool
    {
        return ! is_null($this->combo_id);
    }

    public function getLineTotal(): float
    {
        return (float) $this->subtotal;
    }

    /**
     * Obtiene el total de extras por unidad.
     * Retorna el valor almacenado en options_price.
     */
    public function getOptionsTotal(): float
    {
        return (float) ($this->options_price ?? 0);
    }
}
