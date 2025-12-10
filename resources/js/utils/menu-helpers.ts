/**
 * Helpers para el sistema de menú
 */

import type { ValidityType, PriceSet, PriceRange, BadgeConfig, ItemBadge } from '@/types/menu';
import { formatWeekdays } from '@/constants/weekdays';

// ============================================
// PRICE HELPERS
// ============================================

/**
 * Calcula el rango de precios de un set de precios
 */
export function calculatePriceRange(prices: Partial<PriceSet>): PriceRange | null {
    const values = [
        prices.precio_pickup_capital,
        prices.precio_domicilio_capital,
        prices.precio_pickup_interior,
        prices.precio_domicilio_interior,
    ]
        .map((v) => (typeof v === 'string' ? parseFloat(v) : v))
        .filter((v): v is number => v !== null && v !== undefined && !isNaN(v) && v > 0);

    if (values.length === 0) return null;

    return {
        min: Math.min(...values),
        max: Math.max(...values),
    };
}

/**
 * Formatea un rango de precios para mostrar
 * @example formatPriceRange({ min: 25, max: 45 }) => "Q25.00 - Q45.00"
 */
export function formatPriceRange(range: PriceRange | null): string {
    if (!range) return 'Sin precio';
    if (range.min === range.max) {
        return `Q${range.min.toFixed(2)}`;
    }
    return `Q${range.min.toFixed(2)} - Q${range.max.toFixed(2)}`;
}

/**
 * Formatea un precio individual
 */
export function formatPrice(price: number | string | null | undefined): string {
    if (price === null || price === undefined) return '-';
    const num = typeof price === 'string' ? parseFloat(price) : price;
    if (isNaN(num)) return '-';
    return `Q${num.toFixed(2)}`;
}

/**
 * Convierte precio a string para formularios
 */
export function priceToString(price: number | null | undefined): string {
    if (price === null || price === undefined) return '';
    return price.toString();
}

/**
 * Convierte string a número para guardar
 */
export function stringToPrice(value: string): number | null {
    if (!value || value.trim() === '') return null;
    const num = parseFloat(value);
    return isNaN(num) ? null : num;
}

/**
 * Valida que todos los precios estén completos
 */
export function validatePrices(prices: Partial<PriceSet>): boolean {
    return [
        prices.precio_pickup_capital,
        prices.precio_domicilio_capital,
        prices.precio_pickup_interior,
        prices.precio_domicilio_interior,
    ].every((v) => {
        if (v === null || v === undefined || v === '') return false;
        const num = typeof v === 'string' ? parseFloat(v) : v;
        return !isNaN(num) && num >= 0;
    });
}

// ============================================
// BADGE/VALIDITY HELPERS
// ============================================

/**
 * Obtiene un resumen legible de la configuración de validez
 */
export function getValiditySummary(config: BadgeConfig): string {
    switch (config.validity_type) {
        case 'permanent':
            return 'Permanente';
        case 'date_range':
            if (config.valid_from && config.valid_until) {
                return `${formatDate(config.valid_from)} - ${formatDate(config.valid_until)}`;
            }
            if (config.valid_from) {
                return `Desde ${formatDate(config.valid_from)}`;
            }
            if (config.valid_until) {
                return `Hasta ${formatDate(config.valid_until)}`;
            }
            return 'Rango de fechas';
        case 'weekdays':
            if (config.weekdays.length === 0) {
                return 'Sin días seleccionados';
            }
            if (config.weekdays.length === 7) {
                return 'Todos los días';
            }
            return formatWeekdays(config.weekdays);
        default:
            return '';
    }
}

/**
 * Obtiene el resumen de validez de un badge asignado
 */
export function getBadgeValiditySummary(badge: ItemBadge): string {
    return getValiditySummary({
        validity_type: badge.validity_type,
        valid_from: badge.valid_from ?? '',
        valid_until: badge.valid_until ?? '',
        weekdays: badge.weekdays ?? [],
    });
}

/**
 * Valida la configuración de un badge
 */
export function validateBadgeConfig(config: BadgeConfig): string[] {
    const errors: string[] = [];

    if (config.validity_type === 'date_range') {
        if (!config.valid_from && !config.valid_until) {
            errors.push('Debe especificar al menos una fecha');
        }
        if (config.valid_from && config.valid_until && config.valid_from > config.valid_until) {
            errors.push('La fecha de inicio debe ser anterior a la fecha de fin');
        }
    }

    if (config.validity_type === 'weekdays') {
        if (config.weekdays.length === 0) {
            errors.push('Debe seleccionar al menos un día');
        }
    }

    return errors;
}

