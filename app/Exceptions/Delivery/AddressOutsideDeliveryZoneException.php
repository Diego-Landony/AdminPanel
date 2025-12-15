<?php

namespace App\Exceptions\Delivery;

class AddressOutsideDeliveryZoneException extends \Exception
{
    public function __construct(
        public readonly float $lat,
        public readonly float $lng,
        string $message = 'La dirección está fuera de todas las zonas de entrega'
    ) {
        parent::__construct($message);
    }
}
