import { showNotification } from '@/hooks/useNotifications';
import { useForm } from '@inertiajs/react';
import { useState } from 'react';
import {
    DndContext,
    closestCenter,
    KeyboardSensor,
    PointerSensor,
    useSensor,
    useSensors,
    DragEndEvent,
} from '@dnd-kit/core';
import {
    arrayMove,
    SortableContext,
    sortableKeyboardCoordinates,
    useSortable,
    verticalListSortingStrategy,
} from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';

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
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { NOTIFICATIONS } from '@/constants/ui-constants';
import { generateUniqueId } from '@/utils/generateId';
import { CategoryCombobox } from '@/components/CategoryCombobox';
import { ProductCombobox } from '@/components/ProductCombobox';
import { Banknote, GripVertical, Package, Package2, Plus, X } from 'lucide-react';

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

interface ComboItem {
    id: string;
    product_id: string;
    variant_id: string;
    quantity: string;
    sort_order: number;
}

interface CreateComboPageProps {
    products: Product[];
    categories: Category[];
}

interface SortableItemProps {
    item: ComboItem;
    index: number;
    products: Product[];
    onUpdate: (index: number, field: keyof Omit<ComboItem, 'id' | 'sort_order'>, value: string) => void;
    onUpdateMultiple: (index: number, fields: Partial<Omit<ComboItem, 'id' | 'sort_order'>>) => void;
    onRemove: (index: number) => void;
    errors: Record<string, string>;
    canDelete: boolean;
}

