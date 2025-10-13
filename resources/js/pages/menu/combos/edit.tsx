import { router } from '@inertiajs/react';
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

import { EditPageLayout } from '@/components/edit-page-layout';
import { FormSection } from '@/components/form-section';
import { ImageUpload } from '@/components/ImageUpload';
import { PriceFields } from '@/components/PriceFields';
import { EditProductsSkeleton } from '@/components/skeletons';
import { Button } from '@/components/ui/button';
import { FormField } from '@/components/ui/form-field';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
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
    id: number | string;
    product_id: number;
    variant_id?: number | null;
    quantity: number;
    sort_order: number;
    product: {
        id: number;
        name: string;
    };
    variant?: {
        id: number;
        name: string;
        size: string;
    } | null;
}

interface Combo {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    image: string | null;
    precio_pickup_capital: number;
    precio_domicilio_capital: number;
    precio_pickup_interior: number;
    precio_domicilio_interior: number;
    is_active: boolean;
    sort_order: number;
    items: ComboItem[];
    category: Category | null;
}

interface EditComboPageProps {
    combo: Combo;
    products: Product[];
    categories: Category[];
}

interface FormData {
    category_id: string;
    name: string;
    description: string;
    image: string;
    is_active: boolean;
    precio_pickup_capital: string;
    precio_domicilio_capital: string;
    precio_pickup_interior: string;
    precio_domicilio_interior: string;
    items: {
        id: number | string;
        product_id: string;
        variant_id: string;
        quantity: string;
        sort_order: number;
    }[];
}

interface SortableItemProps {
    item: {
        id: number | string;
        product_id: string;
        variant_id: string;
        quantity: string;
        sort_order: number;
    };
    index: number;
    products: Product[];
    onUpdate: (index: number, field: 'product_id' | 'variant_id' | 'quantity', value: string) => void;
    onUpdateMultiple: (index: number, fields: Partial<{ product_id: string; variant_id: string; quantity: string }>) => void;
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

export default function ComboEdit({ combo, products, categories }: EditComboPageProps) {
    const [formData, setFormData] = useState<FormData>({
        category_id: String(combo.category?.id || ''),
        name: combo.name,
        description: combo.description || '',
        image: combo.image || '',
        is_active: combo.is_active,
        precio_pickup_capital: String(combo.precio_pickup_capital),
        precio_domicilio_capital: String(combo.precio_domicilio_capital),
        precio_pickup_interior: String(combo.precio_pickup_interior),
        precio_domicilio_interior: String(combo.precio_domicilio_interior),
        items: combo.items.map((item) => ({
            id: item.id,
            product_id: String(item.product_id),
            variant_id: String(item.variant_id || ''),
            quantity: String(item.quantity),
            sort_order: item.sort_order,
        })),
    });

    const [errors, setErrors] = useState<Record<string, string>>({});
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [localItems, setLocalItems] = useState(formData.items);

    const sensors = useSensors(
        useSensor(PointerSensor),
        useSensor(KeyboardSensor, {
            coordinateGetter: sortableKeyboardCoordinates,
        })
    );

    const handleInputChange = (field: keyof FormData, value: string | boolean | typeof formData.items) => {
        setFormData((prev) => ({
            ...prev,
            [field]: value,
        }));

        if (errors[field]) {
            setErrors((prev) => {
                const newErrors = { ...prev };
                delete newErrors[field];
                return newErrors;
            });
        }
    };

    const addItem = () => {
        const newItem = {
            id: generateUniqueId(),
            product_id: '',
            variant_id: '',
            quantity: '1',
            sort_order: localItems.length + 1,
        };
        const updated = [...localItems, newItem];
        setLocalItems(updated);
        handleInputChange('items', updated);
    };

    const removeItem = (index: number) => {
        const updated = localItems.filter((_, i) => i !== index);
        setLocalItems(updated);
        handleInputChange('items', updated);
    };

    const updateItem = (index: number, field: 'product_id' | 'variant_id' | 'quantity', value: string) => {
        const updated = [...localItems];
        updated[index] = { ...updated[index], [field]: value };
        setLocalItems(updated);
        handleInputChange('items', updated);
    };

    const updateMultipleFields = (index: number, fields: Partial<{ product_id: string; variant_id: string; quantity: string }>) => {
        const updated = [...localItems];
        updated[index] = { ...updated[index], ...fields };
        setLocalItems(updated);
        handleInputChange('items', updated);
    };

    const handleDragEnd = (event: DragEndEvent) => {
        const { active, over } = event;

        if (over && active.id !== over.id) {
            setLocalItems((items) => {
                const oldIndex = items.findIndex((item) => item.id === active.id);
                const newIndex = items.findIndex((item) => item.id === over.id);

                const newItems = arrayMove(items, oldIndex, newIndex);
                handleInputChange('items', newItems);
                return newItems;
            });
        }
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        setIsSubmitting(true);

        const submitData = {
            ...formData,
            items: localItems.map((item, index) => ({
                product_id: item.product_id,
                variant_id: item.variant_id || null,
                quantity: item.quantity,
                sort_order: index + 1,
            })),
        };

        router.put(`/menu/combos/${combo.id}`, submitData, {
            onSuccess: () => {
                // Redirección manejada por el controlador
            },
            onError: (errors) => {
                setErrors(errors as Record<string, string>);
                setIsSubmitting(false);
            },
        });
    };

    return (
        <EditPageLayout
            title="Editar Combo"
            description={`Modifica los datos del combo "${combo.name}"`}
            backHref={route('menu.combos.index')}
            backLabel="Volver"
            onSubmit={handleSubmit}
            submitLabel="Guardar"
            processing={isSubmitting}
            pageTitle={`Editar ${combo.name}`}
            loading={false}
            loadingSkeleton={EditProductsSkeleton}
        >
            <FormSection icon={Package2} title="Información Básica" description="Datos principales del combo">
                <div className="flex items-center justify-between rounded-lg border p-4">
                    <Label htmlFor="is_active" className="text-base">
                        Combo activo
                    </Label>
                    <Switch
                        id="is_active"
                        checked={formData.is_active}
                        onCheckedChange={(checked) => handleInputChange('is_active', checked as boolean)}
                    />
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

                <ImageUpload label="Imagen del Combo" currentImage={formData.image} onImageChange={(url) => handleInputChange('image', url || '')} error={errors.image} />
            </FormSection>

            <FormSection
                icon={Banknote}
                title="Precios del Combo"
                description="Define el precio del combo completo"
                className="mt-8"
            >
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
        </EditPageLayout>
    );
}
