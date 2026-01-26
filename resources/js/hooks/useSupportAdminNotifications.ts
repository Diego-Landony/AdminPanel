import { useCallback, useEffect, useRef, useState } from 'react';
import { router } from '@inertiajs/react';
import type Echo from 'laravel-echo';

declare global {
    interface Window {
        Echo: Echo<'reverb'>;
    }
}

type PrivateChannel = ReturnType<Echo<'reverb'>['private']>;

interface WebSocketSupportMessage {
    id: number;
    message: string | null;
    is_from_admin: boolean;
    is_read: boolean;
    sender: {
        type: 'admin' | 'customer';
        name: string;
    };
    attachments: Array<{
        id: number;
        url: string;
        file_name: string;
        mime_type: string;
        file_size: number;
    }>;
    created_at: string;
}

interface SupportMessageSentEvent {
    message: WebSocketSupportMessage;
    ticket_id: number;
}

interface AccessIssueCreatedEvent {
    report_id: number;
    email: string;
    issue_type: string;
    issue_type_label: string;
    created_at: string;
}

type ConnectionState = 'connecting' | 'connected' | 'disconnected' | 'error';

interface SupportStats {
    unreadTickets: number;
    pendingAccessIssues: number;
}

interface UseSupportAdminNotificationsOptions {
    enabled?: boolean;
    userId?: number | null;
}

interface UseSupportAdminNotificationsResult {
    connectionState: ConnectionState;
    stats: SupportStats;
    lastEventTime: Date | null;
    error: string | null;
    reconnect: () => void;
    refreshStats: () => void;
}

let isNavigating = false;

if (typeof window !== 'undefined') {
    router.on('start', () => {
        isNavigating = true;
    });
    router.on('finish', () => {
        isNavigating = false;
    });
}

const playNotificationSound = (): void => {
    try {
        const AudioContextClass =
            window.AudioContext ||
            (window as unknown as { webkitAudioContext: typeof AudioContext }).webkitAudioContext;
        const audioContext = new AudioContextClass();
        const oscillator = audioContext.createOscillator();
        const gainNode = audioContext.createGain();

        oscillator.connect(gainNode);
        gainNode.connect(audioContext.destination);

        oscillator.frequency.value = 800;
        oscillator.type = 'sine';
        gainNode.gain.value = 0.15;

        oscillator.start();

        setTimeout(() => {
            gainNode.gain.value = 0;
        }, 100);
        setTimeout(() => {
            oscillator.stop();
            audioContext.close();
        }, 150);
    } catch {
        // Audio not supported
    }
};

/**
 * Hook para notificaciones globales de soporte en el panel admin.
 * Escucha dos canales:
 * - support.admin: para tickets sin asignar (notifica a todos los admins)
 * - admin.{userId}: para tickets asignados al usuario actual (notifica solo a el)
 */