function SortableItem({ item, index, products, onUpdate, onUpdateMultiple, onRemove, errors, canDelete }: SortableItemProps) {
    const {
        attributes,
        listeners,
        setNodeRef,
        transform,
        transition,
        isDragging,
    } = useSortable({ id: item.id });

    const style = {
        transform: CSS.Transform.toString(transform),
        transition,
        opacity: isDragging ? 0.5 : 1,
    };

    const selectedProduct = products.find(p => p.id === Number(item.product_id));
    const hasVariants = selectedProduct?.has_variants && selectedProduct?.variants && selectedProduct.variants.length > 0;

    return (
        <div
            ref={setNodeRef}
            style={style}
            className={`border border-border rounded-lg p-4 space-y-4 ${isDragging ? 'shadow-lg bg-muted/50' : ''}`}
        >
            <div className="flex items-center gap-3 mb-4">
                <button
                    type="button"
                    className="cursor-grab active:cursor-grabbing text-muted-foreground hover:text-foreground transition-colors"
                    {...attributes}
                    {...listeners}
                >
                    <GripVertical className="h-5 w-5" />
                </button>

                <h4 className="text-sm font-medium flex-1">
                    {selectedProduct?.name || `Producto ${index + 1}`}
                </h4>

                {canDelete && (
                    <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        onClick={() => onRemove(index)}
                        className="h-8 w-8 p-0"
                    >
                        <X className="h-4 w-4" />
                    </Button>
                )}
            </div>

            <ProductCombobox
                value={item.product_id ? Number(item.product_id) : null}
                onChange={(value) => {
                    // Actualizar product_id y resetear variant_id en una sola operación
                    onUpdateMultiple(index, {
                        product_id: value ? String(value) : '',
                        variant_id: '',
                    });
                }}
                products={products}
                label="Producto"
                error={errors[`items.${index}.product_id`]}
                required
            />

            {hasVariants && (
                <FormField
                    label="Variante"
                    error={errors[`items.${index}.variant_id`]}
                    required
                >
                    <Select
                        value={item.variant_id}
                        onValueChange={(value) => onUpdate(index, 'variant_id', value)}
                    >
                        <SelectTrigger>
                            <SelectValue placeholder="Selecciona una variante" />
                        </SelectTrigger>
                        <SelectContent>
                            {selectedProduct.variants?.map((variant) => (
                                <SelectItem key={variant.id} value={String(variant.id)}>
                                    {variant.name} {variant.size && `- ${variant.size}`}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </FormField>
            )}

            <FormField
                label="Cantidad"
                error={errors[`items.${index}.quantity`]}
                required
            >
                <Input
                    type="number"
                    min="1"
                    max="10"
                    value={item.quantity}
                    onChange={(e) => onUpdate(index, 'quantity', e.target.value)}
                />
            </FormField>
        </div>
    );
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
        })
    );

    const addItem = () => {
        const newItem: ComboItem = {
            id: generateUniqueId(),
            product_id: '',
            variant_id: '',
            quantity: '1',
            sort_order: localItems.length + 1,
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

    const updateItem = (index: number, field: keyof Omit<ComboItem, 'id' | 'sort_order'>, value: string) => {
        const updated = [...localItems];
        updated[index] = { ...updated[index], [field]: value };
        setLocalItems(updated);
        setData('items', updated);
    };

    const updateMultipleFields = (index: number, fields: Partial<Omit<ComboItem, 'id' | 'sort_order'>>) => {
        const updated = [...localItems];
        updated[index] = { ...updated[index], ...fields };
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

        const submitData = {
            ...data,
            items: localItems.map(({ id, ...rest }, index) => ({
                product_id: rest.product_id,
                variant_id: rest.variant_id || null,
                quantity: rest.quantity,
                sort_order: index + 1,
            })),
        };

        post(route('menu.combos.store'), {
            data: submitData,
            onSuccess: () => {
                reset();
                setLocalItems([]);
            },
            onError: (errors) => {
                if (Object.keys(errors).length === 0) {
                    showNotification.error(NOTIFICATIONS.error.server);
                }
            },
        });
    };

    return (
        <CreatePageLayout
            title="Nuevo Combo"
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
                    <Switch
                        id="is_active"
                        checked={data.is_active}
                        onCheckedChange={(checked) => setData('is_active', checked as boolean)}
                    />
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
                    <Input
                        id="name"
                        type="text"
                        value={data.name}
                        onChange={(e) => setData('name', e.target.value)}
                    />
                </FormField>

                <FormField label="Descripción" error={errors.description}>
                    <Textarea
                        id="description"
                        value={data.description}
                        onChange={(e) => setData('description', e.target.value)}
                        rows={2}
                    />
                </FormField>

                <ImageUpload
                    label="Imagen del Combo"
                    currentImage={data.image}
                    onImageChange={(url) => setData('image', url || '')}
                    error={errors.image}
                />
            </FormSection>

            <FormSection
                icon={Banknote}
                title="Precios del Combo"
                description="Define el precio del combo completo"
                className="mt-8"
            >
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

            <FormSection
                icon={Package}
                title="Productos del Combo"
                description="Agrega al menos 2 productos al combo. Si el producto tiene variantes, debes seleccionar una."
            >
                {localItems.length > 0 && (
                    <DndContext sensors={sensors} collisionDetection={closestCenter} onDragEnd={handleDragEnd}>
                        <SortableContext items={localItems.map((item) => item.id)} strategy={verticalListSortingStrategy}>
                            <div className="space-y-4">
                                {localItems.map((item, index) => (
                                    <SortableItem
                                        key={item.id}
                                        item={item}
                                        index={index}
                                        products={products}
                                        onUpdate={updateItem}
                                        onUpdateMultiple={updateMultipleFields}
                                        onRemove={removeItem}
                                        errors={errors}
                                        canDelete={localItems.length > 1}
                                    />
                                ))}
                            </div>
                        </SortableContext>
                    </DndContext>
                )}

                <Button
                    type="button"
                    variant="outline"
                    onClick={addItem}
                    className="w-full mt-4"
                >
                    <Plus className="h-4 w-4 mr-2" />
                    Agregar Producto
                </Button>

                {errors.items && (
                    <p className="text-sm text-destructive mt-2">{errors.items}</p>
                )}
            </FormSection>
        </CreatePageLayout>
    );
}
