<?php

namespace App\Models\Menu;

use App\Traits\InvalidatesMenuVersion;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ProductBadge extends Model
{
    use HasFactory, InvalidatesMenuVersion;

    protected $fillable = [
        'badge_type_id',
        'badgeable_type',
        'badgeable_id',
        'validity_type',
        'valid_from',
        'valid_until',
        'weekdays',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'badge_type_id' => 'integer',
            'valid_from' => 'date',
            'valid_until' => 'date',
            'weekdays' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function badgeType(): BelongsTo
    {
        return $this->belongsTo(BadgeType::class);
    }

    public function badgeable(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Filtra badges válidos ahora (activos + dentro del rango de fechas o día de semana)
     */
    public function scopeValidNow($query)
    {
        $today = now()->toDateString();
        $currentWeekday = (int) now()->format('N'); // 1=Lunes, 7=Domingo

        return $query->active()
            ->where(function ($q) use ($today, $currentWeekday) {
                // Permanente
                $q->where('validity_type', 'permanent')
                    // O rango de fechas válido
                    ->orWhere(function ($q2) use ($today) {
                        $q2->where('validity_type', 'date_range')
                            ->where(function ($q3) use ($today) {
                                $q3->whereNull('valid_from')
                                    ->orWhere('valid_from', '<=', $today);
                            })
                            ->where(function ($q3) use ($today) {
                                $q3->whereNull('valid_until')
                                    ->orWhere('valid_until', '>=', $today);
                            });
                    })
                    // O días de la semana
                    ->orWhere(function ($q2) use ($currentWeekday) {
                        $q2->where('validity_type', 'weekdays')
                            ->whereJsonContains('weekdays', $currentWeekday);
                    });
            });
    }

    /**
     * Verifica si el badge es válido ahora
     */
    public function isValidNow(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if ($this->validity_type === 'permanent') {
            return true;
        }

        if ($this->validity_type === 'weekdays') {
            $currentWeekday = (int) now()->format('N');

            return is_array($this->weekdays) && in_array($currentWeekday, $this->weekdays);
        }

        // date_range
        $today = now()->toDateString();

        if ($this->valid_from && $this->valid_from->toDateString() > $today) {
            return false;
        }

        if ($this->valid_until && $this->valid_until->toDateString() < $today) {
            return false;
        }

        return true;
    }
}
