/**
 * Helpers para el panel de restaurante
 */

/**
 * Formatea una fecha/hora para mostrar en formato guatemalteco
 */
export const formatDateTime = (dateString: string | null): string => {
    if (!dateString) return 'N/A';
    return new Date(dateString).toLocaleDateString('es-GT', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        timeZone: 'America/Guatemala',
    });
};

/**
 * Formatea una tarjeta SubwayCard con guiones
 * Ejemplo: 12345678901 -> 1234-5678-901
 */
export const formatSubwayCard = (card: string | null | undefined): string => {
    if (!card) return '';
    if (card.length === 11) {
        return `${card.slice(0, 4)}-${card.slice(4, 8)}-${card.slice(8)}`;
    }
    return card;
};

/**
 * Calcula el tiempo transcurrido desde una fecha
 * Retorna strings como "Ahora", "hace 5 min", "hace 1h 30m"
 */
export const getTimeAgo = (dateString: string): string => {
    const now = new Date();
    const created = new Date(dateString);
    const diffMs = now.getTime() - created.getTime();
    const diffMins = Math.floor(diffMs / 60000);

    if (diffMins < 1) return 'Ahora';
    if (diffMins < 60) return `hace ${diffMins} min`;
    const hours = Math.floor(diffMins / 60);
    const mins = diffMins % 60;
    if (mins === 0) return `hace ${hours}h`;
    return `hace ${hours}h ${mins}m`;
};

/**
 * Formatea una fecha a hora HH:MM
 */
export const formatTime = (dateString: string): string => {
    const date = new Date(dateString);
    return date.toLocaleTimeString('es-GT', {
        hour: '2-digit',
        minute: '2-digit',
        hour12: false,
    });
};

/**
 * Obtiene los minutos transcurridos desde una fecha
 */
export const getMinutesElapsed = (dateString: string): number => {
    const now = new Date();
    const created = new Date(dateString);
    return Math.floor((now.getTime() - created.getTime()) / 60000);
};

/**
 * Obtiene el color de urgencia basado en minutos transcurridos
 */
export const getTimeUrgencyColor = (minutes: number): string => {
    if (minutes < 10) return 'text-green-600 dark:text-green-400';
    if (minutes < 20) return 'text-yellow-600 dark:text-yellow-400';
    if (minutes < 30) return 'text-orange-600 dark:text-orange-400';
    return 'text-red-600 dark:text-red-400 font-semibold';
};
