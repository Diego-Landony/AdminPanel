import { useEffect, useRef } from 'react';
import { usePage } from '@inertiajs/react';
import { toast } from 'sonner';

/**
 * Hook personalizado para manejar notificaciones globales
 * Solo maneja mensajes flash del servidor, NO errores de validación
 */
export function useNotifications() {
    const { props } = usePage();
    const { flash } = props as { flash?: { success?: string; error?: string; warning?: string; info?: string; message?: string; status?: string } };
    const processedMessages = useRef<Set<string>>(new Set());

    useEffect(() => {
        // Solo manejar mensajes flash del servidor (NO errores de validación)
        // Prevenir duplicación usando un Set de mensajes procesados
        
        if (flash?.success && !processedMessages.current.has(`success:${flash.success}`)) {
            toast.success(flash.success, {
                duration: 4000,
                position: 'top-right',
            });
            processedMessages.current.add(`success:${flash.success}`);
        }

        if (flash?.error && !processedMessages.current.has(`error:${flash.error}`)) {
            toast.error(flash.error, {
                duration: 5000,
                position: 'top-right',
            });
            processedMessages.current.add(`error:${flash.error}`);
        }

        if (flash?.warning && !processedMessages.current.has(`warning:${flash.warning}`)) {
            toast.warning(flash.warning, {
                duration: 4000,
                position: 'top-right',
            });
            processedMessages.current.add(`warning:${flash.warning}`);
        }

        if (flash?.info && !processedMessages.current.has(`info:${flash.info}`)) {
            toast.info(flash.info, {
                duration: 3000,
                position: 'top-right',
            });
            processedMessages.current.add(`info:${flash.info}`);
        }

        // Manejo de mensajes específicos del servidor
        if (flash?.message && !processedMessages.current.has(`message:${flash.message}`)) {
            toast.info(flash.message, {
                duration: 4000,
                position: 'top-right',
            });
            processedMessages.current.add(`message:${flash.message}`);
        }

        // Manejo de estados específicos del servidor
        if (flash?.status && !processedMessages.current.has(`status:${flash.status}`)) {
            switch (flash.status) {
                case 'verification-link-sent':
                    toast.success('Enlace de verificación enviado', {
                        description: 'Revisa tu correo electrónico',
                        duration: 5000,
                        position: 'top-right',
                    });
                    break;
                case 'password-updated':
                    toast.success('Contraseña actualizada exitosamente', {
                        duration: 4000,
                        position: 'top-right',
                    });
                    break;
                case 'profile-updated':
                    toast.success('Perfil actualizado exitosamente', {
                        duration: 4000,
                        position: 'top-right',
                    });
                    break;
                default:
                    if (flash.status) {
                        toast.info(flash.status, {
                            duration: 4000,
                            position: 'top-right',
                        });
                    }
                    break;
            }
            processedMessages.current.add(`status:${flash.status}`);
        }

    }, [flash]);

    // Funciones de utilidad para mostrar notificaciones programáticamente
    const notify = {
        success: (message: string, description?: string) => {
            toast.success(message, {
                description,
                duration: 4000,
                position: 'top-right',
            });
        },
        error: (message: string, description?: string) => {
            toast.error(message, {
                description,
                duration: 5000,
                position: 'top-right',
            });
        },
        warning: (message: string, description?: string) => {
            toast.warning(message, {
                description,
                duration: 4000,
                position: 'top-right',
            });
        },
        info: (message: string, description?: string) => {
            toast.info(message, {
                description,
                duration: 3000,
                position: 'top-right',
            });
        },
        loading: (message: string) => {
            return toast.loading(message, {
                position: 'top-right',
            });
        },
        promise: <T,>(
            promise: Promise<T>,
            loading: string,
            success: string | ((data: T) => string),
            error: string | ((error: unknown) => string)
        ) => {
            return toast.promise(promise, {
                loading,
                success,
                error,
                position: 'top-right',
            });
        }
    };

    return { notify };
}

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

