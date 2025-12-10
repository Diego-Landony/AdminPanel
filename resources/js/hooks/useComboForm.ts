/**
 * Hook para gestionar formularios de combos
 * Unifica la lógica de create y edit
 */

import { closestCenter, DndContext, DragEndEvent, KeyboardSensor, PointerSensor, useSensor, useSensors } from '@dnd-kit/core';
import { arrayMove, SortableContext, sortableKeyboardCoordinates, verticalListSortingStrategy } from '@dnd-kit/sortable';
import { router } from '@inertiajs/react';
import { useCallback, useMemo, useState } from 'react';

import { showNotification } from '@/hooks/useNotifications';
import { NOTIFICATIONS } from '@/constants/ui-constants';
import { generateUniqueItemId, prepareComboDataForSubmit, validateMinimumComboStructure } from '@/utils/comboHelpers';
import type {
    Category,
    Product,
    Combo,
    FormMode,
    FormErrors,
    LocalComboItem,
    LocalChoiceOption,
} from '@/types/menu';

export interface ComboFormData {
    category_id: string;
    name: string;
    description: string;
    is_active: boolean;
    precio_pickup_capital: string;
    precio_domicilio_capital: string;
    precio_pickup_interior: string;
    precio_domicilio_interior: string;
}

export interface InactiveProductInfo {
    type: 'fixed' | 'choice';
    productName: string;
    groupLabel?: string;
}

export interface UseComboFormOptions {
    mode: FormMode;
    combo?: Combo;
    products: Product[];
    categories: Category[];
    onSuccess?: () => void;
}

export interface UseComboFormReturn {
    // Estado
    formData: ComboFormData;
    imageFile: File | null;
    imagePreview: string | null;
    removeImage: boolean;
    errors: FormErrors;
    processing: boolean;
    localItems: LocalComboItem[];
    inactiveItems: InactiveProductInfo[];
    hasInactiveProducts: boolean;

    // DnD
    sensors: ReturnType<typeof useSensors>;
    DndContext: typeof DndContext;
    SortableContext: typeof SortableContext;
    dndProps: {
        sensors: ReturnType<typeof useSensors>;
        collisionDetection: typeof closestCenter;
        onDragEnd: (event: DragEndEvent) => void;
    };
    sortableContextProps: {
        items: string[];
        strategy: typeof verticalListSortingStrategy;
    };

    // Handlers
    handleInputChange: (field: keyof ComboFormData, value: string | boolean) => void;
    handleImageChange: (file: File | null, previewUrl: string | null) => void;
    addItem: () => void;
    removeItem: (index: number) => void;
    updateItem: (index: number, field: string, value: unknown) => void;
    batchUpdateItem: (index: number, updates: Partial<LocalComboItem>) => void;
    handleDragEnd: (event: DragEndEvent) => void;
    handleSubmit: (e: React.FormEvent) => void;
    resetForm: () => void;
    canDeleteItem: (itemsLength: number) => boolean;
}

const getInitialFormData = (combo?: Combo): ComboFormData => {
    if (!combo) {
        return {
            category_id: '',
            name: '',
            description: '',
            is_active: true,
            precio_pickup_capital: '',
            precio_domicilio_capital: '',
            precio_pickup_interior: '',
            precio_domicilio_interior: '',
        };
    }

    return {
        category_id: String(combo.category?.id || ''),
        name: combo.name,
        description: combo.description || '',
        is_active: combo.is_active,
        precio_pickup_capital: String(combo.precio_pickup_capital),
        precio_domicilio_capital: String(combo.precio_domicilio_capital),
        precio_pickup_interior: String(combo.precio_pickup_interior),
        precio_domicilio_interior: String(combo.precio_domicilio_interior),
    };
};

const getInitialItems = (combo?: Combo): LocalComboItem[] => {
    if (!combo || !combo.items) return [];

    return combo.items.map((item) => ({
        id: `item-${item.id}`,
        is_choice_group: item.is_choice_group,
        choice_label: item.choice_label || '',
        product_id: item.product_id || null,
        variant_id: item.variant_id || null,
        quantity: item.quantity,
        options:
            item.options?.map((opt) => ({
                id: `option-${opt.id}`,
                product_id: opt.product_id,
                variant_id: opt.variant_id || null,
            })) || [],
    }));
};

