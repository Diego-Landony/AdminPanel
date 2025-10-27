import { useCallback, useEffect, useRef, useState } from 'react';

export interface FormDirtyConfig {
    /** Callback when form dirty state changes */
    onDirtyChange?: (isDirty: boolean) => void;
    /** Custom comparison function for deep object comparison */
    compareFunction?: (original: unknown, current: unknown) => boolean;
    /** Whether to ignore specific fields from dirty check */
    ignoreFields?: string[];
    /** Whether to show confirmation dialog when leaving with unsaved changes */
    preventNavigation?: boolean;
}

export interface FormDirtyState {
    /** Whether the form has unsaved changes */
    isDirty: boolean;
    /** Which specific fields have changed */
    changedFields: string[];
    /** Mark form as clean (reset dirty state) */
    markAsClean: () => void;
    /** Mark form as dirty manually */
    markAsDirty: () => void;
    /** Reset to original values */
    resetToOriginal: () => void;
    /** Check if specific field is dirty */
    isFieldDirty: (fieldName: string) => boolean;
}

/**
 * Deep comparison utility for objects
 */
function deepEqual(obj1: unknown, obj2: unknown): boolean {
    if (obj1 === obj2) return true;

    if (obj1 === null || obj2 === null || obj1 === undefined || obj2 === undefined) {
        return obj1 === obj2;
    }

    if (typeof obj1 !== typeof obj2) return false;

    if (typeof obj1 !== 'object') return obj1 === obj2;

    const keys1 = Object.keys(obj1 as Record<string, unknown>);
    const keys2 = Object.keys(obj2 as Record<string, unknown>);

    if (keys1.length !== keys2.length) return false;

    for (const key of keys1) {
        if (!keys2.includes(key)) return false;
        if (!deepEqual((obj1 as Record<string, unknown>)[key], (obj2 as Record<string, unknown>)[key])) {
            return false;
        }
    }

    return true;
}

/**
 * Custom hook for tracking form dirty state with Inertia.js forms
 * Provides detection of unsaved changes and prevents accidental navigation
 */
export function useFormDirty<T extends Record<string, unknown>>(currentData: T, originalData: T, config: FormDirtyConfig = {}): FormDirtyState {
    const { onDirtyChange, compareFunction = deepEqual, ignoreFields = [], preventNavigation = true } = config;

    const [isDirty, setIsDirty] = useState(false);
    const [changedFields, setChangedFields] = useState<string[]>([]);
    const originalDataRef = useRef<T>(originalData);
    const prevIsDirtyRef = useRef(isDirty);

    // Update original data reference when it changes
    useEffect(() => {
        originalDataRef.current = originalData;
    }, [originalData]);

    // Calculate dirty state and changed fields
    useEffect(() => {
        const filteredCurrentData = { ...currentData };
        const filteredOriginalData = { ...originalDataRef.current };

        // Remove ignored fields from comparison
        ignoreFields.forEach((field) => {
            delete filteredCurrentData[field];
            delete filteredOriginalData[field];
        });

        const newIsDirty = !compareFunction(filteredCurrentData, filteredOriginalData);

        // Calculate which fields have changed
        const newChangedFields: string[] = [];
        Object.keys(filteredCurrentData).forEach((key) => {
            if (!compareFunction(filteredCurrentData[key], filteredOriginalData[key])) {
                newChangedFields.push(key);
            }
        });

        setIsDirty(newIsDirty);
        setChangedFields(newChangedFields);

        // Call onDirtyChange callback if state changed
        if (newIsDirty !== prevIsDirtyRef.current) {
            onDirtyChange?.(newIsDirty);
            prevIsDirtyRef.current = newIsDirty;
        }
    }, [currentData, compareFunction, ignoreFields, onDirtyChange]);

    // Prevent navigation when form is dirty
    useEffect(() => {
        if (!preventNavigation || !isDirty) return;

        const handleBeforeUnload = (e: BeforeUnloadEvent) => {
            e.preventDefault();
            e.returnValue = '¿Estás seguro de que quieres salir? Los cambios no guardados se perderán.';
            return e.returnValue;
        };

        window.addEventListener('beforeunload', handleBeforeUnload);

        // Prevent navigation using Inertia router
        const preventInertiaNavigation = (e: Event) => {
            if (isDirty) {
                const confirmLeave = window.confirm('¿Estás seguro de que quieres salir? Los cambios no guardados se perderán.');
                if (!confirmLeave) {
                    e.preventDefault();
                    return false;
                }
            }
            return true;
        };

        // Listen for Inertia before navigation events
        document.addEventListener('inertia:before', preventInertiaNavigation);

        return () => {
            window.removeEventListener('beforeunload', handleBeforeUnload);
            document.removeEventListener('inertia:before', preventInertiaNavigation);
        };
    }, [isDirty, preventNavigation]);

    // Action functions
    const markAsClean = useCallback(() => {
        originalDataRef.current = { ...currentData };
        setIsDirty(false);
        setChangedFields([]);
    }, [currentData]);

    const markAsDirty = useCallback(() => {
        setIsDirty(true);
    }, []);

    const resetToOriginal = useCallback(() => {
        // This function would need to be implemented by the consumer
        // since we can't directly modify the form data from here
        setIsDirty(false);
        setChangedFields([]);
    }, []);

    const isFieldDirty = useCallback(
        (fieldName: string) => {
            return changedFields.includes(fieldName);
        },
        [changedFields],
    );

    return {
        isDirty,
        changedFields,
        markAsClean,
        markAsDirty,
        resetToOriginal,
        isFieldDirty,
    };
}

/**
 * Simplified hook specifically for Inertia.js useForm
 * Automatically integrates with Inertia form data structure
 */
export function useInertiaFormDirty<T extends Record<string, unknown>>(
    formData: T,
    originalData: T,
    config: Omit<FormDirtyConfig, 'compareFunction'> = {},
) {
    return useFormDirty(formData, originalData, {
        ...config,
        // Use a simple comparison for most form cases
        compareFunction: (a, b) => {
            // Handle common form field types
            if (typeof a === 'string' && typeof b === 'string') {
                return a.trim() === b.trim();
            }
            return deepEqual(a, b);
        },
    });
}

/**
 * Hook for tracking form submission state and providing user feedback
 */
export function useFormSubmissionState() {
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [lastSubmitTime, setLastSubmitTime] = useState<Date | null>(null);
    const [submitCount, setSubmitCount] = useState(0);

    const startSubmission = useCallback(() => {
        setIsSubmitting(true);
        setSubmitCount((prev) => prev + 1);
    }, []);

    const endSubmission = useCallback((success: boolean = true) => {
        setIsSubmitting(false);
        setLastSubmitTime(new Date());

        if (success) {
            // Reset submit count on successful submission
            setSubmitCount(0);
        }
    }, []);

    const canSubmit = useCallback(
        (isDirty: boolean) => {
            return isDirty && !isSubmitting;
        },
        [isSubmitting],
    );

    return {
        isSubmitting,
        lastSubmitTime,
        submitCount,
        startSubmission,
        endSubmission,
        canSubmit,
    };
}
