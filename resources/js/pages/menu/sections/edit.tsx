import { PLACEHOLDERS } from '@/constants/ui-constants';
import { closestCenter, DndContext, DragEndEvent, KeyboardSensor, PointerSensor, useSensor, useSensors } from '@dnd-kit/core';
import { arrayMove, SortableContext, sortableKeyboardCoordinates, useSortable, verticalListSortingStrategy } from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';
import { router } from '@inertiajs/react';
import { useState } from 'react';

import { EditPageLayout } from '@/components/edit-page-layout';
import { FormSection } from '@/components/form-section';
import { EditSectionsSkeleton } from '@/components/skeletons';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { FormField } from '@/components/ui/form-field';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { Textarea } from '@/components/ui/textarea';
import { ENTITY_ICONS } from '@/constants/section-icons';
import { generateUniqueId } from '@/utils/generateId';
import { Banknote, GripVertical, ListChecks, Plus, Trash2 } from 'lucide-react';

interface SectionOption {
    id: number | string;
    name: string;
    is_extra: boolean;
    price_modifier: string | number;
}

interface SortableItemProps {
    option: SectionOption;
    index: number;
    onUpdate: (index: number, field: keyof SectionOption, value: string | boolean | number) => void;
    onRemove: (index: number) => void;
}

function SortableItem({ option, index, onUpdate, onRemove }: SortableItemProps) {
    const { attributes, listeners, setNodeRef, transform, transition, isDragging } = useSortable({ id: option.id });

    const style = {
        transform: CSS.Transform.toString(transform),
        transition,
        opacity: isDragging ? 0.5 : 1,
    };

    return (
        <div ref={setNodeRef} style={style} className={`space-y-2 rounded-lg border p-3 ${isDragging ? 'bg-muted/50 shadow-lg' : ''}`}>
            <div className="flex items-center gap-2">
                <button
                    type="button"
                    className="cursor-grab text-muted-foreground transition-colors hover:text-foreground active:cursor-grabbing"
                    {...attributes}
                    {...listeners}
                >
                    <GripVertical className="h-5 w-5" />
                </button>
                <Input value={option.name} onChange={(e) => onUpdate(index, 'name', e.target.value)} className="flex-1" />
                <Button type="button" variant="ghost" size="icon" onClick={() => onRemove(index)}>
                    <Trash2 className="h-4 w-4 text-destructive" />
                </Button>
            </div>

            <div className="flex items-center gap-4">
                <div className="flex items-center space-x-2">
                    <Checkbox
                        id={`is_extra_${option.id}`}
                        checked={option.is_extra}
                        onCheckedChange={(checked) => onUpdate(index, 'is_extra', checked as boolean)}
                    />
                    <Label htmlFor={`is_extra_${option.id}`} className="cursor-pointer text-sm leading-none font-medium">
                        Tiene costo extra
                    </Label>
                </div>

                {option.is_extra && (
                    <div className="flex flex-1 items-center gap-1">
                        <Banknote className="h-4 w-4 text-muted-foreground" />
                        <Input
                            type="number"
                            step="0.01"
                            min="0"
                            value={option.price_modifier}
                            onChange={(e) => onUpdate(index, 'price_modifier', e.target.value)}
                            placeholder={PLACEHOLDERS.price}
                            className="w-24"
                        />
                    </div>
                )}
            </div>
        </div>
    );
}

interface Section {
    id: number;
    title: string;
    description: string | null;
    is_required: boolean;
    allow_multiple: boolean;
    min_selections: number;
    max_selections: number;
    is_active: boolean;
    options: Array<{
        id: number;
        name: string;
        is_extra: boolean;
        price_modifier: number;
        sort_order: number;
    }>;
}

interface EditPageProps {
    section: Section;
}

interface FormData {
    title: string;
    description: string;
    is_required: boolean;
    allow_multiple: boolean;
    min_selections: string | number;
    max_selections: string | number;
    is_active: boolean;
}

/**
 * Página para editar una sección
 */
