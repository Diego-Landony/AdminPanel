import { useCallback, useEffect, useRef, useState } from 'react';
import { router } from '@inertiajs/react';
import type Echo from 'laravel-echo';

// Declaracion de tipos para window.Echo
declare global {
    interface Window {
        Echo: Echo<'reverb'>;
    }
}

/**
 * Tipo para el canal privado de Echo
 */
type PrivateChannel = ReturnType<Echo<'reverb'>['private']>;

/**
 * Estructura de un mensaje de soporte recibido por WebSocket
 */
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

/**
 * Datos del evento WebSocket message.sent
 */
interface SupportMessageSentEvent {
    message: WebSocketSupportMessage;
    ticket_id: number;
}

/**
 * Datos del evento WebSocket ticket.status.changed
 */
interface TicketStatusChangedEvent {
    ticket_id: number;
    status: 'open' | 'closed';
    assigned_to: number | null;
    resolved_at: string | null;
}

/**
 * Estado de la conexión WebSocket
 */
type ConnectionState = 'connecting' | 'connected' | 'disconnected' | 'error';

/**
 * Opciones para el hook useSupportTicketWebSocket
 */
interface UseSupportTicketWebSocketOptions {
    /** ID del ticket para suscribirse al canal */
    ticketId: number;
    /** Callback cuando llega un nuevo mensaje */
    onNewMessage?: (message: WebSocketSupportMessage) => void;
    /** Callback cuando cambia el estado del ticket */
    onStatusChanged?: (data: TicketStatusChangedEvent) => void;
    /** Si el WebSocket esta habilitado */
    enabled?: boolean;
    /** Props especificos a recargar (si no se especifica, recarga todos) */
    reloadProps?: string[];
    /** Si debe reproducir sonido en nuevos mensajes */
    playSound?: boolean;
}

/**
 * Resultado del hook useSupportTicketWebSocket
 */
interface UseSupportTicketWebSocketResult {
    /** Estado actual de la conexion */
    connectionState: ConnectionState;
    /** Ultimo evento recibido */
    lastEventTime: Date | null;
    /** Error de conexion si existe */
    error: string | null;
    /** Forzar reconexion manual */
    reconnect: () => void;
}

// Track if Inertia is currently navigating
let isNavigating = false;

// Set up global navigation listeners (only once)
if (typeof window !== 'undefined') {
    router.on('start', () => {
        isNavigating = true;
    });
    router.on('finish', () => {
        isNavigating = false;
    });
}

/**
 * Reproducir sonido de notificacion para nuevos mensajes
 */
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

        // Tono mas suave para mensajes de chat
        oscillator.frequency.value = 600;
        oscillator.type = 'sine';
        gainNode.gain.value = 0.2;

        oscillator.start();

        // Un solo beep corto
        setTimeout(() => {
            gainNode.gain.value = 0;
        }, 150);
        setTimeout(() => {
            oscillator.stop();
            audioContext.close();
        }, 200);
    } catch {
        console.log('[SupportWebSocket] Audio notification not supported');
    }
};

/**
 * Hook para escuchar eventos de tickets de soporte via WebSocket usando Laravel Echo
 *
 * Este hook permite recibir mensajes y cambios de estado de tickets en tiempo real.
 *
 * @example
 * ```tsx
 * const { connectionState, error, reconnect } = useSupportTicketWebSocket({
 *     ticketId: 123,
 *     onNewMessage: (message) => console.log('Nuevo mensaje:', message),
 *     onStatusChanged: (data) => console.log('Estado cambiado:', data.status),
 * });
 * ```
 */
