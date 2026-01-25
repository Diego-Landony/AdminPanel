<?php

namespace App\Models\Menu;

use App\Traits\InvalidatesMenuVersion;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SectionOption extends Model
{
    use HasFactory, InvalidatesMenuVersion;

    protected $fillable = [
        'section_id',
        'name',
        'is_extra',
        'price_modifier',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_extra' => 'boolean',
            'price_modifier' => 'decimal:2',
            'sort_order' => 'integer',
        ];
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    /**
     * Obtiene el modificador de precio
     * Solo aplica si is_extra = true
     */
    public function getPriceModifier(): float
    {
        return $this->is_extra ? (float) $this->price_modifier : 0.0;
    }
}
