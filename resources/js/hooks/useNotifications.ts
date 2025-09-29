import { usePage } from '@inertiajs/react';
import { useCallback, useEffect } from 'react';
import { toast } from 'sonner';

import { NOTIFICATIONS } from '@/constants/ui-constants';

// Cache global para evitar duplicados entre diferentes instancias
const globalNotificationCache = new Map<string, number>();
const CACHE_DURATION = 5000; // 5 segundos

/**
 * Configuración centralizada de notificaciones
 */
const NOTIFICATION_CONFIG = {
    position: 'top-center' as const,
    durations: {
        success: 4000,
        error: 5000,
        warning: 4000,
        info: 3000,
        loading: Infinity,
    },
};

/**
 * Genera un ID único para la notificación basado en tipo y mensaje
 */
function generateNotificationId(type: string, message: string): string {
    return `${type}:${message.slice(0, 50)}`;
}

/**
 * Verifica si una notificación ya fue mostrada recientemente
 */
function isNotificationRecent(id: string): boolean {
    const timestamp = globalNotificationCache.get(id);
    if (!timestamp) return false;

    const now = Date.now();
    if (now - timestamp > CACHE_DURATION) {
        globalNotificationCache.delete(id);
        return false;
    }

    return true;
}

/**
 * Marca una notificación como mostrada
 */
function markNotificationAsShown(id: string): void {
    globalNotificationCache.set(id, Date.now());

    // Limpieza automática del cache
    setTimeout(() => {
        globalNotificationCache.delete(id);
    }, CACHE_DURATION);
}

/**
 * Mapeo de tipos de flash messages para optimización
 */
const FLASH_TYPE_MAP = {
    success: { method: 'success' as const, duration: NOTIFICATION_CONFIG.durations.success },
    error: { method: 'error' as const, duration: NOTIFICATION_CONFIG.durations.error },
    warning: { method: 'warning' as const, duration: NOTIFICATION_CONFIG.durations.warning },
    info: { method: 'info' as const, duration: NOTIFICATION_CONFIG.durations.info },
    message: { method: 'info' as const, duration: NOTIFICATION_CONFIG.durations.info },
} as const;

/**
 * Mapeo de estados específicos del servidor
 */
const STATUS_MESSAGES = {
    'verification-link-sent': {
        message: 'Enlace de verificación enviado',
        description: 'Revisa tu correo electrónico',
        type: 'success' as const,
    },
    'password-updated': {
        message: 'Contraseña actualizada exitosamente',
        type: 'success' as const,
    },
    'profile-updated': {
        message: 'Perfil actualizado exitosamente',
        type: 'success' as const,
    },
} as const;

/**
 * Hook personalizado para manejar notificaciones globales optimizado
 * Sistema centralizado que previene duplicados de manera robusta con mejor rendimiento
 */
