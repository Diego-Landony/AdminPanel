<?php

return [
    'reset' => 'Contraseña restablecida.',
    'sent' => 'Enlace enviado a tu correo.',
    'throttled' => 'Espera antes de intentar.',
    'token' => 'Token inválido.',
    'user' => 'Usuario no encontrado.',

    'email' => [
        'subject' => 'Restablecer Contraseña',
        'greeting' => 'Hola :name,',
        'line_1' => 'Solicitud de restablecimiento recibida.',
        'line_2' => 'Haz clic para restablecer:',
        'action' => 'Restablecer Contraseña',
        'line_3' => 'Enlace expira en :count minutos.',
        'line_4' => 'Si no solicitaste esto, ignora este email.',
        'salutation' => 'Saludos,',
        'app_name' => config('app.name'),
    ],

    'reset_form' => [
        'title' => 'Restablecer Contraseña',
        'subtitle' => 'Ingresa tu nueva contraseña',
        'email' => 'Correo Electrónico',
        'password' => 'Nueva Contraseña',
        'password_confirmation' => 'Confirmar Contraseña',
        'submit' => 'Restablecer',
        'back_to_login' => 'Volver al Login',
    ],

    'request_form' => [
        'title' => 'Restablecer Contraseña',
        'subtitle' => 'Ingresa tu correo electrónico',
        'email' => 'Correo Electrónico',
        'submit' => 'Enviar Enlace',
        'back_to_login' => 'Volver al Login',
        'success' => 'Enlace enviado.',
    ],

    'validation' => [
        'email_required' => 'Correo obligatorio.',
        'email_email' => 'Correo inválido.',
        'password_required' => 'Contraseña obligatoria.',
        'password_confirmed' => 'Contraseñas no coinciden.',
        'password_min' => 'Mínimo :min caracteres.',
        'token_required' => 'Token obligatorio.',
        'token_invalid' => 'Token inválido o expirado.',
    ],

    'errors' => [
        'invalid_token' => 'Token inválido o expirado.',
        'user_not_found' => 'Usuario no encontrado.',
        'password_already_reset' => 'Ya restablecida.',
        'too_many_attempts' => 'Demasiados intentos.',
        'email_not_sent' => 'Error al enviar.',
    ],

    'success' => [
        'email_sent' => 'Enlace enviado.',
        'password_reset' => 'Contraseña restablecida.',
        'password_changed' => 'Contraseña cambiada.',
    ],
];
