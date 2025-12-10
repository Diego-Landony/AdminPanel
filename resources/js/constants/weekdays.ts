/**
 * Constantes de días de la semana
 * Centralizadas para evitar duplicación
 */

export interface WeekdayOption {
    value: number;
    label: string;
    shortLabel: string;
}

/**
 * Días de la semana según ISO-8601 (1 = Lunes, 7 = Domingo)
 */
export const WEEKDAYS: WeekdayOption[] = [
    { value: 1, label: 'Lunes', shortLabel: 'Lun' },
    { value: 2, label: 'Martes', shortLabel: 'Mar' },
    { value: 3, label: 'Miércoles', shortLabel: 'Mié' },
    { value: 4, label: 'Jueves', shortLabel: 'Jue' },
    { value: 5, label: 'Viernes', shortLabel: 'Vie' },
    { value: 6, label: 'Sábado', shortLabel: 'Sáb' },
    { value: 7, label: 'Domingo', shortLabel: 'Dom' },
];

/**
 * Obtiene el label completo de un día
 */
export function getWeekdayLabel(value: number): string {
    return WEEKDAYS.find((w) => w.value === value)?.label ?? '';
}

/**
 * Obtiene el label corto de un día
 */
export function getWeekdayShortLabel(value: number): string {
    return WEEKDAYS.find((w) => w.value === value)?.shortLabel ?? '';
}

/**
 * Formatea un array de días como string legible
 * @example formatWeekdays([1, 2, 3]) => "Lunes, Martes, Miércoles"
 */
export function formatWeekdays(weekdays: number[] | null | undefined): string {
    if (!weekdays || weekdays.length === 0) return '';
    return weekdays.map(getWeekdayLabel).filter(Boolean).join(', ');
}

/**
 * Formatea un array de días con labels cortos
 * @example formatWeekdaysShort([1, 2, 3]) => "Lun, Mar, Mié"
 */
export function formatWeekdaysShort(weekdays: number[] | null | undefined): string {
    if (!weekdays || weekdays.length === 0) return '';
    return weekdays.map(getWeekdayShortLabel).filter(Boolean).join(', ');
}

/**
 * Toggle de un día en el array (agrega si no existe, remueve si existe)
 */
export function toggleWeekday(weekdays: number[], day: number): number[] {
    if (weekdays.includes(day)) {
        return weekdays.filter((d) => d !== day);
    }
    return [...weekdays, day].sort((a, b) => a - b);
}

/**
 * Verifica si todos los días están seleccionados
 */
export function allWeekdaysSelected(weekdays: number[]): boolean {
    return WEEKDAYS.every((w) => weekdays.includes(w.value));
}

/**
 * Selecciona todos los días
 */
export function selectAllWeekdays(): number[] {
    return WEEKDAYS.map((w) => w.value);
}

/**
 * Días laborales (Lunes a Viernes)
 */
export function selectWeekdaysOnly(): number[] {
    return [1, 2, 3, 4, 5];
}

/**
 * Fin de semana (Sábado y Domingo)
 */
export function selectWeekendOnly(): number[] {
    return [6, 7];
}