/**
 * Crea una configuración de badge por defecto
 */
export function createDefaultBadgeConfig(): BadgeConfig {
    return {
        validity_type: 'permanent',
        valid_from: '',
        valid_until: '',
        weekdays: [],
    };
}

// ============================================
// DATE HELPERS
// ============================================

/**
 * Formatea una fecha ISO a formato legible
 */
export function formatDate(dateString: string | null | undefined): string {
    if (!dateString) return '';
    try {
        const date = new Date(dateString);
        return date.toLocaleDateString('es-GT', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
        });
    } catch {
        return dateString;
    }
}

/**
 * Formatea fecha y hora
 */
export function formatDateTime(dateString: string | null | undefined): string {
    if (!dateString) return '';
    try {
        const date = new Date(dateString);
        return date.toLocaleDateString('es-GT', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
    } catch {
        return dateString;
    }
}

/**
 * Formatea hora solamente
 */
export function formatTime(timeString: string | null | undefined): string {
    if (!timeString) return '';
    // Si es formato HH:MM:SS, convertir a HH:MM
    if (timeString.includes(':')) {
        const parts = timeString.split(':');
        return `${parts[0]}:${parts[1]}`;
    }
    return timeString;
}

/**
 * Obtiene la fecha de hoy en formato YYYY-MM-DD
 */
export function getTodayISO(): string {
    return new Date().toISOString().split('T')[0];
}

// ============================================
// VARIANT HELPERS
// ============================================

/**
 * Genera un ID temporal para variantes nuevas
 */
export function generateTempVariantId(): string {
    return `temp_${Date.now()}_${Math.random().toString(36).substring(2, 9)}`;
}

/**
 * Verifica si un ID es temporal
 */
export function isTempId(id: number | string | undefined): boolean {
    if (id === undefined) return true;
    return typeof id === 'string' && id.startsWith('temp_');
}

/**
 * Filtra variantes activas
 */
export function getActiveVariants<T extends { is_active: boolean }>(variants: T[]): T[] {
    return variants.filter((v) => v.is_active);
}

// ============================================
// FORM HELPERS
// ============================================

/**
 * Prepara un FormData con archivos e información
 */
export function prepareFormData(
    data: Record<string, unknown>,
    options?: {
        imageFile?: File | null;
        imageFieldName?: string;
        removeImage?: boolean;
        method?: 'POST' | 'PUT';
    }
): FormData {
    const formData = new FormData();

    // Agregar método si es PUT
    if (options?.method === 'PUT') {
        formData.append('_method', 'PUT');
    }

    // Agregar campos de datos
    Object.entries(data).forEach(([key, value]) => {
        if (value === null || value === undefined) return;

        if (Array.isArray(value)) {
            formData.append(key, JSON.stringify(value));
        } else if (typeof value === 'object') {
            formData.append(key, JSON.stringify(value));
        } else if (typeof value === 'boolean') {
            formData.append(key, value ? '1' : '0');
        } else {
            formData.append(key, String(value));
        }
    });

    // Agregar imagen si existe
    if (options?.imageFile) {
        formData.append(options.imageFieldName ?? 'image', options.imageFile);
    }

    // Flag para remover imagen
    if (options?.removeImage) {
        formData.append('remove_image', '1');
    }

    return formData;
}

// ============================================
// SORT HELPERS
// ============================================

/**
 * Genera el nuevo orden después de un drag & drop
 */
export function reorderItems<T extends { id: number | string }>(
    items: T[],
    activeId: number | string,
    overId: number | string
): T[] {
    const oldIndex = items.findIndex((item) => item.id === activeId);
    const newIndex = items.findIndex((item) => item.id === overId);

    if (oldIndex === -1 || newIndex === -1) return items;

    const result = [...items];
    const [removed] = result.splice(oldIndex, 1);
    result.splice(newIndex, 0, removed);

    return result;
}

/**
 * Extrae los IDs ordenados de una lista
 */
export function extractOrderedIds<T extends { id: number | string }>(items: T[]): (number | string)[] {
    return items.map((item) => item.id);
}
