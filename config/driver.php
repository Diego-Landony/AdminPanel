<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Driver Settings
    |--------------------------------------------------------------------------
    |
    | Configuraciones para los motoristas/drivers
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Delivery Location Validation
    |--------------------------------------------------------------------------
    |
    | Distancia máxima permitida (en metros) para completar una entrega.
    | El motorista debe estar dentro de esta distancia del destino para
    | poder marcar la orden como entregada.
    |
    */
    'max_delivery_distance_meters' => (int) env('DRIVER_MAX_DELIVERY_DISTANCE', 100),
];
