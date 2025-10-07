/**
 * Utilidades de formateo para fechas, números y texto
 */

/**
 * Formatea una fecha de manera legible en hora de Guatemala
 */
export const formatDate = (dateString: string | null): string => {
    if (!dateString) return 'N/A';

    try {
        return new Date(dateString).toLocaleDateString('es-GT', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
            timeZone: 'America/Guatemala',
        });
    } catch {
        return 'Fecha inválida';
    }
};

/**
 * Formatea una fecha solo con fecha (sin hora)
 */
export const formatDateOnly = (dateString: string | null): string => {
    if (!dateString) return 'N/A';

    try {
        return new Date(dateString).toLocaleDateString('es-GT', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            timeZone: 'America/Guatemala',
        });
    } catch {
        return 'Fecha inválida';
    }
};

/**
 * Formatea números con separadores de miles
 */
export const formatNumber = (num: number): string => {
    return num.toLocaleString('es-GT');
};

/**
 * Calcula la edad basada en una fecha de nacimiento
 */
export const calculateAge = (birthDate: string): number => {
    const birth = new Date(birthDate);
    const now = new Date();
    return Math.floor((now.getTime() - birth.getTime()) / (365.25 * 24 * 60 * 60 * 1000));
};

/**
 * Calcula los días transcurridos desde una fecha
 */
export const daysSince = (dateString: string): number => {
    const date = new Date(dateString);
    const now = new Date();
    return Math.floor((now.getTime() - date.getTime()) / (24 * 60 * 60 * 1000));
};

/**
 * Trunca texto a un número específico de caracteres
 */
export const truncateText = (text: string, maxLength: number): string => {
    if (text.length <= maxLength) return text;
    return text.substring(0, maxLength) + '...';
};

/**
 * Formatea puntos con sufijo 'pts'
 */
export const formatPoints = (points: number): string => {
    return `${formatNumber(points)} pts`;
};

/**
 * Formatea moneda con el símbolo de Quetzal
 */
export const formatCurrency = (amount: number, showSymbol: boolean = true): string => {
    const formatted = formatNumber(amount);
    return showSymbol ? `Q${formatted}` : formatted;
};
