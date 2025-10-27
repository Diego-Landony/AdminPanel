import { showNotification } from '@/hooks/useNotifications';
import { closestCenter, DndContext, DragEndEvent, KeyboardSensor, PointerSensor, useSensor, useSensors } from '@dnd-kit/core';
import { arrayMove, SortableContext, sortableKeyboardCoordinates, verticalListSortingStrategy } from '@dnd-kit/sortable';
import { useForm } from '@inertiajs/react';
import { useState } from 'react';

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
    const { data, setData, post, processing, errors, reset } = useForm({
        category_id: '',
        name: '',
        description: '',
        image: '',
        is_active: true,
        precio_pickup_capital: '',
        precio_domicilio_capital: '',
        precio_pickup_interior: '',
        precio_domicilio_interior: '',
        items: [] as ComboItem[],
    });

    const [localItems, setLocalItems] = useState<ComboItem[]>([]);

    const sensors = useSensors(
        useSensor(PointerSensor),
        useSensor(KeyboardSensor, {
            coordinateGetter: sortableKeyboardCoordinates,
        }),
    );

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
        const updated = [...localItems, newItem];
        setLocalItems(updated);
        setData('items', updated);
    };

    const removeItem = (index: number) => {
        const updated = localItems.filter((_, i) => i !== index);
        setLocalItems(updated);
        setData('items', updated);
    };

    const updateItem = (index: number, field: string, value: any) => {
        const updated = [...localItems];
        updated[index] = { ...updated[index], [field]: value };
        setLocalItems(updated);
        setData('items', updated);
    };

    const batchUpdateItem = (index: number, updates: Partial<ComboItem>) => {
        const updated = [...localItems];
        updated[index] = { ...updated[index], ...updates };
        setLocalItems(updated);
        setData('items', updated);
    };

    const handleDragEnd = (event: DragEndEvent) => {
        const { active, over } = event;

        if (over && active.id !== over.id) {
            setLocalItems((items) => {
                const oldIndex = items.findIndex((item) => item.id === active.id);
                const newIndex = items.findIndex((item) => item.id === over.id);

                const newItems = arrayMove(items, oldIndex, newIndex);
                setData('items', newItems);
                return newItems;
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

        const preparedItems = prepareComboDataForSubmit(localItems);

        const submitData = {
            ...data,
            items: preparedItems,
        };

        post(route('menu.combos.store'), submitData, {
            onSuccess: () => {
                reset();
                setLocalItems([]);
            },
            onError: (errors) => {
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
                    <Switch id="is_active" checked={data.is_active} onCheckedChange={(checked) => setData('is_active', checked as boolean)} />
                </div>

                <CategoryCombobox
                    value={data.category_id ? Number(data.category_id) : null}
                    onChange={(value) => setData('category_id', value ? String(value) : '')}
                    categories={categories}
                    label="Categoría"
                    error={errors.category_id}
                    required
                />

                <FormField label="Nombre" error={errors.name} required>
                    <Input id="name" type="text" value={data.name} onChange={(e) => setData('name', e.target.value)} />
                </FormField>

                <FormField label="Descripción" error={errors.description}>
                    <Textarea id="description" value={data.description} onChange={(e) => setData('description', e.target.value)} rows={2} />
                </FormField>

                <ImageUpload
                    label="Imagen del Combo"
                    currentImage={data.image}
                    onImageChange={(url) => setData('image', url || '')}
                    error={errors.image}
                />
            </FormSection>

            <FormSection icon={Banknote} title="Precios del Combo" description="Define el precio del combo completo" className="mt-8">
                <PriceFields
                    capitalPickup={data.precio_pickup_capital}
                    capitalDomicilio={data.precio_domicilio_capital}
                    interiorPickup={data.precio_pickup_interior}
                    interiorDomicilio={data.precio_domicilio_interior}
                    onChangeCapitalPickup={(value) => setData('precio_pickup_capital', value)}
                    onChangeCapitalDomicilio={(value) => setData('precio_domicilio_capital', value)}
                    onChangeInteriorPickup={(value) => setData('precio_pickup_interior', value)}
                    onChangeInteriorDomicilio={(value) => setData('precio_domicilio_interior', value)}
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
