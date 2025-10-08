import { router } from '@inertiajs/react';
import { useState } from 'react';
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

import { EditPageLayout } from '@/components/edit-page-layout';
import { FormSection } from '@/components/form-section';
import { ImageUpload } from '@/components/ImageUpload';
import { PriceFields } from '@/components/PriceFields';
import { EditProductsSkeleton } from '@/components/skeletons';
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
    id: number | string;
    name: string;
    precio_pickup_capital: string;
    precio_domicilio_capital: string;
    precio_pickup_interior: string;
    precio_domicilio_interior: string;
}

interface Product {
    id: number;
    category_id: number | null;
    name: string;
    description: string | null;
    image: string | null;
    is_customizable: boolean;
    is_active: boolean;
    has_variants: boolean;
    precio_pickup_capital: string | null;
    precio_domicilio_capital: string | null;
    precio_pickup_interior: string | null;
    precio_domicilio_interior: string | null;
    category: Category | null;
    sections: Section[];
    variants: ProductVariant[];
}

interface EditProductPageProps {
    product: Product;
    categories: Category[];
    sections: Section[];
}

interface FormData {
    category_id: string;
    name: string;
    description: string;
    image: string;
    is_active: boolean;
    has_variants: boolean;
    precio_pickup_capital: string;
    precio_domicilio_capital: string;
    precio_pickup_interior: string;
    precio_domicilio_interior: string;
    variants: ProductVariant[];
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

export default function ProductEdit({ product, categories, sections }: EditProductPageProps) {
    const [formData, setFormData] = useState<FormData>({
        category_id: product.category_id ? String(product.category_id) : '',
        name: product.name,
        description: product.description || '',
        image: product.image || '',
        is_active: product.is_active,
        has_variants: product.has_variants,
        precio_pickup_capital: product.precio_pickup_capital || '',
        precio_domicilio_capital: product.precio_domicilio_capital || '',
        precio_pickup_interior: product.precio_pickup_interior || '',
        precio_domicilio_interior: product.precio_domicilio_interior || '',
        variants: product.variants.map((v) => ({
            id: v.id,
            name: v.name,
            precio_pickup_capital: String(v.precio_pickup_capital),
            precio_domicilio_capital: String(v.precio_domicilio_capital),
            precio_pickup_interior: String(v.precio_pickup_interior),
            precio_domicilio_interior: String(v.precio_domicilio_interior),
        })),
    });

    const [errors, setErrors] = useState<Record<string, string>>({});
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [selectedSections, setSelectedSections] = useState<number[]>(product.sections.map((s) => s.id));
    const [localVariants, setLocalVariants] = useState<ProductVariant[]>(formData.variants);

    const sensors = useSensors(
        useSensor(PointerSensor),
        useSensor(KeyboardSensor, {
            coordinateGetter: sortableKeyboardCoordinates,
        })
    );

    const handleInputChange = (field: keyof FormData, value: string | boolean | ProductVariant[]) => {
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
        handleInputChange('variants', updated);
    };

    const removeVariant = (index: number) => {
        const updated = localVariants.filter((_, i) => i !== index);
        setLocalVariants(updated);
        handleInputChange('variants', updated);
    };

    const updateVariant = (index: number, field: keyof Omit<ProductVariant, 'id'>, value: string) => {
        const updated = [...localVariants];
        updated[index] = { ...updated[index], [field]: value };
        setLocalVariants(updated);
        handleInputChange('variants', updated);
    };

    const handleDragEnd = (event: DragEndEvent) => {
        const { active, over } = event;

        if (over && active.id !== over.id) {
            setLocalVariants((items) => {
                const oldIndex = items.findIndex((item) => item.id === active.id);
                const newIndex = items.findIndex((item) => item.id === over.id);

                const newItems = arrayMove(items, oldIndex, newIndex);
                handleInputChange('variants', newItems);
                return newItems;
            });
        }
    };

    const handleVariantToggle = (checked: boolean) => {
        handleInputChange('has_variants', checked);
        if (checked && localVariants.length === 0) {
            addVariant();
        }
    };

    const toggleSection = (sectionId: number) => {
        setSelectedSections((prev) => (prev.includes(sectionId) ? prev.filter((id) => id !== sectionId) : [...prev, sectionId]));
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        setIsSubmitting(true);

        const submitData = {
            ...formData,
            sections: selectedSections,
            variants: localVariants.map((v) => ({
                ...(typeof v.id === 'number' ? { id: v.id } : {}),
                name: v.name,
                precio_pickup_capital: v.precio_pickup_capital,
                precio_domicilio_capital: v.precio_domicilio_capital,
                precio_pickup_interior: v.precio_pickup_interior,
                precio_domicilio_interior: v.precio_domicilio_interior,
            })),
        };

        router.put(`/menu/products/${product.id}`, submitData, {
            onSuccess: () => {
                // La redirección la maneja el controlador
            },
            onError: (errors) => {
                setErrors(errors as Record<string, string>);
                setIsSubmitting(false);
            },
        });
    };

    return (
        <EditPageLayout
            title="Editar Producto"
            description={`Modifica los datos del producto "${product.name}"`}
            backHref={route('menu.products.index')}
            backLabel="Volver"
            onSubmit={handleSubmit}
            submitLabel="Guardar"
            processing={isSubmitting}
            pageTitle={`Editar ${product.name}`}
            loading={false}
            loadingSkeleton={EditProductsSkeleton}
        >
            <FormSection icon={Package} title="Información Básica" description="Datos principales del producto">
                <FormField label="Categoría" error={errors.category_id} required>
                    <Select value={formData.category_id} onValueChange={(value) => handleInputChange('category_id', value)}>
                        <SelectTrigger>
                            <SelectValue />
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
                    <Input id="name" type="text" value={formData.name} onChange={(e) => handleInputChange('name', e.target.value)} />
                </FormField>

                <FormField label="Descripción" error={errors.description}>
                    <Textarea id="description" value={formData.description} onChange={(e) => handleInputChange('description', e.target.value)} rows={2} />
                </FormField>

                <div className="flex items-center space-x-2">
                    <Checkbox
                        id="is_active"
                        checked={formData.is_active}
                        onCheckedChange={(checked) => handleInputChange('is_active', checked as boolean)}
                    />
                    <Label htmlFor="is_active" className="text-sm leading-none font-medium cursor-pointer">
                        Producto activo
                    </Label>
                </div>

                <ImageUpload label="Imagen del Producto" currentImage={formData.image} onImageChange={(url) => handleInputChange('image', url || '')} error={errors.image} />
            </FormSection>

            <FormSection
                icon={Banknote}
                title="Precios y Variantes"
                description="Define si el producto usa variantes (ej: 15cm, 30cm) o tiene un precio único"
            >
                <div className="flex items-center space-x-2 mb-6">
                    <Checkbox
                        id="has_variants"
                        checked={formData.has_variants}
                        onCheckedChange={(checked) => handleVariantToggle(checked as boolean)}
                    />
                    <Label htmlFor="has_variants" className="text-sm leading-none font-medium cursor-pointer">
                        Producto con variantes
                    </Label>
                </div>

                {!formData.has_variants && (
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
                )}

                {formData.has_variants && (
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
        </EditPageLayout>
    );
}