export default function SectionEdit({ section }: EditPageProps) {
    const [formData, setFormData] = useState<FormData>({
        title: section.title,
        description: section.description || '',
        is_required: section.is_required,
        allow_multiple: section.allow_multiple,
        min_selections: section.min_selections,
        max_selections: section.max_selections,
        is_active: section.is_active,
    });

    const [errors, setErrors] = useState<Record<string, string>>({});
    const [isSubmitting, setIsSubmitting] = useState(false);

    const [localOptions, setLocalOptions] = useState<SectionOption[]>(
        section.options.map((opt) => ({
            id: opt.id,
            name: opt.name,
            is_extra: opt.is_extra,
            price_modifier: opt.price_modifier,
        })),
    );

    const sensors = useSensors(
        useSensor(PointerSensor),
        useSensor(KeyboardSensor, {
            coordinateGetter: sortableKeyboardCoordinates,
        }),
    );

    const handleInputChange = (field: keyof FormData, value: string | boolean | number) => {
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

    /**
     * Añade una nueva opción vacía
     */
    const addOption = () => {
        const newOption: SectionOption = {
            id: generateUniqueId(),
            name: '',
            is_extra: false,
            price_modifier: '0',
        };
        setLocalOptions([...localOptions, newOption]);
    };

    /**
     * Elimina una opción
     */
    const removeOption = (index: number) => {
        setLocalOptions(localOptions.filter((_, i) => i !== index));
    };

    /**
     * Actualiza una opción específica
     */
    const updateOption = (index: number, field: keyof SectionOption, value: string | boolean | number) => {
        const updatedOptions = [...localOptions];
        updatedOptions[index] = {
            ...updatedOptions[index],
            [field]: value,
        };

        // Si se desmarca is_extra, limpiar el price_modifier
        if (field === 'is_extra' && value === false) {
            updatedOptions[index].price_modifier = 0;
        }

        setLocalOptions(updatedOptions);
    };

    /**
     * Maneja el drag & drop
     */
    const handleDragEnd = (event: DragEndEvent) => {
        const { active, over } = event;

        if (over && active.id !== over.id) {
            setLocalOptions((items) => {
                const oldIndex = items.findIndex((item) => item.id === active.id);
                const newIndex = items.findIndex((item) => item.id === over.id);

                return arrayMove(items, oldIndex, newIndex);
            });
        }
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        setIsSubmitting(true);

        const submitData = {
            ...formData,
            min_selections: typeof formData.min_selections === 'string' ? parseInt(formData.min_selections) : formData.min_selections,
            max_selections: typeof formData.max_selections === 'string' ? parseInt(formData.max_selections) : formData.max_selections,
            options: localOptions.map((option) => ({
                name: option.name,
                is_extra: option.is_extra,
                // Si no es extra, el precio debe ser 0
                price_modifier: option.is_extra
                    ? (typeof option.price_modifier === 'string' ? parseFloat(option.price_modifier) : option.price_modifier)
                    : 0,
            })),
        };

        router.put(`/menu/sections/${section.id}`, submitData, {
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
            title="Editar Sección"
            description={`Modifica los datos de la sección "${section.title}"`}
            backHref={route('menu.sections.index')}
            backLabel="Volver"
            onSubmit={handleSubmit}
            submitLabel="Guardar"
            processing={isSubmitting}
            pageTitle={`Editar ${section.title}`}
            loading={false}
            loadingSkeleton={EditSectionsSkeleton}
        >
            <div className="space-y-8">
                <Card>
                    <CardContent className="pt-6">
                        <FormSection icon={ListChecks} title="Información Básica" description="Datos principales de la sección">
                            <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
                                <FormField label="Título" error={errors.title} required>
                                    <Input id="title" type="text" value={formData.title} onChange={(e) => handleInputChange('title', e.target.value)} />
                                </FormField>

                                <FormField label="Descripción" error={errors.description}>
                                    <Textarea
                                        id="description"
                                        value={formData.description}
                                        onChange={(e) => handleInputChange('description', e.target.value)}
                                        rows={2}
                                    />
                                </FormField>
                            </div>

                            <div className="flex items-center justify-between rounded-lg border p-4">
                                <Label htmlFor="is_active" className="cursor-pointer text-sm font-medium">
                                    Sección Activa
                                </Label>
                                <Switch
                                    id="is_active"
                                    checked={formData.is_active}
                                    onCheckedChange={(checked) => handleInputChange('is_active', checked)}
                                />
                            </div>

                            <div className="grid grid-cols-1 gap-4 rounded-lg border p-4 md:grid-cols-2">
                                <div className="flex items-center space-x-2">
                                    <Checkbox
                                        id="is_required"
                                        checked={formData.is_required}
                                        onCheckedChange={(checked) => handleInputChange('is_required', checked as boolean)}
                                    />
                                    <Label htmlFor="is_required" className="cursor-pointer text-sm leading-none font-medium">
                                        Obligatoria
                                    </Label>
                                </div>

                                <div className="flex items-center space-x-2">
                                    <Checkbox
                                        id="allow_multiple"
                                        checked={formData.allow_multiple}
                                        onCheckedChange={(checked) => handleInputChange('allow_multiple', checked as boolean)}
                                    />
                                    <Label htmlFor="allow_multiple" className="cursor-pointer text-sm leading-none font-medium">
                                        Selección múltiple
                                    </Label>
                                </div>
                            </div>

                            {formData.allow_multiple && (
                                <div className="grid grid-cols-1 gap-4 lg:grid-cols-2">
                                    <FormField label="Mínimo de items seleccionables" error={errors.min_selections}>
                                        <Input
                                            id="min_selections"
                                            type="number"
                                            min="0"
                                            value={formData.min_selections}
                                            onChange={(e) => handleInputChange('min_selections', e.target.value)}
                                        />
                                    </FormField>

                                    <FormField label="Máximo de items seleccionables" error={errors.max_selections}>
                                        <Input
                                            id="max_selections"
                                            type="number"
                                            min="1"
                                            value={formData.max_selections}
                                            onChange={(e) => handleInputChange('max_selections', e.target.value)}
                                        />
                                    </FormField>
                                </div>
                            )}
                        </FormSection>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent className="pt-6">
                        <FormSection icon={ENTITY_ICONS.menu.sectionOptions} title="Opciones" description="Define las opciones disponibles en esta sección">
                            <div className="space-y-3">
                                {localOptions.length === 0 ? (
                                    <p className="text-sm text-muted-foreground">Sin items aún</p>
                                ) : (
                                    <DndContext sensors={sensors} collisionDetection={closestCenter} onDragEnd={handleDragEnd}>
                                        <SortableContext items={localOptions.map((opt) => opt.id)} strategy={verticalListSortingStrategy}>
                                            <div className="space-y-3">
                                                {localOptions.map((option, index) => (
                                                    <SortableItem key={option.id} option={option} index={index} onUpdate={updateOption} onRemove={removeOption} />
                                                ))}
                                            </div>
                                        </SortableContext>
                                    </DndContext>
                                )}

                                <Button type="button" onClick={addOption} size="sm" variant="outline" className="w-full">
                                    <Plus className="mr-2 h-4 w-4" />
                                    Agregar Item
                                </Button>
                            </div>
                        </FormSection>
                    </CardContent>
                </Card>
            </div>
        </EditPageLayout>
    );
}
