/**
 * Genera un ID único compatible con todos los navegadores
 * Fallback para crypto.randomUUID() cuando no está disponible
 */
export function generateUniqueId(): string {
    // Intentar usar crypto.randomUUID si está disponible
    if (typeof crypto !== 'undefined' && typeof crypto.randomUUID === 'function') {
        return crypto.randomUUID();
    }

    // Fallback: generar un UUID v4 compatible
    return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function (c) {
        const r = (Math.random() * 16) | 0;
        const v = c === 'x' ? r : (r & 0x3) | 0x8;
        return v.toString(16);
    });
}
