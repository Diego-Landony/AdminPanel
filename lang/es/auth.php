<?php

return [
    'failed' => 'Credenciales incorrectas.',
    'password' => 'Contraseña incorrecta.',
    'throttle' => 'Demasiados intentos. Inténtalo en :seconds segundos.',

    'login' => [
        'title' => 'Iniciar Sesión',
        'subtitle' => 'Ingresa tus credenciales',
        'email' => 'Correo Electrónico',
        'password' => 'Contraseña',
        'remember' => 'Recordar sesión',
        'submit' => 'Iniciar Sesión',
        'forgot_password' => '¿Olvidaste tu contraseña?',
        'no_account' => '¿No tienes cuenta? Regístrate',
    ],

    'register' => [
        'title' => 'Registro',
        'subtitle' => 'Crea una nueva cuenta',
        'name' => 'Nombre Completo',
        'email' => 'Correo Electrónico',
        'password' => 'Contraseña',
        'password_confirmation' => 'Confirmar Contraseña',
        'submit' => 'Registrarse',
        'already_account' => '¿Ya tienes cuenta? Inicia sesión',
        'success' => 'Cuenta creada exitosamente.',
    ],

    'password' => [
        'reset' => [
            'title' => 'Restablecer Contraseña',
            'subtitle' => 'Ingresa tu correo electrónico',
            'email' => 'Correo Electrónico',
            'submit' => 'Enviar Enlace',
            'sent' => 'Enlace enviado a tu correo.',
            'reset' => 'Contraseña restablecida.',
            'token' => 'Token inválido.',
        ],
        'confirm' => [
            'title' => 'Confirmar Contraseña',
            'subtitle' => 'Confirma tu contraseña',
            'password' => 'Contraseña',
            'submit' => 'Confirmar',
            'required' => 'Confirma tu contraseña.',
        ],
        'update' => [
            'success' => 'Contraseña actualizada.',
            'failed' => 'Error al actualizar.',
        ],
    ],

    'verification' => [
        'title' => 'Verificar Correo',
        'subtitle' => 'Verifica tu correo electrónico',
        'resend' => 'Reenviar Email',
        'sent' => 'Email enviado.',
        'verified' => 'Correo verificado.',
        'already_verified' => 'Ya verificado.',
    ],

    'logout' => [
        'success' => 'Sesión cerrada.',
        'failed' => 'Error al cerrar.',
    ],

    'profile' => [
        'update' => [
            'success' => 'Perfil actualizado.',
            'failed' => 'Error al actualizar.',
        ],
        'delete' => [
            'success' => 'Cuenta eliminada.',
            'failed' => 'Error al eliminar.',
            'confirm' => '¿Eliminar cuenta? No se puede deshacer.',
        ],
    ],

    'errors' => [
        'unauthorized' => 'Acceso no autorizado.',
        'forbidden' => 'Acceso prohibido.',
        'not_found' => 'Página no encontrada.',
        'server_error' => 'Error del servidor.',
        'maintenance' => 'Sitio en mantenimiento.',
        'too_many_requests' => 'Demasiadas solicitudes.',
        'session_expired' => 'Sesión expirada.',
        'invalid_credentials' => 'Credenciales inválidas.',
        'account_locked' => 'Cuenta bloqueada.',
        'email_not_verified' => 'Correo no verificado.',
        'password_expired' => 'Contraseña expirada.',
    ],

    'messages' => [
        'welcome' => 'Bienvenido.',
        'goodbye' => 'Hasta luego.',
        'login_success' => 'Sesión iniciada.',
        'logout_success' => 'Sesión cerrada.',
        'password_changed' => 'Contraseña cambiada.',
        'profile_updated' => 'Perfil actualizado.',
        'email_verified' => 'Correo verificado.',
        'verification_sent' => 'Email enviado.',
        'password_reset_sent' => 'Email enviado.',
        'password_reset_success' => 'Contraseña restablecida.',
    ],
];
