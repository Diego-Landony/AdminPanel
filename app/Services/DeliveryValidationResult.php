<?php

namespace App\Services;

use App\Models\Restaurant;

readonly class DeliveryValidationResult
{
    public function __construct(
        public bool $isValid,
        public ?Restaurant $restaurant,
        public ?string $zone,
        public array $nearbyPickupRestaurants = [],
        public ?string $errorMessage = null
    ) {}
}
