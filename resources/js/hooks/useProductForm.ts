/**
 * Hook para gestionar formularios de productos
 * Unifica la lógica de create y edit
 */

import { router } from '@inertiajs/react';
import { useCallback, useEffect, useRef, useState } from 'react';

import { showNotification } from '@/hooks/useNotifications';
import { NOTIFICATIONS } from '@/constants/ui-constants';
import type {
    Category,
    Section,
    Product,
    ProductVariant,
    VariantFormData,
    FormMode,
    FormErrors,
} from '@/types/menu';

export interface ProductFormData {
    category_id: string;
    name: string;
    description: string;
    is_active: boolean;
    has_variants: boolean;
    precio_pickup_capital: string;
    precio_domicilio_capital: string;
    precio_pickup_interior: string;
    precio_domicilio_interior: string;
    variants: VariantFormData[];
}

export interface UseProductFormOptions {
    mode: FormMode;
    product?: Product;
    categories: Category[];
    sections: Section[];
    onSuccess?: () => void;
}

export interface UseProductFormReturn {
    // Estado
    formData: ProductFormData;
    imageFile: File | null;
    imagePreview: string | null;
    removeImage: boolean;
    errors: FormErrors;
    processing: boolean;
    selectedCategory: Category | null;
    selectedSections: number[];
    existingVariants: ProductVariant[];

    // Handlers
    handleInputChange: (field: keyof ProductFormData, value: string | boolean | VariantFormData[]) => void;
    handleCategoryChange: (value: number | null) => void;
    handleVariantsChange: (variants: VariantFormData[]) => void;
    handleImageChange: (file: File | null, previewUrl: string | null) => void;
    toggleSection: (sectionId: number) => void;
    handleSubmit: (e: React.FormEvent) => void;
    resetForm: () => void;
}

const getInitialFormData = (product?: Product): ProductFormData => {
    if (!product) {
        return {
            category_id: '',
            name: '',
            description: '',
            is_active: true,
            has_variants: false,
            precio_pickup_capital: '',
            precio_domicilio_capital: '',
            precio_pickup_interior: '',
            precio_domicilio_interior: '',
            variants: [],
        };
    }

    const initialVariants: VariantFormData[] = (product.variants || []).map((v) => ({
        id: v.id,
        name: v.name,
        is_active: true,
        precio_pickup_capital: String(v.precio_pickup_capital || ''),
        precio_domicilio_capital: String(v.precio_domicilio_capital || ''),
        precio_pickup_interior: String(v.precio_pickup_interior || ''),
        precio_domicilio_interior: String(v.precio_domicilio_interior || ''),
    }));

    return {
        category_id: product.category_id ? String(product.category_id) : '',
        name: product.name,
        description: product.description || '',
        is_active: product.is_active,
        has_variants: product.has_variants,
        precio_pickup_capital: product.precio_pickup_capital?.toString() || '',
        precio_domicilio_capital: product.precio_domicilio_capital?.toString() || '',
        precio_pickup_interior: product.precio_pickup_interior?.toString() || '',
        precio_domicilio_interior: product.precio_domicilio_interior?.toString() || '',
        variants: initialVariants,
    };
};

