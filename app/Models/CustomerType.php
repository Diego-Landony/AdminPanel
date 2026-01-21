<?php

namespace App\Models;

use App\Contracts\ActivityLoggable;
use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CustomerType extends Model implements ActivityLoggable
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'name',
        'points_required',
        'multiplier',
        'color',
        'is_active',
    ];

    protected $casts = [
        'points_required' => 'integer',
        'multiplier' => 'decimal:2',
        'is_active' => 'boolean',
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
        return $query->orderBy('points_required')->orderBy('name');
    }

    public static function getTypeForPoints(int $points): ?self
    {
        return static::active()
            ->where('points_required', '<=', $points)
            ->orderBy('points_required', 'desc')
            ->first();
    }

    public static function getDefault(): ?self
    {
        return static::active()
            ->orderBy('points_required')
            ->first();
    }

    /**
     * Get information about the next tier for a customer.
     *
     * @return array{id: int, name: string, points_required: int, points_needed: int, multiplier: float, color: string|null}|null
     */
    public static function getNextTierInfo(Customer $customer): ?array
    {
        $currentPoints = $customer->points ?? 0;
        $currentTier = $customer->customerType;

        $nextTier = static::query()
            ->where('is_active', true)
            ->where('points_required', '>', $currentTier?->points_required ?? 0)
            ->orderBy('points_required')
            ->first();

        if (! $nextTier) {
            return null;
        }

        return [
            'id' => $nextTier->id,
            'name' => $nextTier->name,
            'points_required' => $nextTier->points_required,
            'points_needed' => max(0, $nextTier->points_required - $currentPoints),
            'multiplier' => (float) $nextTier->multiplier,
            'color' => $nextTier->color,
        ];
    }

    /**
     * Nombre del modelo para los logs de actividad
     */
    public static function getActivityModelName(): string
    {
        return 'Tipo de Cliente';
    }
}
