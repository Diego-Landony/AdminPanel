/**
 * Tipos de estado de usuario
 */
export type UserStatus = 'never' | 'online' | 'recent' | 'offline';

/**
 * Configuración de colores para badges de estado
 */
export interface StatusConfig {
    color: string;
    bgColor: string;
    textColor: string;
    label: string;
    description: string;
}

/**
 * Mapeo de estados a configuración visual
 */
const STATUS_CONFIG: Record<UserStatus, StatusConfig> = {
    never: {
        color: 'gray',
        bgColor: 'bg-gray-100 dark:bg-gray-800',
        textColor: 'text-gray-700 dark:text-gray-300',
        label: 'Sin actividad',
        description: 'Sin registro de actividad',
    },
    online: {
        color: 'green',
        bgColor: 'bg-green-100 dark:bg-green-900/30',
        textColor: 'text-green-700 dark:text-green-400',
        label: 'En línea',
        description: 'Activo ahora',
    },
    recent: {
        color: 'yellow',
        bgColor: 'bg-yellow-100 dark:bg-yellow-900/30',
        textColor: 'text-yellow-700 dark:text-yellow-400',
        label: 'Reciente',
        description: 'Activo hace poco',
    },
    offline: {
        color: 'gray',
        bgColor: 'bg-gray-100 dark:bg-gray-800',
        textColor: 'text-gray-700 dark:text-gray-300',
        label: 'Inactivo',
        description: 'Sin actividad reciente',
    },
};

/**
 * Resultado del hook useOnlineStatus
 */
export interface OnlineStatusResult {
    /** Configuración visual del estado */
    config: StatusConfig;
    /** Indica si el usuario está en línea */
    isOnline: boolean;
    /** Color del badge */
    badgeColor: string;
    /** Color de fondo del badge */
    badgeBgColor: string;
    /** Color del texto del badge */
    badgeTextColor: string;
    /** Etiqueta del estado */
    label: string;
    /** Descripción del estado */
    description: string;
    /** Estado original */
    status: UserStatus;
}

/**
 * Hook para obtener información de visualización del estado online de un usuario/cliente
 *
 * @param status - Estado del usuario ('never' | 'online' | 'recent' | 'offline')
 * @returns Objeto con configuración visual y helpers
 *
 * @example
 * ```tsx
 * const { isOnline, badgeColor, label, description } = useOnlineStatus('online');
 *
 * return (
 *   <Badge className={badgeColor}>
 *     {label}
 *   </Badge>
 * );
 * ```
 */
export function useOnlineStatus(status: UserStatus): OnlineStatusResult {
    const config = STATUS_CONFIG[status] || STATUS_CONFIG.never;

    return {
        config,
        isOnline: status === 'online',
        badgeColor: config.color,
        badgeBgColor: config.bgColor,
        badgeTextColor: config.textColor,
        label: config.label,
        description: config.description,
        status,
    };
}

/**
 * Helper para obtener solo el color del badge dado un status
 */
export function getStatusBadgeColor(status: UserStatus): string {
    return STATUS_CONFIG[status]?.color || STATUS_CONFIG.never.color;
}

/**
 * Helper para obtener solo la etiqueta dado un status
 */
export function getStatusLabel(status: UserStatus): string {
    return STATUS_CONFIG[status]?.label || STATUS_CONFIG.never.label;
}

/**
 * Helper para verificar si un status indica que está online
 */
export function isUserOnline(status: UserStatus): boolean {
    return status === 'online';
}

/**
 * Helper para obtener clases de Tailwind para badge basado en status
 */
export function getStatusBadgeClasses(status: UserStatus): string {
    const config = STATUS_CONFIG[status] || STATUS_CONFIG.never;
    return `${config.bgColor} ${config.textColor}`;
}
