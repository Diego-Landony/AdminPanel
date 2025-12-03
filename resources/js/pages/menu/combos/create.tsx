import { showNotification } from '@/hooks/useNotifications';
import { closestCenter, DndContext, DragEndEvent, KeyboardSensor, PointerSensor, useSensor, useSensors } from '@dnd-kit/core';
import { arrayMove, SortableContext, sortableKeyboardCoordinates, verticalListSortingStrategy } from '@dnd-kit/sortable';
import { router } from '@inertiajs/react';
import { useMemo, useState } from 'react';

import { CategoryCombobox } from '@/components/CategoryCombobox';
import { ComboItemCard } from '@/components/combos/ComboItemCard';
import { CreatePageLayout } from '@/components/create-page-layout';
import { FormSection } from '@/components/form-section';
import { ImageUpload } from '@/components/ImageUpload';
import { PriceFields } from '@/components/PriceFields';
import { CreateProductsSkeleton } from '@/components/skeletons';
import { Button } from '@/components/ui/button';
import { FormField } from '@/components/ui/form-field';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { Textarea } from '@/components/ui/textarea';
import { NOTIFICATIONS } from '@/constants/ui-constants';
import { generateUniqueItemId, prepareComboDataForSubmit, validateMinimumComboStructure } from '@/utils/comboHelpers';
import { Banknote, Package, Package2, Plus } from 'lucide-react';

interface ProductVariant {
    id: number;
    product_id: number;
    name: string;
    size: string;
    precio_pickup_capital: number;
}

interface Product {
    id: number;
    name: string;
    has_variants: boolean;
    is_active: boolean;
    variants?: ProductVariant[];
    category?: {
        id: number;
        name: string;
    };
}

interface Category {
    id: number;
    name: string;
}

interface ChoiceOption {
    id: string;
    product_id: number;
    variant_id?: number | null;
    sort_order: number;
}

interface ComboItem {
    id: string;
    is_choice_group: boolean;
    choice_label?: string;
    product_id?: number | null;
    variant_id?: number | null;
    quantity: number;
    sort_order: number;
    options?: ChoiceOption[];
}

interface CreateComboPageProps {
    products: Product[];
    categories: Category[];
}

