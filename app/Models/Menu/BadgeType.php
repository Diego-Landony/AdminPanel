<?php

namespace App\Models\Menu;

use App\Contracts\ActivityLoggable;
use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BadgeType extends Model implements ActivityLoggable
{
    use HasFactory, LogsActivity;

    public function getActivityLabelField(): string
    {
        return 'name';
    }

    public static function getActivityModelName(): string
    {
        return 'Tipo de badge';
    }

    protected $fillable = [
        'name',
        'color',
        'text_color',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function productBadges(): HasMany
    {
        return $this->hasMany(ProductBadge::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }
}
