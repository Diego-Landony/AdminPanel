/**
 * Hook para gestionar formularios de categorías
 * Unifica la lógica de create y edit
 */

import { router } from '@inertiajs/react';
import { useCallback, useEffect, useRef, useState } from 'react';

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
    imagePreview: string | null;
    errors: FormErrors;
    processing: boolean;
    variantsChanged: boolean;

    // Handlers
    handleInputChange: (field: keyof CategoryFormData, value: string | boolean | string[]) => void;
    handleImageChange: (file: File | null, preview: string | null) => void;
    handleSubmit: (e: React.FormEvent) => void;
    resetForm: () => void;
}

const getInitialFormData = (category?: Category): CategoryFormData => {
    if (!category) {
        return {
            name: '',
            description: '',
            is_active: true,
            is_combo_category: false,
            uses_variants: false,
            variant_definitions: [],
        };
    }

    return {
        name: category.name,
        description: category.description || '',
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
    const [imageFile, setImageFile] = useState<File | null>(null);
    const [imagePreview, setImagePreview] = useState<string | null>(category?.image_url || null);
    const [removeImage, setRemoveImage] = useState(false);
    const imageFileRef = useRef<File | null>(null);
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

    const handleImageChange = useCallback(
        (file: File | null, previewUrl: string | null) => {
            setImageFile(file);
            imageFileRef.current = file;
            setImagePreview(previewUrl);
            setRemoveImage(file === null && previewUrl === null);

            if (errors.image) {
                setErrors((prev) => {
                    const newErrors = { ...prev };
                    delete newErrors.image;
                    return newErrors;
                });
            }
        },
        [errors.image]
    );

    const resetForm = useCallback(() => {
        setFormData(getInitialFormData());
        setImageFile(null);
        imageFileRef.current = null;
        setImagePreview(null);
        setRemoveImage(false);
        setErrors({});
    }, []);

    const handleSubmit = useCallback(
        (e: React.FormEvent) => {
            e.preventDefault();
            setProcessing(true);

            const currentImageFile = imageFileRef.current || imageFile;

            // Build FormData for file upload
            const submitData = new FormData();
            submitData.append('name', formData.name);
            submitData.append('description', formData.description || '');
            submitData.append('is_active', formData.is_active ? '1' : '0');
            submitData.append('is_combo_category', formData.is_combo_category ? '1' : '0');
            submitData.append('uses_variants', formData.uses_variants ? '1' : '0');

            if (formData.variant_definitions && formData.variant_definitions.length > 0) {
                formData.variant_definitions.forEach((variant, index) => {
                    submitData.append(`variant_definitions[${index}]`, variant);
                });
            }

            if (currentImageFile) {
                submitData.append('image', currentImageFile);
            }

            if (removeImage) {
                submitData.append('remove_image', '1');
            }

            if (isEdit) {
                submitData.append('_method', 'PUT');
                router.post(
                    `/menu/categories/${category!.id}`,
                    submitData,
                    {
                        forceFormData: true,
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
                router.post(route('menu.categories.store'), submitData, {
                    forceFormData: true,
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
        [formData, imageFile, removeImage, isEdit, category, resetForm, onSuccess]
    );

    return {
        // Estado
        formData,
        imagePreview,
        errors,
        processing,
        variantsChanged,

        // Handlers
        handleInputChange,
        handleImageChange,
        handleSubmit,
        resetForm,
    };
}
