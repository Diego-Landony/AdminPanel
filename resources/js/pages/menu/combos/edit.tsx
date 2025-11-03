import { closestCenter, DndContext, DragEndEvent, KeyboardSensor, PointerSensor, useSensor, useSensors } from '@dnd-kit/core';
import { arrayMove, SortableContext, sortableKeyboardCoordinates, verticalListSortingStrategy } from '@dnd-kit/sortable';
import { router } from '@inertiajs/react';
import { useMemo, useState } from 'react';

import { CategoryCombobox } from '@/components/CategoryCombobox';
import { ComboItemCard } from '@/components/combos/ComboItemCard';
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
import { generateUniqueItemId, prepareComboDataForSubmit, validateMinimumComboStructure } from '@/utils/comboHelpers';
import { AlertCircle, Banknote, Package, Package2, Plus } from 'lucide-react';

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
    id: number;
    product_id: number;
    variant_id?: number | null;
    sort_order: number;
    product: {
        id: number;
        name: string;
        is_active: boolean;
    };
    variant?: {
        id: number;
        name: string;
    } | null;
}

interface ComboItem {
    id: number | string;
    is_choice_group: boolean;
    choice_label?: string | null;
    product_id?: number | null;
    variant_id?: number | null;
    quantity: number;
    sort_order: number;
    product?: {
        id: number;
        name: string;
        is_active: boolean;
    } | null;
    variant?: {
        id: number;
        name: string;
        size: string;
    } | null;
    options?: ChoiceOption[];
}

