import { showNotification } from '@/hooks/useNotifications';
import { useForm } from '@inertiajs/react';
import React, { useState } from 'react';
import { PLACEHOLDERS } from '@/constants/ui-constants';
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
import { Checkbox } from '@/components/ui/checkbox';
import { FormField } from '@/components/ui/form-field';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { NOTIFICATIONS } from '@/constants/ui-constants';
import { generateUniqueId } from '@/utils/generateId';
import { Banknote, GripVertical, ListChecks, Package, Plus, X } from 'lucide-react';

interface Category {
    id: number;
    name: string;
}

interface Section {
    id: number;
    title: string;
}

interface ProductVariant {
    id: string;
    name: string;
    precio_pickup_capital: string;
    precio_domicilio_capital: string;
    precio_pickup_interior: string;
    precio_domicilio_interior: string;
}

interface CreateProductPageProps {
    categories: Category[];
    sections: Section[];
}

interface SortableVariantProps {
    variant: ProductVariant;
    index: number;
    onUpdate: (index: number, field: keyof Omit<ProductVariant, 'id'>, value: string) => void;
    onRemove: (index: number) => void;
    errors: Record<string, string>;
    canDelete: boolean;
}

function SortableVariant({ variant, index, onUpdate, onRemove, errors, canDelete }: SortableVariantProps) {
    const {
        attributes,
        listeners,
        setNodeRef,
        transform,
        transition,
        isDragging,
    } = useSortable({ id: variant.id });

    const style = {
        transform: CSS.Transform.toString(transform),
        transition,
        opacity: isDragging ? 0.5 : 1,
    };

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
                    {variant.name || `Variante ${index + 1}`}
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

            <FormField
                label="Nombre"
                error={errors[`variants.${index}.name`]}
                required
            >
                <Input
                    type="text"
                    value={variant.name}
                    onChange={(e) => onUpdate(index, 'name', e.target.value)}
                    placeholder={PLACEHOLDERS.productVariantSize}
                />
            </FormField>

            <PriceFields
                capitalPickup={variant.precio_pickup_capital}
                capitalDomicilio={variant.precio_domicilio_capital}
                interiorPickup={variant.precio_pickup_interior}
                interiorDomicilio={variant.precio_domicilio_interior}
                onChangeCapitalPickup={(value) => onUpdate(index, 'precio_pickup_capital', value)}
                onChangeCapitalDomicilio={(value) => onUpdate(index, 'precio_domicilio_capital', value)}
                onChangeInteriorPickup={(value) => onUpdate(index, 'precio_pickup_interior', value)}
                onChangeInteriorDomicilio={(value) => onUpdate(index, 'precio_domicilio_interior', value)}
                errors={{
                    capitalPickup: errors[`variants.${index}.precio_pickup_capital`],
                    capitalDomicilio: errors[`variants.${index}.precio_domicilio_capital`],
                    interiorPickup: errors[`variants.${index}.precio_pickup_interior`],
                    interiorDomicilio: errors[`variants.${index}.precio_domicilio_interior`],
                }}
            />
        </div>
    );
}