export default function ComboCreate({ products, categories }: CreateComboPageProps) {
    const [formData, setFormData] = useState({
        category_id: '',
        name: '',
        description: '',
        is_active: true,
        precio_pickup_capital: '',
        precio_domicilio_capital: '',
        precio_pickup_interior: '',
        precio_domicilio_interior: '',
    });

    const [imageFile, setImageFile] = useState<File | null>(null);
    const [imagePreview, setImagePreview] = useState<string | null>(null);
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [processing, setProcessing] = useState(false);
    const [localItems, setLocalItems] = useState<ComboItem[]>([]);

    const hasInactiveProducts = useMemo(() => {
        return localItems.some((item) => {
            if (item.is_choice_group && item.options) {
                return item.options.some((option) => {
                    const product = products.find((p) => p.id === option.product_id);
                    return product && !product.is_active;
                });
            } else if (item.product_id) {
                const product = products.find((p) => p.id === item.product_id);
                return product && !product.is_active;
            }
            return false;
        });
    }, [localItems, products]);

    const sensors = useSensors(
        useSensor(PointerSensor),
        useSensor(KeyboardSensor, {
            coordinateGetter: sortableKeyboardCoordinates,
        }),
    );

    const handleInputChange = (field: string, value: string | boolean) => {
        setFormData((prev) => ({ ...prev, [field]: value }));
        if (errors[field]) {
            setErrors((prev) => {
                const newErrors = { ...prev };
                delete newErrors[field];
                return newErrors;
            });
        }
    };

    const handleImageChange = (file: File | null, previewUrl: string | null) => {
        setImageFile(file);
        setImagePreview(previewUrl);
        if (errors.image) {
            setErrors((prev) => {
                const newErrors = { ...prev };
                delete newErrors.image;
                return newErrors;
            });
        }
    };

    const addItem = () => {
        const newItem: ComboItem = {
            id: generateUniqueItemId(),
            is_choice_group: false,
            product_id: null,
            variant_id: null,
            quantity: 1,
            sort_order: localItems.length + 1,
            options: [],
        };
        setLocalItems([...localItems, newItem]);
    };

    const removeItem = (index: number) => {
        setLocalItems(localItems.filter((_, i) => i !== index));
    };

    const updateItem = (index: number, field: string, value: string | number | boolean | ChoiceOption[] | null) => {
        const updated = [...localItems];
        updated[index] = { ...updated[index], [field]: value };
        setLocalItems(updated);
    };

    const batchUpdateItem = (index: number, updates: Partial<ComboItem>) => {
        const updated = [...localItems];
        updated[index] = { ...updated[index], ...updates };
        setLocalItems(updated);
    };

    const handleDragEnd = (event: DragEndEvent) => {
        const { active, over } = event;

        if (over && active.id !== over.id) {
            setLocalItems((items) => {
                const oldIndex = items.findIndex((item) => item.id === active.id);
                const newIndex = items.findIndex((item) => item.id === over.id);
                return arrayMove(items, oldIndex, newIndex);
            });
        }
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        const validation = validateMinimumComboStructure(localItems);

        if (!validation.valid) {
            showNotification.error(validation.errors[0]);
            return;
        }

        setProcessing(true);

        const preparedItems = prepareComboDataForSubmit(localItems);

        const data = new FormData();
        data.append('category_id', formData.category_id);
        data.append('name', formData.name);
        data.append('description', formData.description);
        data.append('is_active', formData.is_active ? '1' : '0');
        data.append('precio_pickup_capital', formData.precio_pickup_capital);
        data.append('precio_domicilio_capital', formData.precio_domicilio_capital);
        data.append('precio_pickup_interior', formData.precio_pickup_interior);
        data.append('precio_domicilio_interior', formData.precio_domicilio_interior);

        if (imageFile) {
            data.append('image', imageFile);
        }

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

        router.post(route('menu.combos.store'), data, {
            forceFormData: true,
            onSuccess: () => {
                setFormData({
                    category_id: '',
                    name: '',
                    description: '',
                    is_active: true,
                    precio_pickup_capital: '',
                    precio_domicilio_capital: '',
                    precio_pickup_interior: '',
                    precio_domicilio_interior: '',
                });
                setImageFile(null);
                setImagePreview(null);
                setLocalItems([]);
                setProcessing(false);
            },
            onError: (errors: Record<string, string>) => {
                setErrors(errors);
                setProcessing(false);
                if (Object.keys(errors).length === 0) {
                    showNotification.error(NOTIFICATIONS.error.dataLoading);
                }
            },
        });
    };

    return (
        <CreatePageLayout
            title="Nuevo Combo"
            description="Crea un nuevo combo seleccionando productos y definiendo precios"
            backHref={route('menu.combos.index')}
            backLabel="Volver"
            onSubmit={handleSubmit}
            submitLabel="Guardar"
            processing={processing}
            pageTitle="Crear Combo"
            loading={processing}
            loadingSkeleton={CreateProductsSkeleton}
        >
            <FormSection icon={Package2} title="Información Básica" description="Datos principales del combo">
                <div className="flex items-center justify-between rounded-lg border p-4">
                    <Label htmlFor="is_active" className="text-base">
                        Combo activo
                    </Label>
                    <Switch id="is_active" checked={formData.is_active} onCheckedChange={(checked) => handleInputChange('is_active', checked as boolean)} />
                </div>

                <CategoryCombobox
                    value={formData.category_id ? Number(formData.category_id) : null}
                    onChange={(value) => handleInputChange('category_id', value ? String(value) : '')}
                    categories={categories}
                    label="Categoría"
                    error={errors.category_id}
                    required
                />

                <FormField label="Nombre" error={errors.name} required>
                    <Input id="name" type="text" value={formData.name} onChange={(e) => handleInputChange('name', e.target.value)} />
                </FormField>

                <FormField label="Descripción" error={errors.description}>
                    <Textarea id="description" value={formData.description} onChange={(e) => handleInputChange('description', e.target.value)} rows={2} />
                </FormField>

                <ImageUpload
                    label="Imagen del Combo"
                    currentImage={imagePreview}
                    onImageChange={handleImageChange}
                    error={errors.image}
                />
            </FormSection>

            {hasInactiveProducts && (
                <div className="mt-6 rounded-lg border border-amber-200 bg-amber-50 p-4 dark:border-amber-800 dark:bg-amber-950/20">
                    <p className="text-sm font-medium text-amber-800 dark:text-amber-200">
                        ⚠ Advertencia: Este combo tiene productos inactivos seleccionados. El combo no estará disponible para los clientes hasta que se activen todos los productos.
                    </p>
                </div>
            )}

            <FormSection icon={Banknote} title="Precios del Combo" description="Define el precio del combo completo" className="mt-8">
                <PriceFields
                    capitalPickup={formData.precio_pickup_capital}
                    capitalDomicilio={formData.precio_domicilio_capital}
                    interiorPickup={formData.precio_pickup_interior}
                    interiorDomicilio={formData.precio_domicilio_interior}
                    onChangeCapitalPickup={(value) => handleInputChange('precio_pickup_capital', value)}
                    onChangeCapitalDomicilio={(value) => handleInputChange('precio_domicilio_capital', value)}
                    onChangeInteriorPickup={(value) => handleInputChange('precio_pickup_interior', value)}
                    onChangeInteriorDomicilio={(value) => handleInputChange('precio_domicilio_interior', value)}
                    errors={{
                        capitalPickup: errors.precio_pickup_capital,
                        capitalDomicilio: errors.precio_domicilio_capital,
                        interiorPickup: errors.precio_pickup_interior,
                        interiorDomicilio: errors.precio_domicilio_interior,
                    }}
                />
            </FormSection>

            <FormSection icon={Package} title="Items del Combo" description="Define productos fijos o grupos de elección">
                <div className="mb-4 flex items-center justify-between rounded-lg border border-muted bg-muted/50 px-4 py-2">
                    <p className="text-sm text-muted-foreground">Un combo debe tener al menos 2 items</p>
                    <span className="text-xs font-medium text-muted-foreground">Actual: {localItems.length}</span>
                </div>

                {localItems.length > 0 ? (
                    <DndContext sensors={sensors} collisionDetection={closestCenter} onDragEnd={handleDragEnd}>
                        <SortableContext items={localItems.map((item) => item.id)} strategy={verticalListSortingStrategy}>
                            <div className="space-y-4">
                                {localItems.map((item, index) => (
                                    <ComboItemCard
                                        key={item.id}
                                        item={item}
                                        index={index}
                                        products={products}
                                        onUpdate={(field, value) => updateItem(index, field, value)}
                                        onBatchUpdate={(updates) => batchUpdateItem(index, updates)}
                                        onRemove={() => removeItem(index)}
                                        errors={errors}
                                        canDelete={true}
                                    />
                                ))}
                            </div>
                        </SortableContext>
                    </DndContext>
                ) : (
                    <div className="rounded-lg border border-dashed border-muted-foreground/25 p-8 text-center">
                        <p className="text-sm text-muted-foreground">No hay items en el combo</p>
                        <p className="mt-1 text-xs text-muted-foreground">Agrega al menos 2 items para crear el combo</p>
                    </div>
                )}

                <Button type="button" variant="outline" onClick={addItem} className="mt-4 w-full">
                    <Plus className="mr-2 h-4 w-4" />
                    Agregar Item
                </Button>

                {errors.items && <p className="mt-2 text-sm text-destructive">{errors.items}</p>}
            </FormSection>
        </CreatePageLayout>
    );
}
