<?php

namespace App\Models;

use App\Contracts\ActivityLoggable;
use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupportReason extends Model implements ActivityLoggable
{
    use LogsActivity;

    public function getActivityLabelField(): string
    {
        return 'name';
    }

    public static function getActivityModelName(): string
    {
        return 'Razón de soporte';
    }

    protected $fillable = [
        'name',
        'slug',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    // Scopes

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    // Relationships

    public function tickets(): HasMany
    {
        return $this->hasMany(SupportTicket::class);
    }
}
