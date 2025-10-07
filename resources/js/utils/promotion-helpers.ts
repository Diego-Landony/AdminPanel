/**
 * Utilidades de formateo para promociones
 */

/**
 * Formatea los días de la semana en un formato legible
 * @param weekdays Array de números (1-7) donde 1=Lunes, 7=Domingo
 * @returns String formateado con los días
 */
export const formatWeekdays = (weekdays: number[] | null): string => {
    if (!weekdays || weekdays.length === 0) {
        return 'Sin días definidos';
    }

    // dayNames usa índice 1-7 (Lun=1, Dom=7) para coincidir con el backend
    const dayNames: Record<number, string> = {
        1: 'Lun',
        2: 'Mar',
        3: 'Mié',
        4: 'Jue',
        5: 'Vie',
        6: 'Sáb',
        7: 'Dom',
    };

    const sortedDays = [...weekdays].sort((a, b) => a - b);

    // Si son todos los días, mostrar "Todos los días"
    if (sortedDays.length === 7) {
        return 'Todos los días';
    }

    // Si son días consecutivos, mostrar rango
    const isConsecutive = sortedDays.every((day, i) =>
        i === 0 || day === sortedDays[i - 1] + 1
    );

    if (isConsecutive && sortedDays.length > 2) {
        return `${dayNames[sortedDays[0]]} - ${dayNames[sortedDays[sortedDays.length - 1]]}`;
    }

    // Mostrar días separados por comas
    return sortedDays.map(d => dayNames[d]).join(', ');
};

/**
 * Formatea un rango de fechas para promociones
 * @param from Fecha de inicio
 * @param until Fecha de fin
 * @returns String formateado con el rango o null si no hay fechas
 */
export const formatPromotionDateRange = (
    from: string | null,
    until: string | null
): string | null => {
    if (!from && !until) {
        return null;
    }

    const fromDate = from ? new Date(from).toLocaleDateString('es-GT') : '';
    const untilDate = until ? new Date(until).toLocaleDateString('es-GT') : '';

    if (fromDate && untilDate) {
        return `${fromDate} - ${untilDate}`;
    }
    if (fromDate) {
        return `Desde ${fromDate}`;
    }
    if (untilDate) {
        return `Hasta ${untilDate}`;
    }
    return null;
};

/**
 * Formatea un rango de horarios para promociones
 * @param from Hora de inicio
 * @param until Hora de fin
 * @returns String formateado con el rango o null si no hay horarios
 */
export const formatPromotionTimeRange = (
    from: string | null,
    until: string | null
): string | null => {
    if (!from && !until) {
        return null;
    }

    const fromTime = from?.slice(0, 5) || '';
    const untilTime = until?.slice(0, 5) || '';

    if (fromTime && untilTime) {
        return `${fromTime} - ${untilTime}`;
    }
    if (fromTime) {
        return `Desde ${fromTime}`;
    }
    if (untilTime) {
        return `Hasta ${untilTime}`;
    }
    return null;
};
