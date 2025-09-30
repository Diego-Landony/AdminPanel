import { useCallback, useEffect, useRef, useState } from 'react';

/**
 * Configuración del hook useFormPersistence
 */
export interface FormPersistenceConfig<T> {
    /** Clave única para identificar el formulario en localStorage */
    storageKey: string;
    /** Valores iniciales del formulario */
    initialValues: T;
    /** Intervalo de auto-guardado en milisegundos (default: 30000ms = 30s) */
    autoSaveInterval?: number;
    /** Habilitar auto-guardado automático */
    autoSave?: boolean;
    /** Callback cuando se restaura un borrador */
    onRestore?: (values: T) => void;
    /** Callback cuando se guarda un borrador */
    onSave?: (values: T) => void;
    /** Callback cuando se limpia el borrador */
    onClear?: () => void;
}

/**
 * Estado del formulario persistente
 */
export interface FormPersistenceState<T> {
    /** Valores actuales del formulario */
    values: T;
    /** Indica si hay un borrador guardado */
    hasDraft: boolean;
    /** Timestamp del último guardado */
    lastSavedAt: Date | null;
    /** Indica si se está guardando */
    isSaving: boolean;
}

/**
 * Acciones del formulario persistente
 */
export interface FormPersistenceActions<T> {
    /** Actualizar valores del formulario */
    setValues: (values: T) => void;
    /** Actualizar un campo específico */
    updateField: <K extends keyof T>(field: K, value: T[K]) => void;
    /** Guardar borrador manualmente */
    saveDraft: () => void;
    /** Restaurar borrador guardado */
    restoreDraft: () => void;
    /** Limpiar borrador guardado */
    clearDraft: () => void;
    /** Resetear a valores iniciales */
    reset: () => void;
    /** Verificar si hay cambios sin guardar */
    hasUnsavedChanges: () => boolean;
}

/**
 * Estructura del borrador en localStorage
 */
interface DraftData<T> {
    values: T;
    savedAt: string;
    version: number;
}

const DRAFT_VERSION = 1;

/**
 * Hook para persistir formularios en localStorage con auto-guardado
 *
 * Características:
 * - Auto-guardado cada 30 segundos (configurable)
 * - Restauración de borradores al volver
 * - Detección de cambios sin guardar
 * - Limpieza de borradores
 *
 * @example
 * ```tsx
 * const [state, actions] = useFormPersistence({
 *   storageKey: 'user-form-draft',
 *   initialValues: { name: '', email: '' },
 *   autoSave: true,
 *   autoSaveInterval: 30000,
 * });
 *
 * // Usar en formulario
 * <input
 *   value={state.values.name}
 *   onChange={(e) => actions.updateField('name', e.target.value)}
 * />
 *
 * // Al enviar, limpiar borrador
 * const handleSubmit = () => {
 *   submitForm(state.values);
 *   actions.clearDraft();
 * };
 * ```
 */
