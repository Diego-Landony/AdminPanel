<?php

return [
    // Autenticación básica
    'failed' => 'Credenciales incorrectas.',
    'password' => 'La contraseña es incorrecta.',
    'throttle' => 'Demasiados intentos de acceso. Inténtalo de nuevo en :seconds segundos.',

    // Mensajes de éxito
    'register_success' => 'Registro exitoso. Por favor verifica tu email.',
    'login_success' => 'Inicio de sesión exitoso.',
    'logout_success' => 'Sesión cerrada exitosamente.',
    'logout_all_success' => 'Se cerraron todas las sesiones exitosamente.',
    'token_refreshed' => 'Token renovado exitosamente.',
    'password_reset_link_sent' => 'Enlace de restablecimiento de contraseña enviado.',
    'password_reset_success' => 'Contraseña restablecida exitosamente.',
    'email_verified' => 'Email verificado exitosamente.',
    'email_already_verified' => 'El email ya ha sido verificado.',
    'verification_link_resent' => 'Enlace de verificación reenviado.',

    // Errores de credenciales
    'invalid_credentials' => 'Las credenciales proporcionadas son incorrectas.',
    'oauth_account' => 'Esta cuenta usa autenticación con :provider. Por favor inicia sesión con :provider.',
    'account_not_found' => 'No se encontró una cuenta con este email.',

    // OAuth
    'oauth_login_success' => 'Inicio de sesión exitoso.',
    'oauth_account_linked' => 'Cuenta vinculada exitosamente.',
    'oauth_register_success' => 'Cuenta creada exitosamente.',
    'oauth_email_not_registered' => 'No existe una cuenta con este correo electrónico. Por favor regístrate primero.',
    'oauth_email_exists' => 'Ya existe una cuenta con este correo electrónico. Por favor inicia sesión con tu método original.',
    'oauth_provider_mismatch' => 'Esta cuenta ya existe con autenticación :provider.',
    'oauth_invalid_token' => 'Token de Google inválido o expirado.',

    // Verificación de email
    'invalid_verification_link' => 'El enlace de verificación no es válido.',

    // Errores generales
    'unauthenticated' => 'Sesión expirada. Inicia sesión nuevamente.',
    'unauthorized' => 'No autorizado para realizar esta acción.',

    // Errores de validación genéricos
    'invalid_data' => 'Los datos ingresados son inválidos.',
];
