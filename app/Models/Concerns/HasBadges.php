<?php

namespace App\Models\Concerns;

use App\Models\Menu\ProductBadge;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasBadges
{
    public function badges(): MorphMany
    {
        return $this->morphMany(ProductBadge::class, 'badgeable');
    }

    public function activeBadges(): MorphMany
    {
        return $this->badges()->validNow()->with('badgeType');
    }

    public function hasBadge(int $badgeTypeId): bool
    {
        return $this->activeBadges()->where('badge_type_id', $badgeTypeId)->exists();
    }

    public function addBadge(
        int $badgeTypeId,
        string $validityType = 'permanent',
        ?string $validFrom = null,
        ?string $validUntil = null,
        ?array $weekdays = null
    ): ProductBadge {
        return $this->badges()->updateOrCreate(
            ['badge_type_id' => $badgeTypeId],
            [
                'validity_type' => $validityType,
                'valid_from' => $validFrom,
                'valid_until' => $validUntil,
                'weekdays' => $weekdays,
                'is_active' => true,
            ]
        );
    }

    public function removeBadge(int $badgeTypeId): void
    {
        $this->badges()->where('badge_type_id', $badgeTypeId)->delete();
    }

    public function syncBadges(array $badgesData): void
    {
        // Eliminar badges no incluidos
        $badgeTypeIds = collect($badgesData)->pluck('badge_type_id')->filter()->toArray();
        $this->badges()->whereNotIn('badge_type_id', $badgeTypeIds)->delete();

        // Crear o actualizar badges
        foreach ($badgesData as $badgeData) {
            if (! isset($badgeData['badge_type_id'])) {
                continue;
            }

            $this->addBadge(
                $badgeData['badge_type_id'],
                $badgeData['validity_type'] ?? 'permanent',
                $badgeData['valid_from'] ?? null,
                $badgeData['valid_until'] ?? null,
                $badgeData['weekdays'] ?? null
            );
        }
    }
}
