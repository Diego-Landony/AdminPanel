<?php

return [
    // Autenticación básica
    'failed' => 'Credenciales incorrectas.',
    'password' => 'La contraseña es incorrecta.',
    'throttle' => 'Demasiados intentos de acceso. Inténtalo de nuevo en :seconds segundos.',

    // Mensajes de éxito
    'register_success' => 'Registro exitoso. Verifica tu email.',
    'login_success' => 'Inicio de sesión exitoso.',
    'logout_success' => 'Sesión cerrada',
    'logout_all_success' => 'Todas las sesiones cerradas',
    'token_refreshed' => 'Token renovado',
    'password_reset_link_sent' => 'Enlace de restablecimiento enviado',
    'password_reset_success' => 'Contraseña restablecida',
    'password_reset_throttled' => 'Demasiados intentos. Por favor espera :seconds segundos antes de volver a intentarlo.',
    'password_created' => 'Contraseña creada. Ya puedes iniciar sesión.',
    'password_updated' => 'Contraseña actualizada. Sesiones cerradas.',
    'email_verified' => 'Email verificado',
    'email_already_verified' => 'El email ya ha sido verificado.',
    'verification_link_resent' => 'Enlace reenviado',

    // Errores de credenciales
    'invalid_credentials' => 'Las credenciales proporcionadas son incorrectas.',
    'oauth_account' => 'Esta cuenta usa autenticación con :provider. Por favor inicia sesión con :provider.',
    'account_not_found' => 'No se encontró una cuenta con este email.',
    'email_not_found' => 'No existe una cuenta con este correo electrónico.',
    'incorrect_password' => 'La contraseña es incorrecta.',
    'oauth_no_password' => 'Esta cuenta usa autenticación con :provider y no tiene contraseña. Inicia sesión con :provider.',

    // OAuth
    'oauth_login_success' => 'Inicio de sesión exitoso.',
    'oauth_account_linked' => 'Cuenta vinculada exitosamente. Ahora puedes iniciar sesión con tu contraseña o con este método.',
    'oauth_register_success' => 'Cuenta creada exitosamente.',
    'oauth_email_not_registered' => 'No existe una cuenta con este correo electrónico. Por favor regístrate primero.',
    'oauth_email_exists' => 'Ya existe una cuenta con este correo electrónico. Por favor inicia sesión con tu método original.',
    'oauth_user_already_exists' => 'Ya existe una cuenta con este correo electrónico. Usa iniciar sesión en lugar de registrarte.',
    'oauth_provider_mismatch' => 'Esta cuenta ya existe con autenticación :provider.',
    'oauth_invalid_token' => 'Token de Google inválido o expirado.',
    'oauth_authentication_failed' => 'Error al procesar la autenticación. Por favor intenta nuevamente.',

    // Verificación de email
    'invalid_verification_link' => 'El enlace de verificación no es válido.',
    'verification_link_expired' => 'Enlace Expirado',
    'invalid_or_expired_link' => 'El enlace es inválido o ha expirado.',

    // Errores generales
    'unauthenticated' => 'Sesión expirada. Inicia sesión nuevamente.',
    'unauthorized' => 'No autorizado para realizar esta acción.',

    // Errores de validación genéricos
    'invalid_data' => 'Los datos ingresados son inválidos.',

    // Recuperación de cuenta eliminada
    'account_deleted_recoverable' => 'Encontramos una cuenta eliminada con este correo.',
    'oauth_account_deleted_recoverable' => 'Tu cuenta fue eliminada pero aún puede recuperarse. Tienes :points puntos acumulados y :days_left días restantes para recuperar tu cuenta.',

    // Reactivación de cuenta
    'account_reactivated' => 'Cuenta reactivada. Bienvenido.',
    'account_not_found_deleted' => 'No se encontró una cuenta eliminada con este correo electrónico.',
    'reactivation_period_expired' => 'El período de reactivación ha expirado. La cuenta fue eliminada hace más de 30 días.',
    'oauth_deleted_use_provider' => 'Esta cuenta eliminada usa :provider. Para reactivarla, inicia sesión con :provider.',
];
