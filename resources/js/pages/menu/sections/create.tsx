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
import { CreateSectionsSkeleton } from '@/components/skeletons';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { FormField } from '@/components/ui/form-field';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { ENTITY_ICONS } from '@/constants/section-icons';
import { NOTIFICATIONS } from '@/constants/ui-constants';
import { generateUniqueId } from '@/utils/generateId';
import { Banknote, GripVertical, ListChecks, Plus, Trash2 } from 'lucide-react';

interface SectionOption {
    id: string;
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
    const {
        attributes,
        listeners,
        setNodeRef,
        transform,
        transition,
        isDragging,
    } = useSortable({ id: option.id });

    const style = {
        transform: CSS.Transform.toString(transform),
        transition,
        opacity: isDragging ? 0.5 : 1,
    };

    return (
        <div
            ref={setNodeRef}
            style={style}
            className={`p-3 border rounded-lg space-y-2 ${isDragging ? 'shadow-lg bg-muted/50' : ''}`}
        >
            <div className="flex items-center gap-2">
                <button
                    type="button"
                    className="cursor-grab active:cursor-grabbing text-muted-foreground hover:text-foreground transition-colors"
                    {...attributes}
                    {...listeners}
                >
                    <GripVertical className="h-5 w-5" />
                </button>
                <Input
                    value={option.name}
                    onChange={(e) => onUpdate(index, 'name', e.target.value)}
                    placeholder={PLACEHOLDERS.sectionOptionName}
                    className="flex-1"
                />
                <Button type="button" variant="ghost" size="icon" onClick={() => onRemove(index)}>
                    <Trash2 className="h-4 w-4 text-destructive" />
                </Button>
            </div>

            <div className="flex items-center gap-4">
                <div className="flex items-center space-x-2">
                    <Checkbox id={`is_extra_${option.id}`} checked={option.is_extra} onCheckedChange={(checked) => onUpdate(index, 'is_extra', checked as boolean)} />
                    <Label htmlFor={`is_extra_${option.id}`} className="text-sm leading-none font-medium cursor-pointer">
                        Tiene costo extra
                    </Label>
                </div>

                {option.is_extra && (
                    <div className="flex items-center gap-1 flex-1">
                        <Banknote className="h-4 w-4 text-muted-foreground" />
                        <Input
                            type="number"
                            step="0.01"
                            min="0"
                            value={option.price_modifier}
                            onChange={(e) => onUpdate(index, 'price_modifier', e.target.value)}
                            placeholder={PLACEHOLDERS.sectionOptionPrice}
                            className="w-24"
                        />
                    </div>
                )}
            </div>
        </div>
    );
}

/**
 * Página para crear una sección de menú
 *
 * Las opciones solo tienen precio si son marcadas como "is_extra" (ej: aguacate, champiñones)
 */
