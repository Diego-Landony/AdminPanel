<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Restaurant Panel Settings
    |--------------------------------------------------------------------------
    |
    | Configuraciones para el panel de restaurantes
    |
    */

    // Intervalo de polling para nuevas órdenes (en segundos)
    'polling_interval' => (int) env('RESTAURANT_POLLING_INTERVAL', 15),

    // Auto-imprimir nuevas órdenes cuando llegan
    'auto_print_new_orders' => env('RESTAURANT_AUTO_PRINT_NEW_ORDERS', true),
];
