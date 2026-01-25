import { createContext, useContext, type ReactNode } from 'react';
import { useSupportAdminNotifications, type SupportStats } from '@/hooks';

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
    const { stats, refreshStats, connectionState } = useSupportAdminNotifications(enabled);

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
