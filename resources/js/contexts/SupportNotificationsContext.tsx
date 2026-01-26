import { createContext, useContext, type ReactNode } from 'react';
import { usePage } from '@inertiajs/react';
import { useSupportAdminNotifications, type SupportStats } from '@/hooks';
import { type SharedData } from '@/types';

interface SupportNotificationsContextType {
    stats: SupportStats;
    refreshStats: () => void;
    isConnected: boolean;
}

const SupportNotificationsContext = createContext<SupportNotificationsContextType | null>(null);

interface SupportNotificationsProviderProps {
    children: ReactNode;
    enabled?: boolean;
}

export function SupportNotificationsProvider({ children, enabled = true }: SupportNotificationsProviderProps) {
    // Obtener el userId del usuario autenticado via Inertia props
    const { auth } = usePage<SharedData>().props;
    const userId = auth?.user?.id ?? null;

    const { stats, refreshStats, connectionState } = useSupportAdminNotifications({
        enabled,
        userId,
    });

    return (
        <SupportNotificationsContext.Provider
            value={{
                stats,
                refreshStats,
                isConnected: connectionState === 'connected',
            }}
        >
            {children}
        </SupportNotificationsContext.Provider>
    );
}

export function useSupportNotifications(): SupportNotificationsContextType {
    const context = useContext(SupportNotificationsContext);

    if (!context) {
        // Return default values if not wrapped in provider
        return {
            stats: { unreadTickets: 0, pendingAccessIssues: 0 },
            refreshStats: () => {},
            isConnected: false,
        };
    }

    return context;
}