export function useComboForm({
    mode,
    combo,
    products,
    categories,
    onSuccess,
}: UseComboFormOptions): UseComboFormReturn {
    const isEdit = mode === 'edit';

    // Estado principal
    const [formData, setFormData] = useState<ComboFormData>(() => getInitialFormData(combo));
    const [imageFile, setImageFile] = useState<File | null>(null);
    const [imagePreview, setImagePreview] = useState<string | null>(combo?.image || null);
    const [removeImage, setRemoveImage] = useState(false);
    const [errors, setErrors] = useState<FormErrors>({});
    const [processing, setProcessing] = useState(false);
    const [localItems, setLocalItems] = useState<LocalComboItem[]>(() => getInitialItems(combo));

    // DnD Sensors
    const sensors = useSensors(
        useSensor(PointerSensor),
        useSensor(KeyboardSensor, {
            coordinateGetter: sortableKeyboardCoordinates,
        })
    );

    // Detectar productos inactivos
    const inactiveItems = useMemo<InactiveProductInfo[]>(() => {
        const inactive: InactiveProductInfo[] = [];

        localItems.forEach((item) => {
            if (item.is_choice_group && item.options) {
                item.options.forEach((option) => {
                    const product = products.find((p) => p.id === option.product_id);
                    if (product && !product.is_active) {
                        inactive.push({
                            type: 'choice',
                            productName: product.name,
                            groupLabel: item.choice_label || 'Grupo sin nombre',
                        });
                    }
                });
            } else if (item.product_id) {
                const product = products.find((p) => p.id === item.product_id);
                if (product && !product.is_active) {
                    inactive.push({
                        type: 'fixed',
                        productName: product.name,
                    });
                }
            }
        });

        return inactive;
    }, [localItems, products]);

    const hasInactiveProducts = inactiveItems.length > 0;

    // Handlers
    const handleInputChange = useCallback(
        (field: keyof ComboFormData, value: string | boolean) => {
            setFormData((prev) => ({ ...prev, [field]: value }));

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

    const handleImageChange = useCallback(
        (file: File | null, previewUrl: string | null) => {
            setImageFile(file);
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

    const addItem = useCallback(() => {
        const newItem: LocalComboItem = {
            id: generateUniqueItemId(),
            is_choice_group: false,
            choice_label: '',
            product_id: null,
            variant_id: null,
            quantity: 1,
            options: [],
        };
        setLocalItems((prev) => [...prev, newItem]);
    }, []);

    const removeItem = useCallback((index: number) => {
        setLocalItems((prev) => prev.filter((_, i) => i !== index));
    }, []);

    const updateItem = useCallback((index: number, field: string, value: unknown) => {
        setLocalItems((prev) => {
            const updated = [...prev];
            updated[index] = { ...updated[index], [field]: value };
            return updated;
        });
    }, []);

    const batchUpdateItem = useCallback((index: number, updates: Partial<LocalComboItem>) => {
        setLocalItems((prev) => {
            const updated = [...prev];
            updated[index] = { ...updated[index], ...updates };
            return updated;
        });
    }, []);

    const handleDragEnd = useCallback((event: DragEndEvent) => {
        const { active, over } = event;

        if (over && active.id !== over.id) {
            setLocalItems((items) => {
                const oldIndex = items.findIndex((item) => item.id === active.id);
                const newIndex = items.findIndex((item) => item.id === over.id);
                return arrayMove(items, oldIndex, newIndex);
            });
        }
    }, []);

    const canDeleteItem = useCallback(
        (itemsLength: number) => {
            return isEdit ? itemsLength > 2 : true;
        },
        [isEdit]
    );

    const resetForm = useCallback(() => {
        setFormData(getInitialFormData());
        setImageFile(null);
        setImagePreview(null);
        setRemoveImage(false);
        setLocalItems([]);
        setErrors({});
    }, []);

    const handleSubmit = useCallback(
        (e: React.FormEvent) => {
            e.preventDefault();

            const validation = validateMinimumComboStructure(localItems);
            if (!validation.valid) {
                if (isEdit) {
                    setErrors({ items: validation.errors[0] });
                } else {
                    showNotification.error(validation.errors[0]);
                }
                return;
            }

            setProcessing(true);

            const preparedItems = prepareComboDataForSubmit(localItems);

            const data = new FormData();

            if (isEdit) {
                data.append('_method', 'PUT');
            }

            data.append('category_id', formData.category_id);
            data.append('name', formData.name);
            data.append('description', formData.description);
            data.append('is_active', formData.is_active ? '1' : '0');
            data.append('precio_pickup_capital', formData.precio_pickup_capital);
            data.append('precio_domicilio_capital', formData.precio_domicilio_capital);
            data.append('precio_pickup_interior', formData.precio_pickup_interior);
            data.append('precio_domicilio_interior', formData.precio_domicilio_interior);

            // Imagen
            if (imageFile) {
                data.append('image', imageFile);
            } else if (removeImage && isEdit) {
                data.append('remove_image', '1');
            }

            // Items
            preparedItems.forEach((item, index) => {
                data.append(`items[${index}][is_choice_group]`, item.is_choice_group ? '1' : '0');
                data.append(`items[${index}][quantity]`, String(item.quantity));
                data.append(`items[${index}][sort_order]`, String(item.sort_order));

                if (item.choice_label) {
                    data.append(`items[${index}][choice_label]`, item.choice_label);
                }
                if (item.product_id) {
                    data.append(`items[${index}][product_id]`, String(item.product_id));
                }
                if (item.variant_id) {
                    data.append(`items[${index}][variant_id]`, String(item.variant_id));
                }

                if (item.options && item.options.length > 0) {
                    item.options.forEach((option, optIndex) => {
                        data.append(`items[${index}][options][${optIndex}][product_id]`, String(option.product_id));
                        data.append(`items[${index}][options][${optIndex}][sort_order]`, String(option.sort_order));
                        if (option.variant_id) {
                            data.append(`items[${index}][options][${optIndex}][variant_id]`, String(option.variant_id));
                        }
                    });
                }
            });

            const url = isEdit ? `/menu/combos/${combo!.id}` : route('menu.combos.store');

            router.post(url, data, {
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
                        showNotification.error(NOTIFICATIONS.error.dataLoading);
                    }
                },
            });
        },
        [formData, imageFile, removeImage, localItems, isEdit, combo, resetForm, onSuccess]
    );

    return {
        // Estado
        formData,
        imageFile,
        imagePreview,
        removeImage,
        errors,
        processing,
        localItems,
        inactiveItems,
        hasInactiveProducts,

        // DnD
        sensors,
        DndContext,
        SortableContext,
        dndProps: {
            sensors,
            collisionDetection: closestCenter,
            onDragEnd: handleDragEnd,
        },
        sortableContextProps: {
            items: localItems.map((item) => item.id),
            strategy: verticalListSortingStrategy,
        },

        // Handlers
        handleInputChange,
        handleImageChange,
        addItem,
        removeItem,
        updateItem,
        batchUpdateItem,
        handleDragEnd,
        handleSubmit,
        resetForm,
        canDeleteItem,
    };
}