export function useNotifications() {
    const { props } = usePage();
    const { flash } = props as { flash?: { success?: string; error?: string; warning?: string; info?: string; message?: string; status?: string } };

    // Función memoizada para mostrar notificaciones
    const showNotification = useCallback((type: string, message: string, description?: string) => {
        const id = generateNotificationId(type, message);
        if (!isNotificationRecent(id)) {
            const config = FLASH_TYPE_MAP[type as keyof typeof FLASH_TYPE_MAP];
            if (config) {
                toast[config.method](message, {
                    description,
                    duration: config.duration,
                    position: NOTIFICATION_CONFIG.position,
                });
                markNotificationAsShown(id);
            }
        }
    }, []);

    // Efecto optimizado que procesa todos los flash messages
    useEffect(() => {
        if (!flash) return;

        // Procesar flash messages básicos
        Object.entries(FLASH_TYPE_MAP).forEach(([key]) => {
            const message = flash[key as keyof typeof flash];
            if (message) {
                showNotification(key, message);
            }
        });

        // Procesar estados específicos del servidor
        if (flash.status) {
            const statusConfig = STATUS_MESSAGES[flash.status as keyof typeof STATUS_MESSAGES];
            if (statusConfig) {
                showNotification(
                    statusConfig.type,
                    statusConfig.message,
                    'description' in statusConfig ? statusConfig.description : undefined
                );
            } else {
                // Status no reconocido, mostrar como info
                showNotification('info', flash.status);
            }
        }
    }, [flash, showNotification]);

    // Funciones de utilidad para mostrar notificaciones programáticamente con deduplicación
    const notify = {
        success: (message: string, description?: string) => {
            const id = generateNotificationId('success', message);
            if (!isNotificationRecent(id)) {
                toast.success(message, {
                    description,
                    duration: NOTIFICATION_CONFIG.durations.success,
                    position: NOTIFICATION_CONFIG.position,
                });
                markNotificationAsShown(id);
            }
        },
        error: (message: string, description?: string) => {
            const id = generateNotificationId('error', message);
            if (!isNotificationRecent(id)) {
                toast.error(message, {
                    description,
                    duration: NOTIFICATION_CONFIG.durations.error,
                    position: NOTIFICATION_CONFIG.position,
                });
                markNotificationAsShown(id);
            }
        },
        warning: (message: string, description?: string) => {
            const id = generateNotificationId('warning', message);
            if (!isNotificationRecent(id)) {
                toast.warning(message, {
                    description,
                    duration: NOTIFICATION_CONFIG.durations.warning,
                    position: NOTIFICATION_CONFIG.position,
                });
                markNotificationAsShown(id);
            }
        },
        info: (message: string, description?: string) => {
            const id = generateNotificationId('info', message);
            if (!isNotificationRecent(id)) {
                toast.info(message, {
                    description,
                    duration: NOTIFICATION_CONFIG.durations.info,
                    position: NOTIFICATION_CONFIG.position,
                });
                markNotificationAsShown(id);
            }
        },
        loading: (message: string) => {
            // Loading notifications should not be deduplicated as they serve different purposes
            return toast.loading(message, {
                position: NOTIFICATION_CONFIG.position,
            });
        },
        promise: <T>(promise: Promise<T>, loading: string, success: string | ((data: T) => string), error: string | ((error: unknown) => string)) => {
            return toast.promise(promise, {
                loading,
                success,
                error,
                position: NOTIFICATION_CONFIG.position,
            });
        },
        // Network-specific notifications
        networkError: (error?: string) => {
            const message = error || NOTIFICATIONS.error.networkConnection;
            const id = generateNotificationId('network', message);
            if (!isNotificationRecent(id)) {
                toast.error(message, {
                    description: 'Verifica tu conexión a internet',
                    duration: NOTIFICATION_CONFIG.durations.error,
                    position: NOTIFICATION_CONFIG.position,
                });
                markNotificationAsShown(id);
            }
        },
        serverTimeout: () => {
            const id = generateNotificationId('timeout', NOTIFICATIONS.error.serverTimeout);
            if (!isNotificationRecent(id)) {
                toast.error(NOTIFICATIONS.error.serverTimeout, {
                    description: 'Intenta nuevamente en unos momentos',
                    duration: NOTIFICATION_CONFIG.durations.error,
                    position: NOTIFICATION_CONFIG.position,
                });
                markNotificationAsShown(id);
            }
        },
        sessionExpired: () => {
            const id = generateNotificationId('session', NOTIFICATIONS.error.sessionExpired);
            if (!isNotificationRecent(id)) {
                toast.error(NOTIFICATIONS.error.sessionExpired, {
                    description: 'Redirigiendo al login...',
                    duration: NOTIFICATION_CONFIG.durations.error,
                    position: NOTIFICATION_CONFIG.position,
                });
                markNotificationAsShown(id);
            }
        },
        rateLimited: () => {
            const id = generateNotificationId('rate', NOTIFICATIONS.error.rateLimited);
            if (!isNotificationRecent(id)) {
                toast.warning(NOTIFICATIONS.error.rateLimited, {
                    description: 'Por favor, reduce la frecuencia de solicitudes',
                    duration: NOTIFICATION_CONFIG.durations.warning,
                    position: NOTIFICATION_CONFIG.position,
                });
                markNotificationAsShown(id);
            }
        },
    };

    return { notify };
}

/**
 * Función standalone para mostrar notificaciones sin necesidad del hook
 * Útil para ser importada directamente en componentes
 */
export const showNotification = {
    success: (message: string, description?: string) => {
        const id = generateNotificationId('success', message);
        if (!isNotificationRecent(id)) {
            toast.success(message, {
                description,
                duration: NOTIFICATION_CONFIG.durations.success,
                position: NOTIFICATION_CONFIG.position,
            });
            markNotificationAsShown(id);
        }
    },
    error: (message: string, description?: string) => {
        const id = generateNotificationId('error', message);
        if (!isNotificationRecent(id)) {
            toast.error(message, {
                description,
                duration: NOTIFICATION_CONFIG.durations.error,
                position: NOTIFICATION_CONFIG.position,
            });
            markNotificationAsShown(id);
        }
    },
    warning: (message: string, description?: string) => {
        const id = generateNotificationId('warning', message);
        if (!isNotificationRecent(id)) {
            toast.warning(message, {
                description,
                duration: NOTIFICATION_CONFIG.durations.warning,
                position: NOTIFICATION_CONFIG.position,
            });
            markNotificationAsShown(id);
        }
    },
    info: (message: string, description?: string) => {
        const id = generateNotificationId('info', message);
        if (!isNotificationRecent(id)) {
            toast.info(message, {
                description,
                duration: NOTIFICATION_CONFIG.durations.info,
                position: NOTIFICATION_CONFIG.position,
            });
            markNotificationAsShown(id);
        }
    },
    loading: (message: string) => {
        return toast.loading(message, {
            position: NOTIFICATION_CONFIG.position,
        });
    },
    promise: <T>(promise: Promise<T>, loading: string, success: string | ((data: T) => string), error: string | ((error: unknown) => string)) => {
        return toast.promise(promise, {
            loading,
            success,
            error,
            position: NOTIFICATION_CONFIG.position,
        });
    },
};

/**
 * Hook para manejar notificaciones específicas de formularios
 * Solo para mensajes de éxito/error del servidor, NO errores de validación
 */
export function useFormNotifications() {
    const { notify } = useNotifications();

    const showFormSuccess = (action: string) => {
        notify.success(`${action} exitoso`);
    };

    const showFormError = (action: string, error?: string) => {
        notify.error(`Error al ${action}`, error || 'Inténtalo de nuevo');
    };

    return {
        notify,
        showFormSuccess,
        showFormError,
    };
}