export default function ProductCreate({ categories, sections }: CreateProductPageProps) {
    const { data, setData, post, processing, errors, reset } = useForm({
        category_id: '',
        name: '',
        description: '',
        image: '',
        is_active: true,
        has_variants: false,
        precio_pickup_capital: '',
        precio_domicilio_capital: '',
        precio_pickup_interior: '',
        precio_domicilio_interior: '',
        variants: [] as ProductVariant[],
        sections: [] as number[],
    });

    const [localVariants, setLocalVariants] = useState<ProductVariant[]>([]);
    const [selectedSections, setSelectedSections] = useState<number[]>([]);

    const sensors = useSensors(
        useSensor(PointerSensor),
        useSensor(KeyboardSensor, {
            coordinateGetter: sortableKeyboardCoordinates,
        })
    );

    const addVariant = () => {
        const newVariant: ProductVariant = {
            id: generateUniqueId(),
            name: '',
            precio_pickup_capital: '',
            precio_domicilio_capital: '',
            precio_pickup_interior: '',
            precio_domicilio_interior: '',
        };
        const updated = [...localVariants, newVariant];
        setLocalVariants(updated);
        setData('variants', updated);
    };

    const removeVariant = (index: number) => {
        const updated = localVariants.filter((_, i) => i !== index);
        setLocalVariants(updated);
        setData('variants', updated);
    };

    const updateVariant = (index: number, field: keyof Omit<ProductVariant, 'id'>, value: string) => {
        const updated = [...localVariants];
        updated[index] = { ...updated[index], [field]: value };
        setLocalVariants(updated);
        setData('variants', updated);
    };

    const handleDragEnd = (event: DragEndEvent) => {
        const { active, over } = event;

        if (over && active.id !== over.id) {
            setLocalVariants((items) => {
                const oldIndex = items.findIndex((item) => item.id === active.id);
                const newIndex = items.findIndex((item) => item.id === over.id);

                const newItems = arrayMove(items, oldIndex, newIndex);
                setData('variants', newItems);
                return newItems;
            });
        }
    };

    const handleVariantToggle = (checked: boolean) => {
        setData('has_variants', checked);
        if (checked && localVariants.length === 0) {
            addVariant();
        }
    };

    const toggleSection = (sectionId: number) => {
        const newSelected = selectedSections.includes(sectionId)
            ? selectedSections.filter((id) => id !== sectionId)
            : [...selectedSections, sectionId];

        setSelectedSections(newSelected);
        setData('sections', newSelected);
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        console.log('=== INICIO SUBMIT PRODUCTO ===');
        console.log('1. Evento preventDefault ejecutado');
        console.log('2. Data actual:', data);
        console.log('3. Selected sections:', selectedSections);
        console.log('4. Local variants:', localVariants);

        // Limpiar los IDs temporales de las variantes antes de enviar
        const submitData = {
            ...data,
            sections: selectedSections,
            variants: localVariants.map(({ id, ...rest }) => ({ ...rest, _tempId: id })),
        };

        console.log('5. Submit data preparado:', submitData);
        console.log('6. Route:', route('menu.products.store'));
        console.log('7. Iniciando POST request...');

        post(route('menu.products.store'), {
            data: submitData,
            onSuccess: () => {
                console.log('✅ POST exitoso - onSuccess ejecutado');
                reset();
                setSelectedSections([]);
                setLocalVariants([]);
            },
            onError: (errors) => {
                console.error('❌ POST fallido - onError ejecutado');
                console.error('Errors recibidos:', errors);
                if (Object.keys(errors).length === 0) {
                    showNotification.error(NOTIFICATIONS.error.server);
                }
            },
            onFinish: () => {
                console.log('🏁 POST finalizado - onFinish ejecutado');
            },
            onBefore: () => {
                console.log('⏳ POST iniciado - onBefore ejecutado');
            },
        });

        console.log('8. POST request enviado');
        console.log('=== FIN SUBMIT PRODUCTO ===');
    };

    return (
        <CreatePageLayout
            title="Nuevo Producto"
            backHref={route('menu.products.index')}
            backLabel="Volver"
            onSubmit={handleSubmit}
            submitLabel="Guardar"
            processing={processing}
            pageTitle="Crear Producto"
            loading={processing}
            loadingSkeleton={CreateProductsSkeleton}
        >
            <FormSection icon={Package} title="Información Básica" description="Datos principales del producto">
                <FormField label="Categoría" error={errors.category_id} required>
                    <Select value={data.category_id} onValueChange={(value) => setData('category_id', value)}>
                        <SelectTrigger>
                            <SelectValue placeholder={PLACEHOLDERS.selectCategory} />
                        </SelectTrigger>
                        <SelectContent>
                            {categories.map((category) => (
                                <SelectItem key={category.id} value={String(category.id)}>
                                    {category.name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </FormField>

                <FormField label="Nombre" error={errors.name} required>
                    <Input
                        id="name"
                        type="text"
                        value={data.name}
                        onChange={(e) => setData('name', e.target.value)}
                        placeholder={PLACEHOLDERS.productName}
                    />
                </FormField>

                <FormField label="Descripción" error={errors.description}>
                    <Textarea
                        id="description"
                        value={data.description}
                        onChange={(e) => setData('description', e.target.value)}
                        placeholder={PLACEHOLDERS.productDescription}
                        rows={2}
                    />
                </FormField>

                <div className="flex items-center space-x-2">
                    <Checkbox
                        id="is_active"
                        checked={data.is_active}
                        onCheckedChange={(checked) => setData('is_active', checked as boolean)}
                    />
                    <Label htmlFor="is_active" className="text-sm leading-none font-medium cursor-pointer">
                        Producto activo
                    </Label>
                </div>

                <ImageUpload
                    label="Imagen del Producto"
                    currentImage={data.image}
                    onImageChange={(url) => setData('image', url || '')}
                    error={errors.image}
                />
            </FormSection>

            <FormSection
                icon={Banknote}
                title="Precios y Variantes"
                description="Define si el producto usa variantes (ej: 15cm, 30cm) o tiene un precio único"
            >
                <div className="flex items-center space-x-2 mb-6">
                    <Checkbox
                        id="has_variants"
                        checked={data.has_variants}
                        onCheckedChange={(checked) => handleVariantToggle(checked as boolean)}
                    />
                    <Label htmlFor="has_variants" className="text-sm leading-none font-medium cursor-pointer">
                        Producto con variantes
                    </Label>
                </div>

                {!data.has_variants && (
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
                )}

                {data.has_variants && (
                    <div className="space-y-4">
                        {localVariants.length > 0 && (
                            <DndContext sensors={sensors} collisionDetection={closestCenter} onDragEnd={handleDragEnd}>
                                <SortableContext items={localVariants.map((v) => v.id)} strategy={verticalListSortingStrategy}>
                                    <div className="space-y-4">
                                        {localVariants.map((variant, index) => (
                                            <SortableVariant
                                                key={variant.id}
                                                variant={variant}
                                                index={index}
                                                onUpdate={updateVariant}
                                                onRemove={removeVariant}
                                                errors={errors}
                                                canDelete={localVariants.length > 1}
                                            />
                                        ))}
                                    </div>
                                </SortableContext>
                            </DndContext>
                        )}

                        <Button type="button" variant="outline" onClick={addVariant} className="w-full">
                            <Plus className="h-4 w-4 mr-2" />
                            Agregar Variante
                        </Button>
                    </div>
                )}
            </FormSection>

            <FormSection
                icon={ListChecks}
                title="Secciones"
                description="Secciones de personalización (ej: vegetales, salsas, quesos)"
            >
                <div className="space-y-2">
                    {sections.length === 0 ? (
                        <p className="text-sm text-muted-foreground">No hay secciones disponibles</p>
                    ) : (
                        sections.map((section) => (
                            <div key={section.id} className="flex items-center space-x-2">
                                <Checkbox
                                    id={`section-${section.id}`}
                                    checked={selectedSections.includes(section.id)}
                                    onCheckedChange={() => toggleSection(section.id)}
                                />
                                <Label
                                    htmlFor={`section-${section.id}`}
                                    className="text-sm leading-none font-medium cursor-pointer"
                                >
                                    {section.title}
                                </Label>
                            </div>
                        ))
                    )}
                </div>
            </FormSection>
        </CreatePageLayout>
    );
}
