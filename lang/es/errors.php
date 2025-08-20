<?php

return [
    '404' => [
        'title' => 'Página No Encontrada',
        'message' => 'Página no existe.',
        'description' => 'La página fue movida o eliminada.',
        'back_home' => 'Volver al Inicio',
        'search' => 'Buscar en el sitio',
        'contact_support' => 'Contactar Soporte',
    ],

    '403' => [
        'title' => 'Acceso Prohibido',
        'message' => 'Sin permisos para acceder.',
        'description' => 'Contacta al administrador.',
        'back_home' => 'Volver al Inicio',
        'contact_admin' => 'Contactar Administrador',
    ],

    '500' => [
        'title' => 'Error del Servidor',
        'message' => 'Error interno.',
        'description' => 'Inténtalo más tarde.',
        'back_home' => 'Volver al Inicio',
        'refresh' => 'Actualizar Página',
        'contact_support' => 'Contactar Soporte',
    ],

    '419' => [
        'title' => 'Página Expirada',
        'message' => 'La página ha expirado debido a inactividad.',
        'description' => 'Por favor, actualiza la página e inténtalo de nuevo.',
        'refresh' => 'Actualizar Página',
        'back_home' => 'Volver al Inicio',
    ],

    '429' => [
        'title' => 'Demasiadas Solicitudes',
        'message' => 'Has enviado demasiadas solicitudes.',
        'description' => 'Por favor, espera un momento antes de intentarlo de nuevo.',
        'wait' => 'Esperar',
        'back_home' => 'Volver al Inicio',
    ],

    '503' => [
        'title' => 'Servicio No Disponible',
        'message' => 'El servicio no está disponible en este momento.',
        'description' => 'Estamos realizando mantenimiento. Por favor, inténtalo más tarde.',
        'back_home' => 'Volver al Inicio',
        'contact_support' => 'Contactar Soporte',
    ],

    'maintenance' => [
        'title' => 'Sitio en Mantenimiento',
        'message' => 'Estamos realizando mejoras en nuestro sitio.',
        'description' => 'Estaremos de vuelta pronto. Gracias por tu paciencia.',
        'estimated_time' => 'Tiempo estimado: :time',
        'contact_support' => 'Contactar Soporte',
    ],

    'general' => [
        'title' => 'Error',
        'message' => 'Algo salió mal.',
        'description' => 'Error inesperado.',
        'back_home' => 'Volver al Inicio',
        'refresh' => 'Actualizar Página',
        'contact_support' => 'Contactar Soporte',
        'try_again' => 'Intentar de Nuevo',
        'go_back' => 'Volver Atrás',
    ],

    'validation' => [
        'title' => 'Error de Validación',
        'message' => 'Por favor, corrige los errores en el formulario.',
        'description' => 'Algunos campos no cumplen con los requisitos.',
        'fix_errors' => 'Corregir Errores',
        'try_again' => 'Intentar de Nuevo',
    ],

    'authentication' => [
        'title' => 'Error de Autenticación',
        'message' => 'Sin permisos.',
        'description' => 'Inicia sesión.',
        'login' => 'Iniciar Sesión',
        'contact_admin' => 'Contactar Administrador',
    ],

    'database' => [
        'title' => 'Error de Base de Datos',
        'message' => 'Error de base de datos.',
        'description' => 'Inténtalo más tarde.',
        'try_again' => 'Intentar de Nuevo',
        'contact_support' => 'Contactar Soporte',
    ],

    'file' => [
        'title' => 'Error de Archivo',
        'message' => 'Error al procesar archivo.',
        'description' => 'Archivo corrupto o muy grande.',
        'try_again' => 'Intentar de Nuevo',
        'choose_other' => 'Elegir Otro',
    ],

    'network' => [
        'title' => 'Error de Red',
        'message' => 'Error de conexión.',
        'description' => 'Verifica tu internet.',
        'check_connection' => 'Verificar Conexión',
        'try_again' => 'Intentar de Nuevo',
    ],

    'timeout' => [
        'title' => 'Tiempo Agotado',
        'message' => 'Operación tardó mucho.',
        'description' => 'Inténtalo de nuevo.',
        'try_again' => 'Intentar de Nuevo',
        'contact_support' => 'Contactar Soporte',
    ],

    'memory' => [
        'title' => 'Error de Memoria',
        'message' => 'Memoria insuficiente.',
        'description' => 'Reduce datos o contacta soporte.',
        'reduce_data' => 'Reducir Datos',
        'contact_support' => 'Contactar Soporte',
    ],

    'permission' => [
        'title' => 'Error de Permisos',
        'message' => 'Sin permisos.',
        'description' => 'Contacta al administrador.',
        'contact_admin' => 'Contactar Administrador',
        'back_home' => 'Volver al Inicio',
    ],

    'quota' => [
        'title' => 'Cuota Excedida',
        'message' => 'Cuota excedida.',
        'description' => 'Contacta al administrador.',
        'contact_admin' => 'Contactar Administrador',
        'upgrade_plan' => 'Actualizar Plan',
    ],

    'service_unavailable' => [
        'title' => 'Servicio No Disponible',
        'message' => 'Servicio no disponible.',
        'description' => 'Inténtalo más tarde.',
        'try_again' => 'Intentar de Nuevo',
        'contact_support' => 'Contactar Soporte',
    ],

    'bad_gateway' => [
        'title' => 'Puerta de Enlace Incorrecta',
        'message' => 'Error de comunicación.',
        'description' => 'Inténtalo más tarde.',
        'try_again' => 'Intentar de Nuevo',
        'contact_support' => 'Contactar Soporte',
    ],

    'gateway_timeout' => [
        'title' => 'Tiempo de Puerta de Enlace',
        'message' => 'Servidor no respondió.',
        'description' => 'Inténtalo más tarde.',
        'try_again' => 'Intentar de Nuevo',
        'contact_support' => 'Contactar Soporte',
    ],

    'http_version_not_supported' => [
        'title' => 'Versión HTTP No Soportada',
        'message' => 'La versión del protocolo HTTP no es compatible.',
        'description' => 'Actualiza tu navegador o contacta al soporte.',
        'update_browser' => 'Actualizar Navegador',
        'contact_support' => 'Contactar Soporte',
    ],

    'insufficient_storage' => [
        'title' => 'Almacenamiento Insuficiente',
        'message' => 'Sin espacio disponible.',
        'description' => 'Libera espacio.',
        'free_space' => 'Liberar Espacio',
        'contact_admin' => 'Contactar Administrador',
    ],

    'loop_detected' => [
        'title' => 'Bucle Detectado',
        'message' => 'Bucle infinito detectado.',
        'description' => 'Contacta soporte.',
        'contact_support' => 'Contactar Soporte',
        'back_home' => 'Volver al Inicio',
    ],

    'not_extended' => [
        'title' => 'No Extendido',
        'message' => 'Extensión requerida.',
        'description' => 'Contacta soporte.',
        'contact_support' => 'Contactar Soporte',
        'back_home' => 'Volver al Inicio',
    ],

    'network_authentication_required' => [
        'title' => 'Autenticación de Red Requerida',
        'message' => 'Se requiere autenticación de red.',
        'description' => 'Contacta al administrador de red.',
        'contact_network_admin' => 'Contactar Administrador de Red',
        'back_home' => 'Volver al Inicio',
    ],
];
