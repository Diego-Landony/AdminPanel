<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Configuración de Páginas y Permisos
    |--------------------------------------------------------------------------
    |
    | Este archivo define todas las páginas del sistema y sus acciones permitidas.
    | Los permisos se generan automáticamente basándose en esta configuración.
    |
    | Formato: '{page}.{action}' donde action puede ser: view, create, edit, delete
    |
    */

    'pages' => [
        // ==================== PÁGINAS PRINCIPALES ====================

        'home' => [
            'display_name' => 'Inicio',
            'description' => 'Página principal después del login',
            'actions' => ['view'],
        ],

        'dashboard' => [
            'display_name' => 'Dashboard',
            'description' => 'Panel principal del sistema',
            'actions' => ['view'],
        ],

        // ==================== GESTIÓN DE USUARIOS ====================

        'users' => [
            'display_name' => 'Usuarios',
            'description' => 'Gestión de usuarios del sistema',
            'actions' => ['view', 'create', 'edit', 'delete'],
        ],

        'roles' => [
            'display_name' => 'Roles y Permisos',
            'description' => 'Gestión de roles y permisos del sistema',
            'actions' => ['view', 'create', 'edit', 'delete'],
        ],

        // ==================== GESTIÓN DE CLIENTES ====================

        'customers' => [
            'display_name' => 'Clientes',
            'description' => 'Gestión de clientes del sistema',
            'actions' => ['view', 'create', 'edit', 'delete'],
        ],

        'customer-types' => [
            'display_name' => 'Tipos de Cliente',
            'description' => 'Gestión de tipos de cliente',
            'actions' => ['view', 'create', 'edit', 'delete'],
        ],

        // ==================== GESTIÓN DE RESTAURANTES ====================

        'restaurants' => [
            'display_name' => 'Restaurantes',
            'description' => 'Gestión de restaurantes y sucursales',
            'actions' => ['view', 'create', 'edit', 'delete'],
        ],

        // ==================== MÓDULO DE MENÚ ====================

        'menu.categories' => [
            'display_name' => 'Categorías',
            'description' => 'Categorías del menú',
            'actions' => ['view', 'create', 'edit', 'delete'],
        ],

        'menu.products' => [
            'display_name' => 'Productos',
            'description' => 'Productos del menú',
            'actions' => ['view', 'create', 'edit', 'delete'],
        ],

        'menu.sections' => [
            'display_name' => 'Secciones',
            'description' => 'Secciones de personalización',
            'actions' => ['view', 'create', 'edit', 'delete'],
        ],

        'menu.combos' => [
            'display_name' => 'Combos',
            'description' => 'Combos del menú',
            'actions' => ['view', 'create', 'edit', 'delete'],
        ],

        'menu.promotions' => [
            'display_name' => 'Promociones',
            'description' => 'Promociones (2x1, descuentos por porcentaje, sub del día)',
            'actions' => ['view', 'create', 'edit', 'delete'],
        ],

        // ==================== ACTIVIDAD ====================

        'activity' => [
            'display_name' => 'Actividad',
            'description' => 'Logs de actividad del sistema',
            'actions' => ['view'],
        ],

        // ==================== CONFIGURACIÓN ====================

        'settings' => [
            'display_name' => 'Configuración',
            'description' => 'Configuración de perfil y contraseña',
            'actions' => ['view', 'edit'],
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Acciones Base
    |--------------------------------------------------------------------------
    |
    | Nombres legibles para cada tipo de acción en español
    |
    */

    'actions' => [
        'view' => 'Ver',
        'create' => 'Crear',
        'edit' => 'Editar',
        'delete' => 'Eliminar',
    ],
];
