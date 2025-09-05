<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CustomerType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'display_name',
        'points_required',
        'multiplier',
        'color',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'points_required' => 'integer',
        'multiplier' => 'decimal:2',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class, 'customer_type_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('points_required');
    }

    public static function getTypeForPoints(int $points): ?self
    {
        return static::active()
            ->where('points_required', '<=', $points)
            ->orderBy('points_required', 'desc')
            ->first();
    }
}
