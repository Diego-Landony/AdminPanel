<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class PromotionalBanner extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'description',
        'image',
        'orientation',
        'display_seconds',
        'sort_order',
        'link_type',
        'link_id',
        'link_url',
        'validity_type',
        'valid_from',
        'valid_until',
        'time_from',
        'time_until',
        'weekdays',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'display_seconds' => 'integer',
            'sort_order' => 'integer',
            'link_id' => 'integer',
            'valid_from' => 'date',
            'valid_until' => 'date',
            'weekdays' => 'array',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the image URL.
     */
    public function getImageUrl(): ?string
    {
        return $this->image ? Storage::url($this->image) : null;
    }

    /**
     * Scope: only active banners.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: filter by orientation.
     */
    public function scopeOrientation($query, string $orientation)
    {
        return $query->where('orientation', $orientation);
    }

    /**
     * Scope: ordered by sort_order.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    /**
     * Check if current time is within time range.
     */
    private function isWithinTimeRange(): bool
    {
        if (! $this->time_from && ! $this->time_until) {
            return true;
        }

        $currentTime = now()->format('H:i:s');

        if ($this->time_from && $currentTime < $this->time_from) {
            return false;
        }

        if ($this->time_until && $currentTime > $this->time_until) {
            return false;
        }

        return true;
    }

    /**
     * Scope: only banners valid right now (active + within date/weekday/time range).
     */
    public function scopeValidNow($query)
    {
        $today = now()->toDateString();
        $currentTime = now()->format('H:i:s');
        $currentWeekday = (int) now()->format('N'); // 1=Monday, 7=Sunday

        return $query->active()
            ->where(function ($q) use ($today, $currentTime, $currentWeekday) {
                // Permanent (with optional time restriction)
                $q->where(function ($q2) use ($currentTime) {
                    $q2->where('validity_type', 'permanent')
                        ->where(function ($q3) use ($currentTime) {
                            $q3->whereNull('time_from')
                                ->orWhere('time_from', '<=', $currentTime);
                        })
                        ->where(function ($q3) use ($currentTime) {
                            $q3->whereNull('time_until')
                                ->orWhere('time_until', '>=', $currentTime);
                        });
                })
                    // Or valid date range (with optional time restriction)
                    ->orWhere(function ($q2) use ($today, $currentTime) {
                        $q2->where('validity_type', 'date_range')
                            ->where(function ($q3) use ($today) {
                                $q3->whereNull('valid_from')
                                    ->orWhere('valid_from', '<=', $today);
                            })
                            ->where(function ($q3) use ($today) {
                                $q3->whereNull('valid_until')
                                    ->orWhere('valid_until', '>=', $today);
                            })
                            ->where(function ($q3) use ($currentTime) {
                                $q3->whereNull('time_from')
                                    ->orWhere('time_from', '<=', $currentTime);
                            })
                            ->where(function ($q3) use ($currentTime) {
                                $q3->whereNull('time_until')
                                    ->orWhere('time_until', '>=', $currentTime);
                            });
                    })
                    // Or weekdays (with optional time restriction)
                    ->orWhere(function ($q2) use ($currentWeekday, $currentTime) {
                        $q2->where('validity_type', 'weekdays')
                            ->whereJsonContains('weekdays', (string) $currentWeekday)
                            ->where(function ($q3) use ($currentTime) {
                                $q3->whereNull('time_from')
                                    ->orWhere('time_from', '<=', $currentTime);
                            })
                            ->where(function ($q3) use ($currentTime) {
                                $q3->whereNull('time_until')
                                    ->orWhere('time_until', '>=', $currentTime);
                            });
                    });
            });
    }

    /**
     * Check if the banner is valid right now.
     */
    public function isValidNow(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        // Check time restriction first (applies to all validity types)
        if (! $this->isWithinTimeRange()) {
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

    /**
     * Get link data for the API response.
     */
    public function getLinkData(): ?array
    {
        if (! $this->link_type || $this->link_type === 'none') {
            return null;
        }

        if ($this->link_type === 'url') {
            return [
                'type' => 'url',
                'url' => $this->link_url,
            ];
        }

        return [
            'type' => $this->link_type,
            'id' => $this->link_id,
        ];
    }
}
