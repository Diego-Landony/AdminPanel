import { PLACEHOLDERS } from '@/constants/ui-constants';
import { showNotification } from '@/hooks/useNotifications';
import { closestCenter, DndContext, DragEndEvent, KeyboardSensor, PointerSensor, useSensor, useSensors } from '@dnd-kit/core';
import { arrayMove, SortableContext, sortableKeyboardCoordinates, useSortable, verticalListSortingStrategy } from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';
import { useForm } from '@inertiajs/react';
import React, { useState } from 'react';

import { CreatePageLayout } from '@/components/create-page-layout';
import { FormSection } from '@/components/form-section';
import { CreateSectionsSkeleton } from '@/components/skeletons';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { FormField } from '@/components/ui/form-field';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { Textarea } from '@/components/ui/textarea';
import { ENTITY_ICONS } from '@/constants/section-icons';
import { NOTIFICATIONS } from '@/constants/ui-constants';
import { generateUniqueId } from '@/utils/generateId';
import { Banknote, GripVertical, ListChecks, Percent, Plus, Trash2 } from 'lucide-react';

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
        min_selections: '',
        max_selections: '',
        bundle_discount_enabled: false,
        bundle_size: '',
        bundle_discount_amount: '',
        is_active: true,
        options: [] as SectionOption[],
    });

    const [localOptions, setLocalOptions] = useState<SectionOption[]>([]);

    const sensors = useSensors(
        useSensor(PointerSensor),
        useSensor(KeyboardSensor, {
            coordinateGetter: sortableKeyboardCoordinates,
        }),
    );

    /**
     * Añade una nueva opción vacía
     */
    const addOption = () => {
        const newOption: SectionOption = {
            id: generateUniqueId(),
            name: '',
            is_extra: false,
            price_modifier: '',
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

        // Si se desmarca is_extra, limpiar el price_modifier
        if (field === 'is_extra' && value === false) {
            updatedOptions[index].price_modifier = 0;
        }

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

        // Actualizar las opciones en el formulario antes de enviar
        data.options = localOptions.map((option) => ({
            name: option.name,
            is_extra: option.is_extra,
            // Si no es extra, el precio debe ser 0
            price_modifier: option.is_extra
                ? (typeof option.price_modifier === 'string' ? parseFloat(option.price_modifier) : option.price_modifier)
                : 0,
        })) as unknown as typeof data.options;

        // Convertir bundle_size a número o null
        data.bundle_size = data.bundle_size
            ? (typeof data.bundle_size === 'string' ? parseInt(data.bundle_size) : data.bundle_size)
            : (null as unknown as string);

        // Convertir bundle_discount_amount a número o null
        data.bundle_discount_amount = data.bundle_discount_amount
            ? (typeof data.bundle_discount_amount === 'string' ? parseFloat(data.bundle_discount_amount) : data.bundle_discount_amount)
            : (null as unknown as string);

        post(route('menu.sections.store'), {
            onSuccess: () => {
                reset();
                setLocalOptions([]);
            },
            onError: (errors: Record<string, string>) => {
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
            <div className="space-y-8">
                {/* Información Básica */}
                <Card>
                    <CardContent className="pt-6">
                        <FormSection icon={ListChecks} title="Información Básica" description="Datos principales de la sección">
                            <div className="space-y-6">
                                <FormField label="Título" error={errors.title} required>
                                    <Input id="title" type="text" value={data.title} onChange={(e) => setData('title', e.target.value)} />
                                </FormField>

                                <FormField label="Descripción en app" error={errors.description}>
                                    <Textarea id="description" value={data.description} onChange={(e) => setData('description', e.target.value)} rows={2} />
                                </FormField>

                                <div className="flex items-center justify-between rounded-lg border p-4">
                                    <Label htmlFor="is_active" className="cursor-pointer text-sm font-medium">
                                        Sección Activa
                                    </Label>
                                    <Switch id="is_active" checked={data.is_active} onCheckedChange={(checked) => setData('is_active', checked)} />
                                </div>

                                <div className="grid grid-cols-1 gap-4 rounded-lg border p-4 md:grid-cols-2">
                                    <div className="flex items-center space-x-2">
                                        <Checkbox
                                            id="is_required"
                                            checked={data.is_required}
                                            onCheckedChange={(checked) => setData('is_required', checked as boolean)}
                                        />
                                        <Label htmlFor="is_required" className="cursor-pointer text-sm font-medium leading-none">
                                            Obligatoria
                                        </Label>
                                    </div>

                                    <div className="flex items-center space-x-2">
                                        <Checkbox
                                            id="allow_multiple"
                                            checked={data.allow_multiple}
                                            onCheckedChange={(checked) => setData('allow_multiple', checked as boolean)}
                                        />
                                        <Label htmlFor="allow_multiple" className="cursor-pointer text-sm font-medium leading-none">
                                            Selección múltiple
                                        </Label>
                                    </div>
                                </div>

                                {data.allow_multiple && (
                                    <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
                                        <FormField label="Mínimo de items seleccionables" error={errors.min_selections}>
                                            <Input
                                                id="min_selections"
                                                type="number"
                                                min="0"
                                                value={data.min_selections}
                                                onChange={(e) => setData('min_selections', e.target.value)}
                                            />
                                        </FormField>

                                        <FormField label="Máximo de items seleccionables" error={errors.max_selections}>
                                            <Input
                                                id="max_selections"
                                                type="number"
                                                min="1"
                                                value={data.max_selections}
                                                onChange={(e) => setData('max_selections', e.target.value)}
                                            />
                                        </FormField>
                                    </div>
                                )}
                            </div>
                        </FormSection>
                    </CardContent>
                </Card>

                {/* Opciones */}
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

                {/* Bundle Pricing - Solo si hay opciones con is_extra */}
                {localOptions.some((opt) => opt.is_extra) && (
                    <Card>
                        <CardContent className="pt-6">
                            <FormSection icon={Percent} title="Descuento por Cantidad" description="Descuento cuando se seleccionan múltiples extras del mismo precio">
                                <div className="space-y-4">
                                    <div className="flex items-center space-x-2">
                                        <Checkbox
                                            id="bundle_discount_enabled"
                                            checked={data.bundle_discount_enabled}
                                            onCheckedChange={(checked) => setData('bundle_discount_enabled', checked as boolean)}
                                        />
                                        <Label htmlFor="bundle_discount_enabled" className="cursor-pointer text-sm font-medium leading-none">
                                            Habilitar descuento por cantidad
                                        </Label>
                                    </div>

                                    {data.bundle_discount_enabled && (
                                        <div className="grid grid-cols-1 gap-4 rounded-lg border p-4 md:grid-cols-2">
                                            <FormField label="Items necesarios para descuento" error={errors.bundle_size}>
                                                <Input
                                                    id="bundle_size"
                                                    type="number"
                                                    min="2"
                                                    max="10"
                                                    value={data.bundle_size}
                                                    onChange={(e) => setData('bundle_size', e.target.value ? parseInt(e.target.value) : '')}
                                                />
                                            </FormField>

                                            <FormField label={`Descuento (Q)`} error={errors.bundle_discount_amount}>
                                                <Input
                                                    id="bundle_discount_amount"
                                                    type="number"
                                                    step="0.01"
                                                    min="0"
                                                    value={data.bundle_discount_amount}
                                                    onChange={(e) => setData('bundle_discount_amount', e.target.value)}
                                                />
                                            </FormField>
                                        </div>
                                    )}
                                </div>
                            </FormSection>
                        </CardContent>
                    </Card>
                )}
            </div>
        </CreatePageLayout>
    );
}