export function useFormPersistence<T extends Record<string, unknown>>(
    config: FormPersistenceConfig<T>
): [FormPersistenceState<T>, FormPersistenceActions<T>] {
    const {
        storageKey,
        initialValues,
        autoSaveInterval = 30000,
        autoSave = true,
        onRestore,
        onSave,
        onClear,
    } = config;

    const [values, setValuesState] = useState<T>(initialValues);
    const [lastSavedAt, setLastSavedAt] = useState<Date | null>(null);
    const [isSaving, setIsSaving] = useState(false);
    const autoSaveTimerRef = useRef<NodeJS.Timeout | null>(null);
    const isInitializedRef = useRef(false);

    // Cargar borrador al montar
    useEffect(() => {
        if (isInitializedRef.current) return;
        isInitializedRef.current = true;

        const loadDraft = () => {
            try {
                const stored = localStorage.getItem(storageKey);
                if (!stored) return;

                const draft: DraftData<T> = JSON.parse(stored);

                // Verificar versión del borrador
                if (draft.version !== DRAFT_VERSION) {
                    localStorage.removeItem(storageKey);
                    return;
                }

                setValuesState(draft.values);
                setLastSavedAt(new Date(draft.savedAt));

                onRestore?.(draft.values);
            } catch (error) {
                console.error('Error loading draft:', error);
                localStorage.removeItem(storageKey);
            }
        };

        loadDraft();
    }, [storageKey, onRestore]);

    // Guardar en localStorage
    const saveDraft = useCallback(() => {
        try {
            setIsSaving(true);

            const draft: DraftData<T> = {
                values,
                savedAt: new Date().toISOString(),
                version: DRAFT_VERSION,
            };

            localStorage.setItem(storageKey, JSON.stringify(draft));
            setLastSavedAt(new Date());

            onSave?.(values);
        } catch (error) {
            console.error('Error saving draft:', error);
        } finally {
            setIsSaving(false);
        }
    }, [storageKey, values, onSave]);

    // Auto-guardado
    useEffect(() => {
        if (!autoSave) return;

        // Limpiar timer anterior
        if (autoSaveTimerRef.current) {
            clearInterval(autoSaveTimerRef.current);
        }

        // Configurar nuevo timer
        autoSaveTimerRef.current = setInterval(() => {
            saveDraft();
        }, autoSaveInterval);

        return () => {
            if (autoSaveTimerRef.current) {
                clearInterval(autoSaveTimerRef.current);
            }
        };
    }, [autoSave, autoSaveInterval, saveDraft]);

    // Restaurar borrador
    const restoreDraft = useCallback(() => {
        try {
            const stored = localStorage.getItem(storageKey);
            if (!stored) return;

            const draft: DraftData<T> = JSON.parse(stored);
            setValuesState(draft.values);
            setLastSavedAt(new Date(draft.savedAt));

            onRestore?.(draft.values);
        } catch (error) {
            console.error('Error restoring draft:', error);
        }
    }, [storageKey, onRestore]);

    // Limpiar borrador
    const clearDraft = useCallback(() => {
        try {
            localStorage.removeItem(storageKey);
            setLastSavedAt(null);
            onClear?.();
        } catch (error) {
            console.error('Error clearing draft:', error);
        }
    }, [storageKey, onClear]);

    // Resetear a valores iniciales
    const reset = useCallback(() => {
        setValuesState(initialValues);
        clearDraft();
    }, [initialValues, clearDraft]);

    // Verificar si hay un borrador guardado
    const hasDraft = useCallback((): boolean => {
        try {
            const stored = localStorage.getItem(storageKey);
            return stored !== null;
        } catch {
            return false;
        }
    }, [storageKey]);

    // Verificar cambios sin guardar
    const hasUnsavedChanges = useCallback((): boolean => {
        return JSON.stringify(values) !== JSON.stringify(initialValues);
    }, [values, initialValues]);

    // Acciones
    const setValues = useCallback((newValues: T) => {
        setValuesState(newValues);
    }, []);

    const updateField = useCallback(<K extends keyof T>(field: K, value: T[K]) => {
        setValuesState(prev => ({ ...prev, [field]: value }));
    }, []);

    const state: FormPersistenceState<T> = {
        values,
        hasDraft: hasDraft(),
        lastSavedAt,
        isSaving,
    };

    const actions: FormPersistenceActions<T> = {
        setValues,
        updateField,
        saveDraft,
        restoreDraft,
        clearDraft,
        reset,
        hasUnsavedChanges,
    };

    return [state, actions];
}

/**
 * Helper para mostrar notificación de borrador guardado
 */
export function getDraftMessage(lastSavedAt: Date | null): string {
    if (!lastSavedAt) return '';

    const now = new Date();
    const diffMs = now.getTime() - lastSavedAt.getTime();
    const diffMinutes = Math.floor(diffMs / 60000);

    if (diffMinutes < 1) return 'Borrador guardado hace un momento';
    if (diffMinutes === 1) return 'Borrador guardado hace 1 minuto';
    if (diffMinutes < 60) return `Borrador guardado hace ${diffMinutes} minutos`;

    const diffHours = Math.floor(diffMinutes / 60);
    if (diffHours === 1) return 'Borrador guardado hace 1 hora';
    if (diffHours < 24) return `Borrador guardado hace ${diffHours} horas`;

    const diffDays = Math.floor(diffHours / 24);
    if (diffDays === 1) return 'Borrador guardado hace 1 día';
    return `Borrador guardado hace ${diffDays} días`;
}