export function useSupportAdminNotifications(
    options: UseSupportAdminNotificationsOptions = {}
): UseSupportAdminNotificationsResult {
    const { enabled = true, userId = null } = options;

    const [connectionState, setConnectionState] = useState<ConnectionState>('disconnected');
    const [lastEventTime, setLastEventTime] = useState<Date | null>(null);
    const [error, setError] = useState<string | null>(null);
    const [stats, setStats] = useState<SupportStats>({
        unreadTickets: 0,
        pendingAccessIssues: 0,
    });

    const isMounted = useRef(true);
    const supportAdminChannelRef = useRef<PrivateChannel | null>(null);
    const userChannelRef = useRef<PrivateChannel | null>(null);
    const reconnectAttempts = useRef(0);
    const maxReconnectAttempts = 15; // Aumentado de 5 a 15 para mayor resiliencia
    const userIdRef = useRef(userId);

    // Mantener actualizado el userId en el ref
    useEffect(() => {
        userIdRef.current = userId;
    }, [userId]);

    /**
     * Obtener estadisticas de soporte del servidor
     */
    const refreshStats = useCallback(async () => {
        try {
            const response = await fetch('/api/admin/support/stats', {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            });

            if (response.ok) {
                const data = await response.json();
                if (isMounted.current) {
                    setStats({
                        unreadTickets: data.unread_tickets ?? 0,
                        pendingAccessIssues: data.pending_access_issues ?? 0,
                    });
                }
            }
        } catch (err) {
            console.error('[SupportAdminNotifications] Error fetching stats:', err);
        }
    }, []);

    /**
     * Manejar evento de nuevo mensaje
     */
    const handleMessageSent = useCallback(
        (event: SupportMessageSentEvent, channelSource: 'support.admin' | 'admin.user') => {
            if (!isMounted.current || isNavigating) {
                return;
            }

            const { message } = event;

            // Solo contar mensajes de clientes (no de admin)
            if (!message.is_from_admin) {
                console.log('[SupportAdminNotifications] New customer message received:', {
                    messageId: message.id,
                    ticketId: event.ticket_id,
                    channel: channelSource,
                });

                setLastEventTime(new Date());

                // Solo reproducir sonido si NO estamos en la pagina del chat de ese ticket
                // (si estamos viendo el chat, no necesitamos sonido porque ya lo vemos)
                const isOnTicketShowPage = window.location.pathname.match(/\/support\/tickets\/\d+$/);
                if (!isOnTicketShowPage) {
                    playNotificationSound();
                }

                // Refrescar stats reales del servidor
                // NO incrementamos manualmente porque el servidor ya cuenta correctamente
                // los tickets con mensajes no leidos (no los mensajes individuales)
                refreshStats();
            }
        },
        [refreshStats]
    );

    /**
     * Manejar evento de nuevo reporte de acceso
     */
    const handleAccessIssueCreated = useCallback(
        (event: AccessIssueCreatedEvent) => {
            if (!isMounted.current || isNavigating) {
                return;
            }

            console.log('[SupportAdminNotifications] New access issue report received:', {
                reportId: event.report_id,
                email: event.email,
                issueType: event.issue_type,
            });

            setLastEventTime(new Date());

            // Solo reproducir sonido si NO estamos en la pagina de access issues
            const isOnAccessIssuesPage = window.location.pathname.includes('/support/access-issues');
            if (!isOnAccessIssuesPage) {
                playNotificationSound();
            }

            // Refrescar stats del servidor
            refreshStats();
        },
        [refreshStats]
    );

    /**
     * Suscribirse a los canales WebSocket de admin
     */
    const subscribe = useCallback(() => {
        if (!window.Echo) {
            console.error('[SupportAdminNotifications] Laravel Echo not initialized');
            setError('Laravel Echo no esta inicializado');
            setConnectionState('error');
            return;
        }

        try {
            setConnectionState('connecting');
            setError(null);

            // Canal 1: support.admin - para tickets sin asignar (todos los admins)
            const supportAdminChannelName = 'support.admin';
            console.log('[SupportAdminNotifications] Subscribing to channel:', supportAdminChannelName);

            const supportAdminChannel = window.Echo.private(supportAdminChannelName);
            supportAdminChannelRef.current = supportAdminChannel;

            supportAdminChannel.listen('.message.sent', (event: SupportMessageSentEvent) => {
                handleMessageSent(event, 'support.admin');
            });

            // Escuchar nuevos reportes de acceso
            supportAdminChannel.listen('.access-issue.created', (event: AccessIssueCreatedEvent) => {
                handleAccessIssueCreated(event);
            });

            supportAdminChannel.subscribed(() => {
                if (isMounted.current) {
                    console.log('[SupportAdminNotifications] Successfully subscribed to channel:', supportAdminChannelName);
                    reconnectAttempts.current = 0;

                    // Cargar stats iniciales
                    refreshStats();

                    // Marcar como conectado si el canal de usuario ya esta listo o no es necesario
                    if (!userIdRef.current || userChannelRef.current) {
                        setConnectionState('connected');
                        setError(null);
                    }
                }
            });

            supportAdminChannel.error((err: unknown) => {
                if (isMounted.current) {
                    console.error('[SupportAdminNotifications] Support admin channel error:', err);
                    setConnectionState('error');
                    setError('Error en la conexion del canal support.admin');
                }
            });

            // Canal 2: admin.{userId} - para tickets asignados al usuario actual
            if (userIdRef.current) {
                const userChannelName = `admin.${userIdRef.current}`;
                console.log('[SupportAdminNotifications] Subscribing to user channel:', userChannelName);

                const userChannel = window.Echo.private(userChannelName);
                userChannelRef.current = userChannel;

                userChannel.listen('.message.sent', (event: SupportMessageSentEvent) => {
                    handleMessageSent(event, 'admin.user');
                });

                userChannel.subscribed(() => {
                    if (isMounted.current) {
                        console.log('[SupportAdminNotifications] Successfully subscribed to user channel:', userChannelName);

                        // Marcar como conectado si el canal de soporte tambien esta listo
                        if (supportAdminChannelRef.current) {
                            setConnectionState('connected');
                            setError(null);
                        }
                    }
                });

                userChannel.error((err: unknown) => {
                    if (isMounted.current) {
                        console.error('[SupportAdminNotifications] User channel error:', err);
                        // No marcar como error total si el canal principal funciona
                    }
                });
            } else {
                // Si no hay userId, solo conectamos al canal de soporte admin
                setConnectionState('connected');
                setError(null);
            }
        } catch (err) {
            console.error('[SupportAdminNotifications] Subscription error:', err);
            if (isMounted.current) {
                setConnectionState('error');
                setError(err instanceof Error ? err.message : 'Error al suscribirse');
            }
        }
    }, [handleMessageSent, handleAccessIssueCreated, refreshStats]);

    /**
     * Desuscribirse de los canales WebSocket
     */
    const unsubscribe = useCallback(() => {
        if (window.Echo) {
            // Desuscribirse del canal support.admin
            if (supportAdminChannelRef.current) {
                const supportAdminChannelName = 'support.admin';
                console.log('[SupportAdminNotifications] Unsubscribing from channel:', supportAdminChannelName);

                try {
                    window.Echo.leave(supportAdminChannelName);
                } catch (err) {
                    console.error('[SupportAdminNotifications] Error leaving support.admin channel:', err);
                }

                supportAdminChannelRef.current = null;
            }

            // Desuscribirse del canal admin.{userId}
            if (userChannelRef.current && userIdRef.current) {
                const userChannelName = `admin.${userIdRef.current}`;
                console.log('[SupportAdminNotifications] Unsubscribing from user channel:', userChannelName);

                try {
                    window.Echo.leave(userChannelName);
                } catch (err) {
                    console.error('[SupportAdminNotifications] Error leaving user channel:', err);
                }

                userChannelRef.current = null;
            }
        }
        setConnectionState('disconnected');
    }, []);

    /**
     * Reconectar manualmente
     */
    const reconnect = useCallback(() => {
        if (reconnectAttempts.current >= maxReconnectAttempts) {
            console.warn('[SupportAdminNotifications] Max reconnect attempts reached');
            setError('Maximo de intentos de reconexion alcanzado');
            return;
        }

        reconnectAttempts.current += 1;
        console.log('[SupportAdminNotifications] Attempting reconnect, attempt:', reconnectAttempts.current);

        unsubscribe();

        // Backoff exponencial con jitter: 1s, 2s, 4s, 8s, 16s, 30s (max)
        const baseDelay = 1000;
        const maxDelay = 30000;
        const exponentialDelay = Math.min(baseDelay * Math.pow(2, reconnectAttempts.current - 1), maxDelay);
        // Agregar jitter aleatorio (±25%) para evitar reconexiones sincronizadas
        const jitter = exponentialDelay * 0.25 * (Math.random() * 2 - 1);
        const delay = Math.round(exponentialDelay + jitter);

        console.log(`[SupportAdminNotifications] Reconnecting in ${delay}ms (attempt ${reconnectAttempts.current})`);

        setTimeout(() => {
            if (isMounted.current && enabled) {
                subscribe();
            }
        }, delay);
    }, [enabled, subscribe, unsubscribe]);

    // Refs para guardar las funciones actuales sin causar re-renders
    const subscribeRef = useRef(subscribe);
    const unsubscribeRef = useRef(unsubscribe);
    const reconnectRef = useRef(reconnect);
    const refreshStatsRef = useRef(refreshStats);

    // Actualizar refs cuando las funciones cambien
    useEffect(() => {
        subscribeRef.current = subscribe;
        unsubscribeRef.current = unsubscribe;
        reconnectRef.current = reconnect;
        refreshStatsRef.current = refreshStats;
    });

    /**
     * Efecto principal para manejar la suscripcion
     * Se re-ejecuta cuando cambia enabled o userId
     */
    useEffect(() => {
        isMounted.current = true;

        // No ejecutar en el panel de restaurante
        if (window.location.pathname.startsWith('/restaurant')) {
            return;
        }

        if (!enabled) {
            unsubscribeRef.current();
            return;
        }

        // Cargar stats iniciales inmediatamente
        refreshStatsRef.current();

        // Pequeño delay para asegurar que todo este listo
        const timeoutId = setTimeout(() => {
            if (isMounted.current) {
                subscribeRef.current();
            }
        }, 100);

        const handleVisibilityChange = () => {
            if (document.visibilityState === 'visible') {
                // Siempre refrescar stats cuando la pagina vuelve a ser visible
                refreshStatsRef.current();

                if (!supportAdminChannelRef.current) {
                    console.log('[SupportAdminNotifications] Page became visible, reconnecting...');
                    reconnectAttempts.current = 0; // Resetear intentos al volver visible
                    reconnectRef.current();
                }
            }
        };

        const handleOnline = () => {
            if (!supportAdminChannelRef.current) {
                console.log('[SupportAdminNotifications] Back online, reconnecting...');
                reconnectAttempts.current = 0; // Resetear intentos al volver online
                reconnectRef.current();
            }
        };

        document.addEventListener('visibilitychange', handleVisibilityChange);
        window.addEventListener('online', handleOnline);

        // Refrescar stats periodicamente (cada 60 segundos)
        const statsInterval = setInterval(() => {
            if (isMounted.current && document.visibilityState === 'visible') {
                refreshStatsRef.current();
            }
        }, 60000);

        return () => {
            isMounted.current = false;
            clearTimeout(timeoutId);
            document.removeEventListener('visibilitychange', handleVisibilityChange);
            window.removeEventListener('online', handleOnline);
            clearInterval(statsInterval);
            unsubscribeRef.current();
        };
    }, [enabled, userId]);

    return {
        connectionState,
        stats,
        lastEventTime,
        error,
        reconnect,
        refreshStats,
    };
}

export type { ConnectionState, SupportStats, UseSupportAdminNotificationsResult, UseSupportAdminNotificationsOptions };
