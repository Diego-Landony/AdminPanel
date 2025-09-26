import { usePage } from '@inertiajs/react';
import { useEffect, useRef } from 'react';
import { toast } from 'sonner';

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
 * Hook personalizado para manejar notificaciones globales
 * Sistema centralizado que previene duplicados de manera robusta
 */
export function useNotifications() {
    const { props } = usePage();
    const { flash } = props as { flash?: { success?: string; error?: string; warning?: string; info?: string; message?: string; status?: string } };
    const processedMessages = useRef<Set<string>>(new Set());

    useEffect(() => {
        // Limpiar mensajes procesados en cada renderizado para permitir nuevas notificaciones
        processedMessages.current.clear();

        if (flash?.success) {
            const id = generateNotificationId('success', flash.success);
            if (!isNotificationRecent(id)) {
                toast.success(flash.success, {
                    duration: NOTIFICATION_CONFIG.durations.success,
                    position: NOTIFICATION_CONFIG.position,
                });
                markNotificationAsShown(id);
            }
        }

        if (flash?.error) {
            const id = generateNotificationId('error', flash.error);
            if (!isNotificationRecent(id)) {
                toast.error(flash.error, {
                    duration: NOTIFICATION_CONFIG.durations.error,
                    position: NOTIFICATION_CONFIG.position,
                });
                markNotificationAsShown(id);
            }
        }

        if (flash?.warning) {
            const id = generateNotificationId('warning', flash.warning);
            if (!isNotificationRecent(id)) {
                toast.warning(flash.warning, {
                    duration: NOTIFICATION_CONFIG.durations.warning,
                    position: NOTIFICATION_CONFIG.position,
                });
                markNotificationAsShown(id);
            }
        }

        if (flash?.info) {
            const id = generateNotificationId('info', flash.info);
            if (!isNotificationRecent(id)) {
                toast.info(flash.info, {
                    duration: NOTIFICATION_CONFIG.durations.info,
                    position: NOTIFICATION_CONFIG.position,
                });
                markNotificationAsShown(id);
            }
        }

        // Manejo de mensajes específicos del servidor
        if (flash?.message) {
            const id = generateNotificationId('message', flash.message);
            if (!isNotificationRecent(id)) {
                toast.info(flash.message, {
                    duration: NOTIFICATION_CONFIG.durations.info,
                    position: NOTIFICATION_CONFIG.position,
                });
                markNotificationAsShown(id);
            }
        }

        // Manejo de estados específicos del servidor
        if (flash?.status) {
            const id = generateNotificationId('status', flash.status);
            if (!isNotificationRecent(id)) {
                switch (flash.status) {
                    case 'verification-link-sent':
                        toast.success('Enlace de verificación enviado', {
                            description: 'Revisa tu correo electrónico',
                            duration: NOTIFICATION_CONFIG.durations.success,
                            position: NOTIFICATION_CONFIG.position,
                        });
                        break;
                    case 'password-updated':
                        toast.success('Contraseña actualizada exitosamente', {
                            duration: NOTIFICATION_CONFIG.durations.success,
                            position: NOTIFICATION_CONFIG.position,
                        });
                        break;
                    case 'profile-updated':
                        toast.success('Perfil actualizado exitosamente', {
                            duration: NOTIFICATION_CONFIG.durations.success,
                            position: NOTIFICATION_CONFIG.position,
                        });
                        break;
                    default:
                        if (flash.status) {
                            toast.info(flash.status, {
                                duration: NOTIFICATION_CONFIG.durations.info,
                                position: NOTIFICATION_CONFIG.position,
                            });
                        }
                        break;
                }
                markNotificationAsShown(id);
            }
        }
    }, [flash]);

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
