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

type ConnectionState = 'connecting' | 'connected' | 'disconnected' | 'error';

interface SupportStats {
    unreadTickets: number;
    pendingAccessIssues: number;
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
 * Escucha el canal support.admin para recibir mensajes de clientes en tiempo real.
 */
export function useSupportAdminNotifications(enabled = true): UseSupportAdminNotificationsResult {
    const [connectionState, setConnectionState] = useState<ConnectionState>('disconnected');
    const [lastEventTime, setLastEventTime] = useState<Date | null>(null);
    const [error, setError] = useState<string | null>(null);
    const [stats, setStats] = useState<SupportStats>({
        unreadTickets: 0,
        pendingAccessIssues: 0,
    });

    const isMounted = useRef(true);
    const channelRef = useRef<PrivateChannel | null>(null);
    const reconnectAttempts = useRef(0);
    const maxReconnectAttempts = 5;

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
        (event: SupportMessageSentEvent) => {
            if (!isMounted.current || isNavigating) {
                return;
            }

            const { message } = event;

            // Solo contar mensajes de clientes (no de admin)
            if (!message.is_from_admin) {
                console.log('[SupportAdminNotifications] New customer message received:', {
                    messageId: message.id,
                    ticketId: event.ticket_id,
                });

                setLastEventTime(new Date());
                playNotificationSound();

                // Incrementar contador temporalmente y luego refrescar stats reales
                setStats((prev) => ({
                    ...prev,
                    unreadTickets: prev.unreadTickets + 1,
                }));

                // Refrescar stats reales del servidor
                setTimeout(() => {
                    if (isMounted.current) {
                        refreshStats();
                    }
                }, 500);
            }
        },
        [refreshStats]
    );

    /**
     * Suscribirse al canal WebSocket de admin
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

            const channelName = 'support.admin';
            console.log('[SupportAdminNotifications] Subscribing to channel:', channelName);

            const channel = window.Echo.private(channelName);
            channelRef.current = channel;

            channel.listen('.message.sent', handleMessageSent);

            channel.subscribed(() => {
                if (isMounted.current) {
                    console.log('[SupportAdminNotifications] Successfully subscribed to channel:', channelName);
                    setConnectionState('connected');
                    setError(null);
                    reconnectAttempts.current = 0;

                    // Cargar stats iniciales
                    refreshStats();
                }
            });

            channel.error((err: unknown) => {
                if (isMounted.current) {
                    console.error('[SupportAdminNotifications] Channel error:', err);
                    setConnectionState('error');
                    setError('Error en la conexion del canal');
                }
            });
        } catch (err) {
            console.error('[SupportAdminNotifications] Subscription error:', err);
            if (isMounted.current) {
                setConnectionState('error');
                setError(err instanceof Error ? err.message : 'Error al suscribirse');
            }
        }
    }, [handleMessageSent, refreshStats]);

    /**
     * Desuscribirse del canal WebSocket
     */
    const unsubscribe = useCallback(() => {
        if (channelRef.current && window.Echo) {
            const channelName = 'support.admin';
            console.log('[SupportAdminNotifications] Unsubscribing from channel:', channelName);

            try {
                window.Echo.leave(channelName);
            } catch (err) {
                console.error('[SupportAdminNotifications] Error leaving channel:', err);
            }

            channelRef.current = null;
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

        setTimeout(() => {
            if (isMounted.current && enabled) {
                subscribe();
            }
        }, 1000 * reconnectAttempts.current);
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
     * Solo se re-ejecuta cuando cambia enabled
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

        // Pequeño delay para asegurar que todo esté listo
        const timeoutId = setTimeout(() => {
            if (isMounted.current) {
                subscribeRef.current();
            }
        }, 100);

        const handleVisibilityChange = () => {
            if (document.visibilityState === 'visible') {
                // Siempre refrescar stats cuando la pagina vuelve a ser visible
                refreshStatsRef.current();

                if (!channelRef.current) {
                    console.log('[SupportAdminNotifications] Page became visible, reconnecting...');
                    reconnectRef.current();
                }
            }
        };

        const handleOnline = () => {
            if (!channelRef.current) {
                console.log('[SupportAdminNotifications] Back online, reconnecting...');
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
    }, [enabled]);

    return {
        connectionState,
        stats,
        lastEventTime,
        error,
        reconnect,
        refreshStats,
    };
}

export type { ConnectionState, SupportStats, UseSupportAdminNotificationsResult };