export default function SectionCreate() {
    const { data, setData, post, processing, errors, reset } = useForm({
        title: '',
        description: '',
        is_required: false,
        allow_multiple: false,
        min_selections: '1',
        max_selections: '1',
        is_active: true,
        options: [] as SectionOption[],
    });

    const [localOptions, setLocalOptions] = useState<SectionOption[]>([]);

    const sensors = useSensors(
        useSensor(PointerSensor),
        useSensor(KeyboardSensor, {
            coordinateGetter: sortableKeyboardCoordinates,
        })
    );

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
        const updatedOptions = [...localOptions, newOption];
        setLocalOptions(updatedOptions);
        setData('options', updatedOptions);
    };

    /**
     * Elimina una opción
     */
    const removeOption = (index: number) => {
        const updatedOptions = localOptions.filter((_, i) => i !== index);
        setLocalOptions(updatedOptions);
        setData('options', updatedOptions);
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
        setLocalOptions(updatedOptions);
        setData('options', updatedOptions);
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

                const newItems = arrayMove(items, oldIndex, newIndex);
                setData('options', newItems);
                return newItems;
            });
        }
    };

    /**
     * Maneja el envío del formulario
     */
    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        // Convertir valores string a números antes de enviar
        const submitData = {
            ...data,
            min_selections: typeof data.min_selections === 'string' ? parseInt(data.min_selections) : data.min_selections,
            max_selections: typeof data.max_selections === 'string' ? parseInt(data.max_selections) : data.max_selections,
            options: localOptions.map((option) => ({
                name: option.name,
                is_extra: option.is_extra,
                price_modifier: typeof option.price_modifier === 'string' ? parseFloat(option.price_modifier) : option.price_modifier,
            })),
        };

        post(route('menu.sections.store'), {
            data: submitData,
            onSuccess: () => {
                reset();
                setLocalOptions([]);
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
            title="Nueva Sección"
            backHref={route('menu.sections.index')}
            backLabel="Volver"
            onSubmit={handleSubmit}
            submitLabel="Guardar"
            processing={processing}
            pageTitle="Crear Sección"
            loading={processing}
            loadingSkeleton={CreateSectionsSkeleton}
        >
            <FormSection icon={ListChecks} title="Información Básica" description="Datos principales de la sección">
                {/* Título */}
                <FormField label="Título" error={errors.title} required>
                    <Input id="title" type="text" value={data.title} onChange={(e) => setData('title', e.target.value)} placeholder={PLACEHOLDERS.sectionTitle} />
                </FormField>

                {/* Descripción */}
                <FormField label="Descripción" error={errors.description}>
                    <Textarea id="description" value={data.description} onChange={(e) => setData('description', e.target.value)} placeholder={PLACEHOLDERS.sectionDescription} rows={2} />
                </FormField>

                <div className="space-y-3">
                    {/* Es requerida */}
                    <div className="flex items-center space-x-2">
                        <Checkbox id="is_required" checked={data.is_required} onCheckedChange={(checked) => setData('is_required', checked as boolean)} />
                        <Label htmlFor="is_required" className="text-sm leading-none font-medium cursor-pointer">
                            Obligatoria
                        </Label>
                    </div>

                    {/* Permite múltiples selecciones */}
                    <div className="flex items-center space-x-2">
                        <Checkbox id="allow_multiple" checked={data.allow_multiple} onCheckedChange={(checked) => setData('allow_multiple', checked as boolean)} />
                        <Label htmlFor="allow_multiple" className="text-sm leading-none font-medium cursor-pointer">
                            Selección múltiple
                        </Label>
                    </div>

                    {/* Sección activa */}
                    <div className="flex items-center space-x-2">
                        <Checkbox id="is_active" checked={data.is_active} onCheckedChange={(checked) => setData('is_active', checked as boolean)} />
                        <Label htmlFor="is_active" className="text-sm leading-none font-medium cursor-pointer">
                            Sección activa
                        </Label>
                    </div>
                </div>

                {/* Selecciones mínimas/máximas - solo si allow_multiple */}
                {data.allow_multiple && (
                    <div className="grid grid-cols-2 gap-4">
                        <FormField label="Mínimo de items seleccionables" error={errors.min_selections}>
                            <Input id="min_selections" type="number" min="1" value={data.min_selections} onChange={(e) => setData('min_selections', e.target.value)} />
                        </FormField>

                        <FormField label="Máximo de items seleccionables" error={errors.max_selections}>
                            <Input id="max_selections" type="number" min="1" value={data.max_selections} onChange={(e) => setData('max_selections', e.target.value)} />
                        </FormField>
                    </div>
                )}
            </FormSection>

            <FormSection icon={ENTITY_ICONS.menu.sectionOptions} title="Opciones" description="Define las opciones disponibles en esta sección">
                <div className="space-y-3">
                    {localOptions.length === 0 ? (
                        <p className="text-sm text-muted-foreground">Sin items aún</p>
                    ) : (
                        <DndContext sensors={sensors} collisionDetection={closestCenter} onDragEnd={handleDragEnd}>
                            <SortableContext items={localOptions.map((opt) => opt.id)} strategy={verticalListSortingStrategy}>
                                <div className="space-y-3">
                                    {localOptions.map((option, index) => (
                                        <SortableItem
                                            key={option.id}
                                            option={option}
                                            index={index}
                                            onUpdate={updateOption}
                                            onRemove={removeOption}
                                        />
                                    ))}
                                </div>
                            </SortableContext>
                        </DndContext>
                    )}

                    <Button type="button" onClick={addOption} size="sm" variant="outline" className="w-full">
                        <Plus className="h-4 w-4 mr-2" />
                        Agregar Item
                    </Button>
                </div>
            </FormSection>
        </CreatePageLayout>
    );
}