interface Combo {
    id: number;
    name: string;
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

interface LocalComboItem {
    id: string;
    is_choice_group: boolean;
    choice_label?: string;
    product_id?: number | null;
    variant_id?: number | null;
    quantity: number;
    sort_order: number;
    options?: {
        id: string;
        product_id: number;
        variant_id?: number | null;
        sort_order: number;
    }[];
}

export default function ComboEdit({ combo, products, categories }: EditComboPageProps) {
    // Map server data to local format
    const initialItems: LocalComboItem[] = combo.items.map((item) => ({
        id: `item-${item.id}`,
        is_choice_group: item.is_choice_group,
        choice_label: item.choice_label || undefined,
        product_id: item.product_id || null,
        variant_id: item.variant_id || null,
        quantity: item.quantity,
        sort_order: item.sort_order,
        options:
            item.options?.map((opt) => ({
                id: `option-${opt.id}`,
                product_id: opt.product_id,
                variant_id: opt.variant_id || null,
                sort_order: opt.sort_order,
            })) || [],
    }));

    const [formData, setFormData] = useState({
        category_id: String(combo.category?.id || ''),
        name: combo.name,
        description: combo.description || '',
        image: combo.image || '',
        is_active: combo.is_active,
        precio_pickup_capital: String(combo.precio_pickup_capital),
        precio_domicilio_capital: String(combo.precio_domicilio_capital),
        precio_pickup_interior: String(combo.precio_pickup_interior),
        precio_domicilio_interior: String(combo.precio_domicilio_interior),
    });

    const [errors, setErrors] = useState<Record<string, string>>({});
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [localItems, setLocalItems] = useState<LocalComboItem[]>(initialItems);

    const sensors = useSensors(
        useSensor(PointerSensor),
        useSensor(KeyboardSensor, {
            coordinateGetter: sortableKeyboardCoordinates,
        }),
    );

    const addItem = () => {
        const newItem: LocalComboItem = {
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

    const updateItem = (
        index: number,
        field: string,
        value: string | number | boolean | { id: string; product_id: number; variant_id?: number | null; sort_order: number }[] | null,
    ) => {
        const updated = [...localItems];
        updated[index] = { ...updated[index], [field]: value };
        setLocalItems(updated);
    };

    const batchUpdateItem = (index: number, updates: Partial<LocalComboItem>) => {
        console.log('🔄 [Edit] batchUpdateItem called:', { index, updates });
        const updated = [...localItems];
        updated[index] = { ...updated[index], ...updates };
        console.log('🔄 [Edit] Updated item after batch:', updated[index]);
        setLocalItems(updated);
    };

    const handleDragEnd = (event: DragEndEvent) => {
        const { active, over } = event;

        if (over && active.id !== over.id) {
            const oldIndex = localItems.findIndex((item) => item.id === active.id);
            const newIndex = localItems.findIndex((item) => item.id === over.id);
            setLocalItems(arrayMove(localItems, oldIndex, newIndex));
        }
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        const validation = validateMinimumComboStructure(localItems);
        if (!validation.valid) {
            setErrors({ items: validation.errors[0] });
            return;
        }

        setIsSubmitting(true);

        const preparedItems = prepareComboDataForSubmit(localItems);

        router.put(
            `/menu/combos/${combo.id}`,
            {
                ...formData,
                items: preparedItems,
            },
            {
                onSuccess: () => {
                    // Redirección manejada por el controlador
                },
                onError: (errors) => {
                    setErrors(errors as Record<string, string>);
                    setIsSubmitting(false);
                },
            },
        );
    };

    // Detect inactive products in both fixed items and choice groups
    const inactiveItems = useMemo(() => {
        const inactive: Array<{ type: 'fixed' | 'choice'; productName: string; groupLabel?: string }> = [];

        localItems.forEach((item) => {
            if (item.is_choice_group && item.options) {
                // Check choice group options
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
                // Check fixed items
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
            {inactiveItems.length > 0 && (
                <div className="mb-6 rounded-lg border border-amber-200 bg-amber-50 p-4 dark:border-amber-800 dark:bg-amber-950/20">
                    <div className="flex gap-3">
                        <AlertCircle className="h-5 w-5 text-amber-800 dark:text-amber-200" />
                        <div className="flex-1">
                            <h3 className="font-semibold text-amber-800 dark:text-amber-200">Productos Inactivos Detectados</h3>
                            <p className="mt-1 text-sm text-amber-800 dark:text-amber-200">
                                Este combo tiene productos inactivos que no estarán disponibles para los clientes:
                            </p>
                            <ul className="mt-2 list-inside list-disc space-y-1 text-sm text-amber-800 dark:text-amber-200">
                                {inactiveItems.map((item, index) => (
                                    <li key={index}>
                                        <span className="font-medium">{item.productName}</span>
                                        {item.type === 'choice' && item.groupLabel && (
                                            <>
                                                {' en '}
                                                <span className="opacity-80">{item.groupLabel}</span>
                                            </>
                                        )}
                                        {item.type === 'fixed' && <span className="opacity-80"> (Item fijo)</span>}
                                    </li>
                                ))}
                            </ul>
                        </div>
                    </div>
                </div>
            )}

            <FormSection icon={Package2} title="Información Básica" description="Datos principales del combo">
                <div className="flex items-center justify-between rounded-lg border p-4">
                    <Label htmlFor="is_active" className="text-base">
                        Combo activo
                    </Label>
                    <Switch
                        id="is_active"
                        checked={formData.is_active}
                        onCheckedChange={(checked) => setFormData({ ...formData, is_active: checked as boolean })}
                    />
                </div>

                <CategoryCombobox
                    value={formData.category_id ? Number(formData.category_id) : null}
                    onChange={(value) => setFormData({ ...formData, category_id: value ? String(value) : '' })}
                    categories={categories}
                    label="Categoría"
                    error={errors.category_id}
                    required
                />

                <FormField label="Nombre" error={errors.name} required>
                    <Input id="name" type="text" value={formData.name} onChange={(e) => setFormData({ ...formData, name: e.target.value })} />
                </FormField>

                <FormField label="Descripción" error={errors.description}>
                    <Textarea
                        id="description"
                        value={formData.description}
                        onChange={(e) => setFormData({ ...formData, description: e.target.value })}
                        rows={2}
                    />
                </FormField>

                <ImageUpload
                    label="Imagen del Combo"
                    currentImage={formData.image}
                    onImageChange={(url) => setFormData({ ...formData, image: url || '' })}
                    error={errors.image}
                />
            </FormSection>

            <FormSection icon={Banknote} title="Precios del Combo" description="Define el precio del combo completo" className="mt-8">
                <PriceFields
                    capitalPickup={formData.precio_pickup_capital}
                    capitalDomicilio={formData.precio_domicilio_capital}
                    interiorPickup={formData.precio_pickup_interior}
                    interiorDomicilio={formData.precio_domicilio_interior}
                    onChangeCapitalPickup={(value) => setFormData({ ...formData, precio_pickup_capital: value })}
                    onChangeCapitalDomicilio={(value) => setFormData({ ...formData, precio_domicilio_capital: value })}
                    onChangeInteriorPickup={(value) => setFormData({ ...formData, precio_pickup_interior: value })}
                    onChangeInteriorDomicilio={(value) => setFormData({ ...formData, precio_domicilio_interior: value })}
                    errors={{
                        capitalPickup: errors.precio_pickup_capital,
                        capitalDomicilio: errors.precio_domicilio_capital,
                        interiorPickup: errors.precio_pickup_interior,
                        interiorDomicilio: errors.precio_domicilio_interior,
                    }}
                />
            </FormSection>

            <FormSection icon={Package} title="Items del Combo" description="Define productos fijos o grupos de elección">
                {localItems.length > 0 && (
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
                                        canDelete={localItems.length > 2}
                                    />
                                ))}
                            </div>
                        </SortableContext>
                    </DndContext>
                )}

                <Button type="button" variant="outline" onClick={addItem} className="mt-4 w-full">
                    <Plus className="mr-2 h-4 w-4" />
                    Agregar Item
                </Button>

                {errors.items && <p className="mt-2 text-sm text-destructive">{errors.items}</p>}
            </FormSection>
        </EditPageLayout>
    );
}
