/**
 * Hook para gestionar formularios de categorías
 * Unifica la lógica de create y edit
 */

import { router } from '@inertiajs/react';
import { useCallback, useEffect, useState } from 'react';

import { showNotification } from '@/hooks/useNotifications';
import { NOTIFICATIONS } from '@/constants/ui-constants';
import type { Category, CategoryFormData, FormMode, FormErrors } from '@/types/menu';

export interface UseCategoryFormOptions {
    mode: FormMode;
    category?: Category;
    onSuccess?: () => void;
}

export interface UseCategoryFormReturn {
    // Estado
    formData: CategoryFormData;
    errors: FormErrors;
    processing: boolean;
    variantsChanged: boolean;

    // Handlers
    handleInputChange: (field: keyof CategoryFormData, value: string | boolean | string[]) => void;
    handleSubmit: (e: React.FormEvent) => void;
    resetForm: () => void;
}

const getInitialFormData = (category?: Category): CategoryFormData => {
    if (!category) {
        return {
            name: '',
            is_active: true,
            is_combo_category: false,
            uses_variants: false,
            variant_definitions: [],
        };
    }

    return {
        name: category.name,
        is_active: category.is_active,
        is_combo_category: category.is_combo_category,
        uses_variants: category.uses_variants,
        variant_definitions: category.variant_definitions || [],
    };
};

export function useCategoryForm({
    mode,
    category,
    onSuccess,
}: UseCategoryFormOptions): UseCategoryFormReturn {
    const isEdit = mode === 'edit';

    // Estado principal
    const [formData, setFormData] = useState<CategoryFormData>(() => getInitialFormData(category));
    const [errors, setErrors] = useState<FormErrors>({});
    const [processing, setProcessing] = useState(false);

    // Para detectar cambios en variantes (solo en edit)
    const initialVariants = category?.variant_definitions || [];
    const variantsChanged = isEdit && JSON.stringify(formData.variant_definitions) !== JSON.stringify(initialVariants);

    // Limpiar variant_definitions si uses_variants se desactiva
    useEffect(() => {
        if (!formData.uses_variants && formData.variant_definitions.length > 0) {
            setFormData((prev) => ({
                ...prev,
                variant_definitions: [],
            }));
        }
    }, [formData.uses_variants, formData.variant_definitions.length]);

    // Validación en tiempo real
    const validateField = useCallback((field: string, value: string | boolean | string[]): string | null => {
        switch (field) {
            case 'name':
                if (typeof value === 'string') {
                    if (!value || value.trim() === '') {
                        return 'El nombre es requerido';
                    }
                    if (value.length < 2) {
                        return 'El nombre debe tener al menos 2 caracteres';
                    }
                    if (value.length > 255) {
                        return 'El nombre no puede exceder 255 caracteres';
                    }
                }
                return null;
            default:
                return null;
        }
    }, []);

    // Handlers
    const handleInputChange = useCallback(
        (field: keyof CategoryFormData, value: string | boolean | string[]) => {
            setFormData((prev) => ({ ...prev, [field]: value }));

            // Validar en tiempo real
            const error = validateField(field, value);
            setErrors((prev) => {
                const newErrors = { ...prev };
                if (error) {
                    newErrors[field] = error;
                } else {
                    delete newErrors[field];
                }
                return newErrors;
            });
        },
        [validateField]
    );

    const resetForm = useCallback(() => {
        setFormData(getInitialFormData());
        setErrors({});
    }, []);

    const handleSubmit = useCallback(
        (e: React.FormEvent) => {
            e.preventDefault();
            setProcessing(true);

            if (isEdit) {
                router.put(
                    `/menu/categories/${category!.id}`,
                    formData as unknown as Record<string, string | number | boolean>,
                    {
                        onSuccess: () => {
                            setProcessing(false);
                            onSuccess?.();
                        },
                        onError: (newErrors) => {
                            setErrors(newErrors as Record<string, string>);
                            setProcessing(false);
                        },
                    }
                );
            } else {
                router.post(route('menu.categories.store'), formData as unknown as Record<string, string | number | boolean>, {
                    onSuccess: () => {
                        resetForm();
                        setProcessing(false);
                        onSuccess?.();
                    },
                    onError: (newErrors) => {
                        setErrors(newErrors as Record<string, string>);
                        setProcessing(false);
                        if (Object.keys(newErrors).length === 0) {
                            showNotification.error(NOTIFICATIONS.error.server);
                        }
                    },
                });
            }
        },
        [formData, isEdit, category, resetForm, onSuccess]
    );

    return {
        // Estado
        formData,
        errors,
        processing,
        variantsChanged,

        // Handlers
        handleInputChange,
        handleSubmit,
        resetForm,
    };
}
