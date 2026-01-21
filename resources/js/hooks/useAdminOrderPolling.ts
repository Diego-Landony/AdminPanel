import { useCallback, useEffect, useRef, useState, useMemo } from 'react';
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

interface UseAdminOrderPollingOptions {
    /** Intervalo de polling en segundos */
    intervalSeconds: number;
    /** Si el polling está habilitado */
    enabled?: boolean;
    /** Props específicos a recargar (si no se especifica, recarga todos) */
    reloadProps?: string[];
    /** Restaurant ID para filtrar (opcional) */
    restaurantId?: number | null;
    /** Callback cuando hay cambios */
    onOrdersChanged?: () => void;
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
 * Hook para polling de ordenes en el panel admin.
 * Versión simplificada sin auto-impresión.
 */
export function useAdminOrderPolling({
    intervalSeconds,
    enabled = true,
    reloadProps,
    restaurantId,
    onOrdersChanged,
}: UseAdminOrderPollingOptions) {
    const [isPolling, setIsPolling] = useState(false);
    const [lastPollTime, setLastPollTime] = useState<Date | null>(null);
    const [error, setError] = useState<string | null>(null);
    const previousOrderIds = useRef<Set<number>>(new Set());
    const isFirstPoll = useRef(true);
    const isMounted = useRef(true);

    // Memoize reloadProps to prevent unnecessary callback recreations
    const stableReloadProps = useMemo(
        () => reloadProps,
        // eslint-disable-next-line react-hooks/exhaustive-deps
        [reloadProps?.join(',')]
    );

    const poll = useCallback(async () => {
        // Don't poll if disabled, navigating, or unmounted
        if (!enabled || isNavigating || !isMounted.current) return;

        try {
            setIsPolling(true);
            setError(null);

            const params = new URLSearchParams();
            if (restaurantId) {
                params.append('restaurant_id', restaurantId.toString());
            }

            const response = await fetch(`/orders-poll?${params.toString()}`, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            });

            // Check again after fetch in case navigation started
            if (!isMounted.current || isNavigating) return;

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }

            const data: PollingResponse = await response.json();
            const currentOrderIds = new Set(data.orders.map(o => o.id));

            // Detectar cambios (solo si no es el primer poll)
            if (!isFirstPoll.current && !isNavigating && isMounted.current) {
                const hasNewOrders = data.orders.some(
                    order => !previousOrderIds.current.has(order.id)
                );
                const hasRemovedOrders = [...previousOrderIds.current].some(
                    id => !currentOrderIds.has(id)
                );
                const hasChanges = hasNewOrders || hasRemovedOrders ||
                    currentOrderIds.size !== previousOrderIds.current.size;

                if (hasChanges && !isNavigating && isMounted.current) {
                    onOrdersChanged?.();
                    const reloadOptions = stableReloadProps ? { only: stableReloadProps } : {};
                    router.reload(reloadOptions);
                }
            }

            previousOrderIds.current = currentOrderIds;
            isFirstPoll.current = false;
            if (isMounted.current) {
                setLastPollTime(new Date());
            }
        } catch (err) {
            if (isMounted.current) {
                setError(err instanceof Error ? err.message : 'Error de conexión');
            }
            console.error('Admin polling error:', err);
        } finally {
            if (isMounted.current) {
                setIsPolling(false);
            }
        }
    }, [enabled, restaurantId, onOrdersChanged, stableReloadProps]);

    useEffect(() => {
        isMounted.current = true;

        if (!enabled) return;

        // Poll inicial
        poll();

        // Configurar intervalo
        const intervalId = setInterval(poll, intervalSeconds * 1000);

        return () => {
            isMounted.current = false;
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
