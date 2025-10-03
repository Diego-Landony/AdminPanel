<?php

namespace App\Models\Menu;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Section extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'is_required',
        'allow_multiple',
        'min_selections',
        'max_selections',
        'sort_order',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'allow_multiple' => 'boolean',
        'min_selections' => 'integer',
        'max_selections' => 'integer',
        'sort_order' => 'integer',
    ];

    public function options(): HasMany
    {
        return $this->hasMany(SectionOption::class)->orderBy('sort_order');
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_sections')
            ->withPivot('sort_order')
            ->withTimestamps();
    }
}
