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

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }
}