export function useProductForm({
    mode,
    product,
    categories,
    sections,
    onSuccess,
}: UseProductFormOptions): UseProductFormReturn {
    const isEdit = mode === 'edit';

    // Estado principal
    const [formData, setFormData] = useState<ProductFormData>(() => getInitialFormData(product));
    const [imageFile, setImageFile] = useState<File | null>(null);
    const [imagePreview, setImagePreview] = useState<string | null>(product?.image || null);
    const [removeImage, setRemoveImage] = useState(false);

    // Ref para mantener referencia estable al archivo de imagen
    const imageFileRef = useRef<File | null>(null);
    const [errors, setErrors] = useState<FormErrors>({});
    const [processing, setProcessing] = useState(false);
    const [selectedSections, setSelectedSections] = useState<number[]>(
        product?.sections?.map((s) => s.id) || []
    );
    const [selectedCategory, setSelectedCategory] = useState<Category | null>(() => {
        if (product?.category_id) {
            return categories.find((c) => c.id === product.category_id) || null;
        }
        return null;
    });

    // Sincronizar categoría cuando cambia category_id
    useEffect(() => {
        if (formData.category_id) {
            const category = categories.find((c) => c.id === Number(formData.category_id));
            setSelectedCategory(category || null);
            setFormData((prev) => ({ ...prev, has_variants: category?.uses_variants || false }));
        } else {
            setSelectedCategory(null);
            setFormData((prev) => ({ ...prev, has_variants: false }));
        }
    }, [formData.category_id, categories]);

    // Handlers
    const handleInputChange = useCallback(
        (field: keyof ProductFormData, value: string | boolean | VariantFormData[]) => {
            setFormData((prev) => ({ ...prev, [field]: value }));

            // Limpiar error del campo
            if (errors[field]) {
                setErrors((prev) => {
                    const newErrors = { ...prev };
                    delete newErrors[field];
                    return newErrors;
                });
            }
        },
        [errors]
    );

    const handleCategoryChange = useCallback(
        (value: number | null) => {
            handleInputChange('category_id', value ? String(value) : '');
        },
        [handleInputChange]
    );

    const handleVariantsChange = useCallback(
        (variants: VariantFormData[]) => {
            setFormData((prev) => ({ ...prev, variants }));

            if (errors.variants) {
                setErrors((prev) => {
                    const newErrors = { ...prev };
                    delete newErrors.variants;
                    return newErrors;
                });
            }
        },
        [errors]
    );

    const handleImageChange = useCallback(
        (file: File | null, previewUrl: string | null) => {
            console.log('handleImageChange called:', { file, previewUrl });
            setImageFile(file);
            imageFileRef.current = file; // Mantener referencia estable
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
        [errors]
    );

    const toggleSection = useCallback((sectionId: number) => {
        setSelectedSections((prev) =>
            prev.includes(sectionId) ? prev.filter((id) => id !== sectionId) : [...prev, sectionId]
        );
    }, []);

    const resetForm = useCallback(() => {
        setFormData(getInitialFormData());
        setImageFile(null);
        imageFileRef.current = null;
        setImagePreview(null);
        setRemoveImage(false);
        setSelectedSections([]);
        setErrors({});
    }, []);

    const handleSubmit = useCallback(
        (e: React.FormEvent) => {
            e.preventDefault();
            setProcessing(true);

            // Usar ref como fuente primaria para evitar problemas de closure
            const currentImageFile = imageFileRef.current || imageFile;

            // Debug: verificar estado de imagen
            console.log('=== DEBUG IMAGE UPLOAD ===');
            console.log('currentImageFile:', currentImageFile);

            // Variantes activas
            const activeVariants = formData.has_variants
                ? formData.variants.filter((v) => v.is_active)
                : [];

            // Construir FormData manualmente para asegurar que el archivo se envíe correctamente
            const data = new FormData();

            // Agregar _method para spoofing en edit
            if (isEdit) {
                data.append('_method', 'PUT');
            }

            // Campos básicos
            data.append('category_id', formData.category_id);
            data.append('name', formData.name);
            data.append('description', formData.description || '');
            data.append('is_active', formData.is_active ? '1' : '0');
            data.append('has_variants', formData.has_variants ? '1' : '0');
            data.append('precio_pickup_capital', formData.precio_pickup_capital || '');
            data.append('precio_domicilio_capital', formData.precio_domicilio_capital || '');
            data.append('precio_pickup_interior', formData.precio_pickup_interior || '');
            data.append('precio_domicilio_interior', formData.precio_domicilio_interior || '');

            // Imagen - IMPORTANTE: agregar el archivo directamente al FormData
            if (currentImageFile) {
                data.append('image', currentImageFile, currentImageFile.name);
                console.log('Image appended to FormData:', currentImageFile.name, currentImageFile.size);
            } else if (removeImage && isEdit) {
                data.append('remove_image', '1');
            }

            // Secciones
            selectedSections.forEach((sectionId) => {
                data.append('sections[]', String(sectionId));
            });

            // Variantes
            activeVariants.forEach((variant, index) => {
                if (variant.id) {
                    data.append(`variants[${index}][id]`, String(variant.id));
                }
                data.append(`variants[${index}][name]`, variant.name);
                data.append(`variants[${index}][precio_pickup_capital]`, variant.precio_pickup_capital);
                data.append(`variants[${index}][precio_domicilio_capital]`, variant.precio_domicilio_capital);
                data.append(`variants[${index}][precio_pickup_interior]`, variant.precio_pickup_interior);
                data.append(`variants[${index}][precio_domicilio_interior]`, variant.precio_domicilio_interior);
            });

            const url = isEdit ? `/menu/products/${product!.id}` : route('menu.products.store');

            // Debug: mostrar contenido del FormData
            console.log('FormData entries:');
            for (const [key, value] of data.entries()) {
                console.log(`  ${key}:`, value instanceof File ? `File(${value.name}, ${value.size} bytes)` : value);
            }

            // Convertir FormData a objeto plano para Inertia
            // Inertia maneja mejor los objetos planos y los convierte a FormData internamente
            const plainData: Record<string, unknown> = {};
            for (const [key, value] of data.entries()) {
                // Manejar arrays (sections[] y variants[x][y])
                if (key.endsWith('[]')) {
                    const baseKey = key.slice(0, -2);
                    if (!plainData[baseKey]) {
                        plainData[baseKey] = [];
                    }
                    (plainData[baseKey] as unknown[]).push(value);
                } else if (key.includes('[')) {
                    // Manejar objetos anidados como variants[0][name]
                    const matches = key.match(/^(\w+)\[(\d+)\]\[(\w+)\]$/);
                    if (matches) {
                        const [, arrayName, index, propName] = matches;
                        if (!plainData[arrayName]) {
                            plainData[arrayName] = [];
                        }
                        const arr = plainData[arrayName] as Record<string, unknown>[];
                        const idx = parseInt(index, 10);
                        if (!arr[idx]) {
                            arr[idx] = {};
                        }
                        arr[idx][propName] = value;
                    } else {
                        plainData[key] = value;
                    }
                } else {
                    plainData[key] = value;
                }
            }

            console.log('Plain data for Inertia:', plainData);

            router.post(url, plainData, {
                forceFormData: true,
                onSuccess: () => {
                    if (!isEdit) {
                        resetForm();
                    }
                    setProcessing(false);
                    onSuccess?.();
                },
                onError: (newErrors: Record<string, string>) => {
                    setErrors(newErrors);
                    setProcessing(false);
                    if (Object.keys(newErrors).length === 0) {
                        showNotification.error(NOTIFICATIONS.error.server);
                    }
                },
            });
        },
        [formData, imageFile, removeImage, selectedSections, isEdit, product, resetForm, onSuccess]
    );

    return {
        // Estado
        formData,
        imageFile,
        imagePreview,
        removeImage,
        errors,
        processing,
        selectedCategory,
        selectedSections,
        existingVariants: product?.variants || [],

        // Handlers
        handleInputChange,
        handleCategoryChange,
        handleVariantsChange,
        handleImageChange,
        toggleSection,
        handleSubmit,
        resetForm,
    };
}
