<?php

use App\Models\BadgeType;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\CustomerDevice;
use App\Models\CustomerNit;
use App\Models\CustomerType;
use App\Models\LegalDocument;
use App\Models\Menu\Category;
use App\Models\Menu\Combo;
use App\Models\Menu\Product;
use App\Models\Menu\ProductVariant;
use App\Models\Menu\Promotion;
use App\Models\Menu\Section;
use App\Models\PointsSetting;
use App\Models\PromotionalBanner;
use App\Models\Restaurant;
use App\Models\Role;
use App\Models\SupportReason;
use App\Models\SupportTicket;
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
        BadgeType::class => [
            'name' => 'Tipo de badge',
            'icon' => 'badge',
        ],
        ProductVariant::class => [
            'name' => 'Variante de producto',
            'icon' => 'box',
        ],
        PromotionalBanner::class => [
            'name' => 'Banner',
            'icon' => 'image',
        ],
        SupportTicket::class => [
            'name' => 'Ticket de soporte',
            'icon' => 'ticket',
        ],
        SupportReason::class => [
            'name' => 'Razón de soporte',
            'icon' => 'help-circle',
        ],
        LegalDocument::class => [
            'name' => 'Documento legal',
            'icon' => 'file-text',
        ],
        CustomerAddress::class => [
            'name' => 'Dirección de cliente',
            'icon' => 'map-pin',
        ],
        CustomerNit::class => [
            'name' => 'NIT de cliente',
            'icon' => 'file',
        ],
        CustomerDevice::class => [
            'name' => 'Dispositivo de cliente',
            'icon' => 'smartphone',
        ],
        PointsSetting::class => [
            'name' => 'Configuración de puntos',
            'icon' => 'settings',
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
        'reordered' => 'Reordenado',
        'badges_updated' => 'Badges actualizados',

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
        'roles_updated' => 'Roles actualizados',

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

        'badge_type_created' => 'Tipo de badge creado',
        'badge_type_updated' => 'Tipo de badge actualizado',
        'badge_type_deleted' => 'Tipo de badge eliminado',
        'badge_type_restored' => 'Tipo de badge restaurado',
        'badge_type_force_deleted' => 'Tipo de badge eliminado permanentemente',

        'product_variant_created' => 'Variante de producto creada',
        'product_variant_updated' => 'Variante de producto actualizada',
        'product_variant_deleted' => 'Variante de producto eliminada',
        'product_variant_restored' => 'Variante de producto restaurada',
        'product_variant_force_deleted' => 'Variante de producto eliminada permanentemente',

        'promotional_banner_created' => 'Banner creado',
        'promotional_banner_updated' => 'Banner actualizado',
        'promotional_banner_deleted' => 'Banner eliminado',
        'promotional_banner_restored' => 'Banner restaurado',
        'promotional_banner_force_deleted' => 'Banner eliminado permanentemente',

        'support_ticket_created' => 'Ticket de soporte creado',
        'support_ticket_updated' => 'Ticket de soporte actualizado',
        'support_ticket_deleted' => 'Ticket de soporte eliminado',
        'support_ticket_restored' => 'Ticket de soporte restaurado',
        'support_ticket_force_deleted' => 'Ticket de soporte eliminado permanentemente',

        'support_reason_created' => 'Razón de soporte creada',
        'support_reason_updated' => 'Razón de soporte actualizada',
        'support_reason_deleted' => 'Razón de soporte eliminada',
        'support_reason_restored' => 'Razón de soporte restaurada',
        'support_reason_force_deleted' => 'Razón de soporte eliminada permanentemente',

        'legal_document_created' => 'Documento legal creado',
        'legal_document_updated' => 'Documento legal actualizado',
        'legal_document_deleted' => 'Documento legal eliminado',
        'legal_document_restored' => 'Documento legal restaurado',
        'legal_document_force_deleted' => 'Documento legal eliminado permanentemente',

        'customer_address_created' => 'Dirección de cliente creada',
        'customer_address_updated' => 'Dirección de cliente actualizada',
        'customer_address_deleted' => 'Dirección de cliente eliminada',
        'customer_address_restored' => 'Dirección de cliente restaurada',
        'customer_address_force_deleted' => 'Dirección de cliente eliminada permanentemente',

        'customer_nit_created' => 'NIT de cliente creado',
        'customer_nit_updated' => 'NIT de cliente actualizado',
        'customer_nit_deleted' => 'NIT de cliente eliminado',
        'customer_nit_restored' => 'NIT de cliente restaurado',
        'customer_nit_force_deleted' => 'NIT de cliente eliminado permanentemente',

        'customer_device_created' => 'Dispositivo de cliente creado',
        'customer_device_updated' => 'Dispositivo de cliente actualizado',
        'customer_device_deleted' => 'Dispositivo de cliente eliminado',
        'customer_device_restored' => 'Dispositivo de cliente restaurado',
        'customer_device_force_deleted' => 'Dispositivo de cliente eliminado permanentemente',

        'points_setting_created' => 'Configuración de puntos creada',
        'points_setting_updated' => 'Configuración de puntos actualizada',
        'points_setting_deleted' => 'Configuración de puntos eliminada',
        'points_setting_restored' => 'Configuración de puntos restaurada',
        'points_setting_force_deleted' => 'Configuración de puntos eliminada permanentemente',
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
        'reordered' => 'purple',
        'badges_updated' => 'purple',
        'roles_updated' => 'purple',
        'login' => 'green',
        'logout' => 'blue',
    ],
];