export function useSupportTicketWebSocket({
    ticketId,
    onNewMessage,
    onStatusChanged,
    enabled = true,
    reloadProps,
    playSound = true,
}: UseSupportTicketWebSocketOptions): UseSupportTicketWebSocketResult {
    const [connectionState, setConnectionState] = useState<ConnectionState>('disconnected');
    const [lastEventTime, setLastEventTime] = useState<Date | null>(null);
    const [error, setError] = useState<string | null>(null);

    const isMounted = useRef(true);
    const channelRef = useRef<PrivateChannel | null>(null);
    const reconnectAttempts = useRef(0);
    const maxReconnectAttempts = 5;

    // Refs para mantener valores actuales sin causar re-subscripciones
    const onNewMessageRef = useRef(onNewMessage);
    const onStatusChangedRef = useRef(onStatusChanged);
    const reloadPropsRef = useRef(reloadProps);
    const playSoundRef = useRef(playSound);

    // Actualizar refs cuando los props cambien
    useEffect(() => {
        onNewMessageRef.current = onNewMessage;
        onStatusChangedRef.current = onStatusChanged;
        reloadPropsRef.current = reloadProps;
        playSoundRef.current = playSound;
    }, [onNewMessage, onStatusChanged, reloadProps, playSound]);

    /**
     * Manejar evento de nuevo mensaje
     * Usa refs para evitar closures stale
     */
    const handleMessageSent = useCallback(
        (event: SupportMessageSentEvent) => {
            if (!isMounted.current || isNavigating) {
                console.log('[SupportWebSocket] Ignoring message - not mounted or navigating');
                return;
            }

            const { message, ticket_id } = event;

            console.log('[SupportWebSocket] New message received:', {
                messageId: message.id,
                ticketId: ticket_id,
                isFromAdmin: message.is_from_admin,
            });

            setLastEventTime(new Date());

            // Solo reproducir sonido si el mensaje es del cliente (no del admin actual)
            if (!message.is_from_admin && playSoundRef.current) {
                playNotificationSound();
            }

            // Notificar callback
            onNewMessageRef.current?.(message);

            // Recargar datos de la pagina
            if (!isNavigating && isMounted.current) {
                const reloadOptions = reloadPropsRef.current ? { only: reloadPropsRef.current } : {};
                console.log('[SupportWebSocket] Reloading page with options:', reloadOptions);
                router.reload(reloadOptions);
            }
        },
        [] // Sin dependencias - usa refs
    );

    /**
     * Manejar evento de cambio de estado del ticket
     * Usa refs para evitar closures stale
     */
    const handleStatusChanged = useCallback(
        (event: TicketStatusChangedEvent) => {
            if (!isMounted.current || isNavigating) {
                return;
            }

            console.log('[SupportWebSocket] Ticket status changed:', {
                ticketId: event.ticket_id,
                status: event.status,
                assignedTo: event.assigned_to,
            });

            setLastEventTime(new Date());

            // Notificar callback
            onStatusChangedRef.current?.(event);

            // Recargar datos de la pagina
            if (!isNavigating && isMounted.current) {
                const reloadOptions = reloadPropsRef.current ? { only: reloadPropsRef.current } : {};
                router.reload(reloadOptions);
            }
        },
        [] // Sin dependencias - usa refs
    );

    /**
     * Suscribirse al canal WebSocket del ticket
     */
    const subscribe = useCallback(() => {
        if (!window.Echo) {
            console.error('[SupportWebSocket] Laravel Echo not initialized');
            setError('Laravel Echo no esta inicializado');
            setConnectionState('error');
            return;
        }

        if (!ticketId) {
            console.warn('[SupportWebSocket] No ticketId provided');
            return;
        }

        try {
            setConnectionState('connecting');
            setError(null);

            const channelName = `support.ticket.${ticketId}`;
            console.log('[SupportWebSocket] Subscribing to channel:', channelName);

            const channel = window.Echo.private(channelName);
            channelRef.current = channel;

            // Escuchar evento de nuevo mensaje
            channel.listen('.message.sent', handleMessageSent);

            // Escuchar evento de cambio de estado
            channel.listen('.ticket.status.changed', handleStatusChanged);

            // Manejar eventos de conexion del canal
            channel.subscribed(() => {
                if (isMounted.current) {
                    console.log('[SupportWebSocket] Successfully subscribed to channel:', channelName);
                    setConnectionState('connected');
                    setError(null);
                    reconnectAttempts.current = 0;
                }
            });

            channel.error((err: unknown) => {
                if (isMounted.current) {
                    console.error('[SupportWebSocket] Channel error:', err);
                    setConnectionState('error');
                    setError('Error en la conexion del canal');
                }
            });
        } catch (err) {
            console.error('[SupportWebSocket] Subscription error:', err);
            if (isMounted.current) {
                setConnectionState('error');
                setError(err instanceof Error ? err.message : 'Error al suscribirse');
            }
        }
    }, [ticketId, handleMessageSent, handleStatusChanged]);

    /**
     * Desuscribirse del canal WebSocket
     */
    const unsubscribe = useCallback(() => {
        if (channelRef.current && window.Echo) {
            const channelName = `support.ticket.${ticketId}`;
            console.log('[SupportWebSocket] Unsubscribing from channel:', channelName);

            try {
                window.Echo.leave(channelName);
            } catch (err) {
                console.error('[SupportWebSocket] Error leaving channel:', err);
            }

            channelRef.current = null;
        }
        setConnectionState('disconnected');
    }, [ticketId]);

    /**
     * Reconectar manualmente
     */
    const reconnect = useCallback(() => {
        if (reconnectAttempts.current >= maxReconnectAttempts) {
            console.warn('[SupportWebSocket] Max reconnect attempts reached');
            setError('Maximo de intentos de reconexion alcanzado');
            return;
        }

        reconnectAttempts.current += 1;
        console.log('[SupportWebSocket] Attempting reconnect, attempt:', reconnectAttempts.current);

        unsubscribe();

        // Esperar un poco antes de reconectar
        setTimeout(() => {
            if (isMounted.current && enabled) {
                subscribe();
            }
        }, 1000 * reconnectAttempts.current); // Backoff exponencial
    }, [enabled, subscribe, unsubscribe]);

    /**
     * Efecto principal para manejar la suscripcion
     * NOTA: No incluir connectionState en deps para evitar loop infinito
     */
    useEffect(() => {
        isMounted.current = true;

        if (!enabled) {
            unsubscribe();
            return;
        }

        subscribe();

        // Manejar reconexion cuando la ventana vuelve a estar visible
        const handleVisibilityChange = () => {
            if (document.visibilityState === 'visible' && enabled) {
                console.log('[SupportWebSocket] Page became visible, checking connection...');
                // Solo reconectar si no hay canal activo
                if (!channelRef.current) {
                    reconnect();
                }
            }
        };

        // Manejar reconexion cuando vuelve la conexion a internet
        const handleOnline = () => {
            if (enabled && !channelRef.current) {
                console.log('[SupportWebSocket] Back online, reconnecting...');
                reconnect();
            }
        };

        document.addEventListener('visibilitychange', handleVisibilityChange);
        window.addEventListener('online', handleOnline);

        return () => {
            isMounted.current = false;
            document.removeEventListener('visibilitychange', handleVisibilityChange);
            window.removeEventListener('online', handleOnline);
            unsubscribe();
        };
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [enabled, ticketId]);

    return {
        connectionState,
        lastEventTime,
        error,
        reconnect,
    };
}

export type {
    ConnectionState,
    SupportMessageSentEvent,
    TicketStatusChangedEvent,
    UseSupportTicketWebSocketOptions,
    UseSupportTicketWebSocketResult,
    WebSocketSupportMessage,
};
