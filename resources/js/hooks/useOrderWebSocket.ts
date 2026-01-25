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
 * Estructura de una orden recibida por WebSocket
 */
interface WebSocketOrder {
    id: number;
    order_number: string;
    status: string;
    created_at: string;
    [key: string]: unknown;
}

/**
 * Datos del evento WebSocket order.status.updated
 */
interface OrderStatusUpdatedEvent {
    order: WebSocketOrder;
    previous_status: string;
    new_status: string;
}

/**
 * Estado de la conexión WebSocket
 */
type ConnectionState = 'connecting' | 'connected' | 'disconnected' | 'error';

/**
 * Opciones para el hook useOrderWebSocket
 */
interface UseOrderWebSocketOptions {
    /** ID del restaurante para suscribirse al canal */
    restaurantId: number;
    /** Auto-imprimir nuevas ordenes */
    autoPrint: boolean;
    /** Callback cuando llega una nueva orden */
    onNewOrder?: (order: WebSocketOrder) => void;
    /** Callback cuando cambia el estado de una orden */
    onOrderStatusChanged?: (order: WebSocketOrder, previousStatus: string) => void;
    /** Si el WebSocket esta habilitado */
    enabled?: boolean;
    /** Props especificos a recargar (si no se especifica, recarga todos) */
    reloadProps?: string[];
}

/**
 * Resultado del hook useOrderWebSocket
 */
