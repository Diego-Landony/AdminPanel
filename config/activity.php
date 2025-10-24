<?php

use App\Models\Customer;
use App\Models\CustomerType;
use App\Models\Menu\Category;
use App\Models\Menu\Combo;
use App\Models\Menu\Product;
use App\Models\Menu\Promotion;
use App\Models\Menu\Section;
use App\Models\Restaurant;
use App\Models\Role;
use App\Models\User;

return [
    /*
    |--------------------------------------------------------------------------
    | Configuración de Modelos
    |--------------------------------------------------------------------------
    |
    | Nombres y configuración para cada modelo que registra actividad
    |
    */
    'models' => [
        User::class => [
            'name' => 'Usuario',
            'icon' => 'user',
        ],
        Role::class => [
            'name' => 'Rol',
            'icon' => 'shield',
        ],
        Customer::class => [
            'name' => 'Cliente',
            'icon' => 'user-circle',
        ],
        CustomerType::class => [
            'name' => 'Tipo de cliente',
            'icon' => 'star',
        ],
        Restaurant::class => [
            'name' => 'Restaurante',
            'icon' => 'utensils',
        ],
        Category::class => [
            'name' => 'Categoría',
            'icon' => 'layers',
        ],
        Section::class => [
            'name' => 'Sección',
            'icon' => 'list-checks',
        ],
        Product::class => [
            'name' => 'Producto',
            'icon' => 'package',
        ],
        Combo::class => [
            'name' => 'Combo',
            'icon' => 'package-2',
        ],
        Promotion::class => [
            'name' => 'Promoción',
            'icon' => 'percent',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Traducciones de Tipos de Eventos
    |--------------------------------------------------------------------------
    |
    | Traducciones al español de los tipos de eventos
    |
    */
    'event_types' => [
        // Eventos comunes
        'created' => 'Creado',
        'updated' => 'Actualizado',
        'deleted' => 'Eliminado',
        'restored' => 'Restaurado',
        'force_deleted' => 'Eliminado permanentemente',

        // Eventos de autenticación
        'login' => 'Inicio de sesión',
        'logout' => 'Cierre de sesión',

        // Eventos específicos (legacy)
        'user_created' => 'Usuario creado',
        'user_updated' => 'Usuario actualizado',
        'user_deleted' => 'Usuario eliminado',
        'user_restored' => 'Usuario restaurado',
        'user_force_deleted' => 'Usuario eliminado permanentemente',

        'role_created' => 'Rol creado',
        'role_updated' => 'Rol actualizado',
        'role_deleted' => 'Rol eliminado',
        'role_restored' => 'Rol restaurado',
        'role_force_deleted' => 'Rol eliminado permanentemente',
        'role_users_updated' => 'Usuarios de rol actualizados',

        'customer_created' => 'Cliente creado',
        'customer_updated' => 'Cliente actualizado',
        'customer_deleted' => 'Cliente eliminado',
        'customer_restored' => 'Cliente restaurado',
        'customer_force_deleted' => 'Cliente eliminado permanentemente',

        'customer_type_created' => 'Tipo de cliente creado',
        'customer_type_updated' => 'Tipo de cliente actualizado',
        'customer_type_deleted' => 'Tipo de cliente eliminado',
        'customer_type_restored' => 'Tipo de cliente restaurado',
        'customer_type_force_deleted' => 'Tipo de cliente eliminado permanentemente',

        'restaurant_created' => 'Restaurante creado',
        'restaurant_updated' => 'Restaurante actualizado',
        'restaurant_deleted' => 'Restaurante eliminado',
        'restaurant_restored' => 'Restaurante restaurado',
        'restaurant_force_deleted' => 'Restaurante eliminado permanentemente',
    ],

    /*
    |--------------------------------------------------------------------------
    | Traducciones de Campos
    |--------------------------------------------------------------------------
    |
    | Traducciones de nombres de campos para descripciones legibles
    |
    */
    'field_translations' => [
        'name' => 'nombre',
        'title' => 'título',
        'email' => 'email',
        'description' => 'descripción',
        'is_active' => 'estado',
        'is_required' => 'requerido',
        'allow_multiple' => 'permite múltiples',
        'min_selections' => 'selecciones mínimas',
        'max_selections' => 'selecciones máximas',
        'sort_order' => 'orden',
        'price' => 'precio',
        'precio_pickup_capital' => 'precio pickup capital',
        'precio_domicilio_capital' => 'precio domicilio capital',
        'precio_pickup_interior' => 'precio pickup interior',
        'precio_domicilio_interior' => 'precio domicilio interior',
        'image' => 'imagen',
        'address' => 'dirección',
        'phone' => 'teléfono',
        'is_combo_category' => 'es categoría de combos',
        'uses_variants' => 'usa variantes',
        'has_variants' => 'tiene variantes',
    ],

    /*
    |--------------------------------------------------------------------------
    | Colores por Tipo de Evento (para frontend)
    |--------------------------------------------------------------------------
    */
    'event_colors' => [
        'created' => 'green',
        'updated' => 'blue',
        'deleted' => 'red',
        'restored' => 'yellow',
        'force_deleted' => 'red',
        'login' => 'green',
        'logout' => 'blue',
    ],
];
