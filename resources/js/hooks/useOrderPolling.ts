import { useCallback, useEffect, useRef, useState } from 'react';
import { router } from '@inertiajs/react';

interface PollingOrder {
    id: number;
    order_number: string;
    status: string;
    created_at: string;
}

interface PollingResponse {
    orders: PollingOrder[];
    timestamp: string;
}

interface UseOrderPollingOptions {
    /** Intervalo de polling en segundos */
    intervalSeconds: number;
    /** Auto-imprimir nuevas órdenes */
    autoPrint: boolean;
    /** Callback cuando hay nuevas órdenes */
    onNewOrders?: (newOrders: PollingOrder[]) => void;
    /** Callback cuando hay cambios en el estado de órdenes */
    onOrdersChanged?: () => void;
    /** Si el polling está habilitado */
    enabled?: boolean;
    /** Props específicos a recargar (si no se especifica, recarga todos) */
    reloadProps?: string[];
}

// Clave para localStorage
const PRINTED_ORDERS_KEY = 'restaurant_printed_orders';

// Obtener IDs de órdenes ya impresas
const getPrintedOrderIds = (): Set<number> => {
    try {
        const stored = localStorage.getItem(PRINTED_ORDERS_KEY);
        if (stored) {
            const parsed = JSON.parse(stored);
            // Limpiar órdenes antiguas (más de 24 horas)
            const now = Date.now();
            const filtered = parsed.filter((item: { id: number; timestamp: number }) =>
                now - item.timestamp < 24 * 60 * 60 * 1000
            );
            return new Set(filtered.map((item: { id: number }) => item.id));
        }
    } catch {
        // Ignorar errores de localStorage
    }
    return new Set();
};

// Guardar ID de orden impresa
const markOrderAsPrinted = (orderId: number) => {
    try {
        const stored = localStorage.getItem(PRINTED_ORDERS_KEY);
        const orders = stored ? JSON.parse(stored) : [];
        orders.push({ id: orderId, timestamp: Date.now() });
        localStorage.setItem(PRINTED_ORDERS_KEY, JSON.stringify(orders));
    } catch {
        // Ignorar errores de localStorage
    }
};

// Reproducir sonido de notificación
const playNotificationSound = () => {
    try {
        // Usar Web Audio API para un beep simple
        const audioContext = new (window.AudioContext || (window as any).webkitAudioContext)();
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

export function useOrderPolling({
    intervalSeconds,
    autoPrint,
    onNewOrders,
    onOrdersChanged,
    enabled = true,
    reloadProps,
}: UseOrderPollingOptions) {
    const [isPolling, setIsPolling] = useState(false);
    const [lastPollTime, setLastPollTime] = useState<Date | null>(null);
    const [error, setError] = useState<string | null>(null);
    const previousOrderIds = useRef<Set<number>>(new Set());
    const isFirstPoll = useRef(true);

    const poll = useCallback(async () => {
        if (!enabled) return;

        try {
            setIsPolling(true);
            setError(null);

            const response = await fetch('/restaurant/poll', {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }

            const data: PollingResponse = await response.json();
            const currentOrderIds = new Set(data.orders.map(o => o.id));
            const printedOrderIds = getPrintedOrderIds();

            // Detectar nuevas órdenes (solo si no es el primer poll)
            if (!isFirstPoll.current) {
                const newOrders = data.orders.filter(
                    order => !previousOrderIds.current.has(order.id) && order.status === 'pending'
                );

                if (newOrders.length > 0) {
                    // Reproducir sonido
                    playNotificationSound();

                    // Notificar callback
                    onNewOrders?.(newOrders);

                    // Auto-imprimir órdenes no impresas
                    if (autoPrint) {
                        for (const order of newOrders) {
                            if (!printedOrderIds.has(order.id)) {
                                // Abrir en nueva pestaña
                                window.open(
                                    `/restaurant/orders/${order.id}?print=1`,
                                    '_blank'
                                );
                                markOrderAsPrinted(order.id);
                            }
                        }
                    }

                    // Recargar la página para mostrar las nuevas órdenes
                    const reloadOptions = reloadProps ? { only: reloadProps } : {};
                    router.reload(reloadOptions);
                }

                // Detectar cambios en estados (para actualizar la UI)
                const hasChanges =
                    currentOrderIds.size !== previousOrderIds.current.size ||
                    data.orders.some(order => !previousOrderIds.current.has(order.id));

                if (hasChanges) {
                    onOrdersChanged?.();
                    const reloadOptions = reloadProps ? { only: reloadProps } : {};
                    router.reload(reloadOptions);
                }
            }

            previousOrderIds.current = currentOrderIds;
            isFirstPoll.current = false;
            setLastPollTime(new Date());
        } catch (err) {
            setError(err instanceof Error ? err.message : 'Error de conexión');
            console.error('Polling error:', err);
        } finally {
            setIsPolling(false);
        }
    }, [enabled, autoPrint, onNewOrders, onOrdersChanged, reloadProps]);

    useEffect(() => {
        if (!enabled) return;

        // Poll inicial
        poll();

        // Configurar intervalo
        const intervalId = setInterval(poll, intervalSeconds * 1000);

        return () => {
            clearInterval(intervalId);
        };
    }, [enabled, intervalSeconds, poll]);

    return {
        isPolling,
        lastPollTime,
        error,
        manualPoll: poll,
    };
}