interface UseOrderWebSocketResult {
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

// Clave para localStorage
const PRINTED_ORDERS_KEY = 'restaurant_printed_orders';

/**
 * Obtener IDs de ordenes ya impresas
 */
const getPrintedOrderIds = (): Set<number> => {
    try {
        const stored = localStorage.getItem(PRINTED_ORDERS_KEY);
        if (stored) {
            const parsed = JSON.parse(stored);
            // Limpiar ordenes antiguas (mas de 24 horas)
            const now = Date.now();
            const filtered = parsed.filter(
                (item: { id: number; timestamp: number }) => now - item.timestamp < 24 * 60 * 60 * 1000
            );
            return new Set(filtered.map((item: { id: number }) => item.id));
        }
    } catch {
        // Ignorar errores de localStorage
    }
    return new Set();
};

/**
 * Guardar ID de orden impresa
 */
const markOrderAsPrinted = (orderId: number): void => {
    try {
        const stored = localStorage.getItem(PRINTED_ORDERS_KEY);
        const orders = stored ? JSON.parse(stored) : [];
        orders.push({ id: orderId, timestamp: Date.now() });
        localStorage.setItem(PRINTED_ORDERS_KEY, JSON.stringify(orders));
    } catch {
        // Ignorar errores de localStorage
    }
};

/**
 * Reproducir sonido de notificacion
 */
const playNotificationSound = (): void => {
    try {
        // Usar Web Audio API para un beep simple
        const AudioContextClass = window.AudioContext || (window as unknown as { webkitAudioContext: typeof AudioContext }).webkitAudioContext;
        const audioContext = new AudioContextClass();
        const oscillator = audioContext.createOscillator();
        const gainNode = audioContext.createGain();

        oscillator.connect(gainNode);
        gainNode.connect(audioContext.destination);

        oscillator.frequency.value = 800; // Frecuencia del tono
        oscillator.type = 'sine';
        gainNode.gain.value = 0.3;

        oscillator.start();

        // Hacer 3 beeps
        setTimeout(() => {
            gainNode.gain.value = 0;
        }, 200);
        setTimeout(() => {
            gainNode.gain.value = 0.3;
        }, 300);
        setTimeout(() => {
            gainNode.gain.value = 0;
        }, 500);
        setTimeout(() => {
            gainNode.gain.value = 0.3;
        }, 600);
        setTimeout(() => {
            oscillator.stop();
            audioContext.close();
        }, 800);
    } catch {
        // Fallback: si no hay soporte de audio, ignorar
        console.log('Audio notification not supported');
    }
};

/**
 * Hook para escuchar eventos de ordenes via WebSocket usando Laravel Echo
 *
 * Este hook reemplaza el polling tradicional por una conexion WebSocket
 * que escucha eventos en tiempo real del canal del restaurante.
 *
 * @example
 * ```tsx
 * const { connectionState, error, reconnect } = useOrderWebSocket({
 *     restaurantId: 123,
 *     autoPrint: true,
 *     onNewOrder: (order) => console.log('Nueva orden:', order),
 *     onOrderStatusChanged: (order, prevStatus) => console.log('Cambio de estado:', order.status),
 * });
 * ```
 */
export function useOrderWebSocket({
    restaurantId,
    autoPrint,
    onNewOrder,
    onOrderStatusChanged,
    enabled = true,
    reloadProps,
}: UseOrderWebSocketOptions): UseOrderWebSocketResult {
    const [connectionState, setConnectionState] = useState<ConnectionState>('disconnected');
    const [lastEventTime, setLastEventTime] = useState<Date | null>(null);
    const [error, setError] = useState<string | null>(null);

    const isMounted = useRef(true);
    const channelRef = useRef<PrivateChannel | null>(null);
    const reconnectAttempts = useRef(0);
    const maxReconnectAttempts = 5;

    /**
     * Manejar evento de actualizacion de estado de orden
     */
    const handleOrderStatusUpdated = useCallback(
        (event: OrderStatusUpdatedEvent) => {
            if (!isMounted.current || isNavigating) {
                return;
            }

            const { order, previous_status, new_status } = event;

            console.log('[WebSocket] Order status updated:', {
                orderId: order.id,
                orderNumber: order.order_number,
                previousStatus: previous_status,
                newStatus: new_status,
            });

            setLastEventTime(new Date());

            // Detectar si es una nueva orden (status cambio a pending o era undefined/null)
            const isNewOrder = new_status === 'pending' && (!previous_status || previous_status === '');

            if (isNewOrder) {
                // Reproducir sonido de notificacion
                playNotificationSound();

                // Notificar callback de nueva orden
                onNewOrder?.(order);

                // Auto-imprimir si esta habilitado
                if (autoPrint) {
                    const printedOrderIds = getPrintedOrderIds();
                    if (!printedOrderIds.has(order.id)) {
                        window.open(`/restaurant/orders/${order.id}?print=1`, '_blank');
                        markOrderAsPrinted(order.id);
                    }
                }
            } else {
                // Notificar callback de cambio de estado
                onOrderStatusChanged?.(order, previous_status);
            }

            // Recargar datos de la pagina
            if (!isNavigating && isMounted.current) {
                const reloadOptions = reloadProps ? { only: reloadProps } : {};
                router.reload(reloadOptions);
            }
        },
        [autoPrint, onNewOrder, onOrderStatusChanged, reloadProps]
    );

    /**
     * Realizar la suscripcion al canal
     */
    const doSubscribe = useCallback(() => {
        if (!restaurantId || !isMounted.current) {
            return;
        }

        try {
            const channelName = `restaurant.${restaurantId}.orders`;
            console.log('[WebSocket] Subscribing to channel:', channelName);

            const channel = window.Echo.private(channelName);
            channelRef.current = channel;

            // Escuchar evento de actualizacion de estado
            channel.listen('.order.status.updated', handleOrderStatusUpdated);

            // Manejar eventos de conexion del canal
            channel.subscribed(() => {
                if (isMounted.current) {
                    console.log('[WebSocket] Successfully subscribed to channel:', channelName);
                    setConnectionState('connected');
                    setError(null);
                    reconnectAttempts.current = 0;
                }
            });

            channel.error((err: unknown) => {
                if (isMounted.current) {
                    console.error('[WebSocket] Channel error:', err);
                    setConnectionState('error');
                    setError('Error en la conexion del canal');
                }
            });
        } catch (err) {
            console.error('[WebSocket] Subscription error:', err);
            if (isMounted.current) {
                setConnectionState('error');
                setError(err instanceof Error ? err.message : 'Error al suscribirse');
            }
        }
    }, [restaurantId, handleOrderStatusUpdated]);

    /**
     * Suscribirse al canal WebSocket (espera a que Pusher este conectado)
     */
    const subscribe = useCallback(() => {
        if (!window.Echo) {
            console.error('[WebSocket] Laravel Echo not initialized');
            setError('Laravel Echo no esta inicializado');
            setConnectionState('error');
            return;
        }

        if (!restaurantId) {
            console.warn('[WebSocket] No restaurantId provided');
            return;
        }

        setConnectionState('connecting');
        setError(null);

        // Acceder al conector Pusher subyacente
        const pusher = (window.Echo.connector as { pusher: { connection: { state: string; bind: (event: string, callback: () => void) => void; unbind: (event: string, callback: () => void) => void } } }).pusher;

        const attemptSubscribe = () => {
            const currentState = pusher?.connection?.state;
            console.log('[WebSocket] Checking Pusher connection state:', currentState);

            if (currentState === 'connected') {
                doSubscribe();
                return true;
            }
            return false;
        };

        // Si ya esta conectado, suscribirse inmediatamente
        if (attemptSubscribe()) {
            return;
        }

        // Usar un intervalo para verificar la conexion (mas robusto que solo el evento)
        let attempts = 0;
        const maxAttempts = 50; // 50 * 200ms = 10 segundos
        const checkInterval = setInterval(() => {
            attempts++;

            if (!isMounted.current || channelRef.current) {
                clearInterval(checkInterval);
                return;
            }

            if (attemptSubscribe()) {
                console.log('[WebSocket] Connected after', attempts, 'attempts');
                clearInterval(checkInterval);
                return;
            }

            if (attempts >= maxAttempts) {
                console.log('[WebSocket] Max attempts reached, forcing subscribe...');
                clearInterval(checkInterval);
                doSubscribe();
            }
        }, 200);

        // Tambien escuchar el evento connected como backup
        const onConnected = () => {
            console.log('[WebSocket] Pusher connected event received');
            if (isMounted.current && !channelRef.current) {
                clearInterval(checkInterval);
                doSubscribe();
            }
            pusher.connection.unbind('connected', onConnected);
        };

        pusher.connection.bind('connected', onConnected);

        // Cleanup en caso de que el componente se desmonte
        return () => {
            clearInterval(checkInterval);
            pusher.connection.unbind('connected', onConnected);
        };
    }, [restaurantId, doSubscribe]);

    /**
     * Desuscribirse del canal WebSocket
     */
    const unsubscribe = useCallback(() => {
        if (channelRef.current && window.Echo) {
            const channelName = `restaurant.${restaurantId}.orders`;
            console.log('[WebSocket] Unsubscribing from channel:', channelName);

            try {
                window.Echo.leave(channelName);
            } catch (err) {
                console.error('[WebSocket] Error leaving channel:', err);
            }

            channelRef.current = null;
        }
        setConnectionState('disconnected');
    }, [restaurantId]);

    /**
     * Reconectar manualmente
     */
    const reconnect = useCallback(() => {
        if (reconnectAttempts.current >= maxReconnectAttempts) {
            console.warn('[WebSocket] Max reconnect attempts reached');
            setError('Maximo de intentos de reconexion alcanzado');
            return;
        }

        reconnectAttempts.current += 1;
        console.log('[WebSocket] Attempting reconnect, attempt:', reconnectAttempts.current);

        unsubscribe();

        // Esperar un poco antes de reconectar
        setTimeout(() => {
            if (isMounted.current && enabled) {
                subscribe();
            }
        }, 1000 * reconnectAttempts.current); // Backoff exponencial
    }, [enabled, subscribe, unsubscribe]);

    // Refs para guardar las funciones actuales sin causar re-renders
    const subscribeRef = useRef(subscribe);
    const unsubscribeRef = useRef(unsubscribe);
    const reconnectRef = useRef(reconnect);

    // Actualizar refs cuando las funciones cambien
    useEffect(() => {
        subscribeRef.current = subscribe;
        unsubscribeRef.current = unsubscribe;
        reconnectRef.current = reconnect;
    });

    /**
     * Efecto principal para manejar la suscripcion
     * Solo se re-ejecuta cuando cambia enabled o restaurantId
     */
    useEffect(() => {
        isMounted.current = true;

        if (!enabled || !restaurantId) {
            unsubscribeRef.current();
            return;
        }

        // Pequeño delay para asegurar que todo esté listo
        const timeoutId = setTimeout(() => {
            if (isMounted.current) {
                subscribeRef.current();
            }
        }, 100);

        // Manejar reconexion cuando la ventana vuelve a estar visible
        const handleVisibilityChange = () => {
            if (document.visibilityState === 'visible' && !channelRef.current) {
                console.log('[WebSocket] Page became visible, reconnecting...');
                reconnectRef.current();
            }
        };

        // Manejar reconexion cuando vuelve la conexion a internet
        const handleOnline = () => {
            if (!channelRef.current) {
                console.log('[WebSocket] Back online, reconnecting...');
                reconnectRef.current();
            }
        };

        document.addEventListener('visibilitychange', handleVisibilityChange);
        window.addEventListener('online', handleOnline);

        return () => {
            isMounted.current = false;
            clearTimeout(timeoutId);
            document.removeEventListener('visibilitychange', handleVisibilityChange);
            window.removeEventListener('online', handleOnline);
            unsubscribeRef.current();
        };
    }, [enabled, restaurantId]);

    return {
        connectionState,
        lastEventTime,
        error,
        reconnect,
    };
}

export type {
    ConnectionState,
    OrderStatusUpdatedEvent,
    UseOrderWebSocketOptions,
    UseOrderWebSocketResult,
    WebSocketOrder,
};
