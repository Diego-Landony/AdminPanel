<?php

namespace App\Exceptions\Delivery;

class InvalidKmlException extends \Exception
{
    public function __construct(
        public readonly int $restaurantId,
        string $message = 'El KML de la geocerca es inválido'
    ) {
        parent::__construct($message);
    }
}
