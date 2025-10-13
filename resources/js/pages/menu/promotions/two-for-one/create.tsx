import { useForm } from '@inertiajs/react';
import React, { useState } from 'react';
import { PLACEHOLDERS } from '@/constants/ui-constants';
import { Plus, Trash2, Store, Truck } from 'lucide-react';

import { CreatePageLayout } from '@/components/create-page-layout';
import { FormSection } from '@/components/form-section';
import { FormField } from '@/components/ui/form-field';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Button } from '@/components/ui/button';
import { Switch } from '@/components/ui/switch';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { CategoryCombobox } from '@/components/CategoryCombobox';
import { CreatePageSkeleton } from '@/components/skeletons';
import { generateUniqueId } from '@/utils/generateId';

interface Category {
    id: number;
    name: string;
}

interface CreatePromotionPageProps {
    categories: Category[];
}

interface TwoForOneItem {
    id: string;
    category_id: number | null;
    service_type: 'both' | 'delivery_only' | 'pickup_only';
    validity_type: 'permanent' | 'date_range' | 'time_range' | 'date_time_range';
    valid_from: string;
    valid_until: string;
    time_from: string;
    time_until: string;
}

export default function CreateTwoForOnePromotion({ categories }: CreatePromotionPageProps) {
    const { data, setData, post, processing, errors } = useForm({
        is_active: true,
        name: '',
        description: '',
        type: 'two_for_one' as const,
        items: [] as TwoForOneItem[],
    });

    const [localItems, setLocalItems] = useState<TwoForOneItem[]>([
        {
            id: generateUniqueId(),
            category_id: null,
            service_type: 'both',
            validity_type: 'permanent',
            valid_from: '',
            valid_until: '',
            time_from: '',
            time_until: '',
        },
    ]);

    const addItem = () => {
        const newItem: TwoForOneItem = {
            id: generateUniqueId(),
            category_id: null,
            service_type: 'both',
            validity_type: 'permanent',
            valid_from: '',
            valid_until: '',
            time_from: '',
            time_until: '',
        };
        const updated = [...localItems, newItem];
        setLocalItems(updated);
        setData('items', updated.map(({ id: _id, ...rest }) => rest));
    };

    const removeItem = (index: number) => {
        const updated = localItems.filter((_, i) => i !== index);
        setLocalItems(updated);
        setData('items', updated.map(({ id: _id, ...rest }) => rest));
    };

    const updateItem = (
        index: number,
        field: keyof Omit<TwoForOneItem, 'id'>,
        value: string | number | null
    ) => {
        const updated = [...localItems];
        updated[index] = { ...updated[index], [field]: value };
        setLocalItems(updated);
        setData('items', updated.map(({ id: _id, ...rest }) => rest));
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        post(route('menu.promotions.store'), {
            transform: (data) => ({
                ...data,
                items: localItems.map(({ id: _id, ...rest }) => rest),
            }),
        });
    };

    return (
        <CreatePageLayout
            title="Nueva Promoción - 2x1"
            backHref={route('menu.promotions.two-for-one.index')}
            backLabel="Volver"
            onSubmit={handleSubmit}
            submitLabel="Guardar"
            processing={processing}
            pageTitle="Crear Promoción - 2x1"
            loading={processing}
            loadingSkeleton={CreatePageSkeleton}
        >
            {/* INFORMACIÓN BÁSICA */}
            <FormSection title="Información de la Promoción">
                {/* Switch Activo */}
                <div className="flex items-center justify-between rounded-lg border p-4">
                    <Label htmlFor="is_active" className="text-base">
                        Promoción activa
                    </Label>
                    <Switch
                        id="is_active"
                        checked={data.is_active}
                        onCheckedChange={(checked) => setData('is_active', checked)}
                    />
                </div>

                <FormField label="Nombre" error={errors.name} required>
                    <Input
                        type="text"
                        value={data.name}
                        onChange={(e) => setData('name', e.target.value)}

                    />
                </FormField>

                <FormField label="Descripción" error={errors.description}>
                    <Textarea
                        value={data.description}
                        onChange={(e) => setData('description', e.target.value)}

                        rows={2}
                    />
                </FormField>
            </FormSection>

            {/* CATEGORÍAS */}
            <FormSection title="Categorías">
                <div className="space-y-4">
                    {localItems.map((item, index) => (
                        <div
                            key={item.id}
                            className="border border-border rounded-lg p-4 space-y-4 relative"
                        >
                            {/* Header */}
                            <div className="flex items-center justify-between mb-2">
                                <h4 className="text-sm font-medium">Categoría {index + 1}</h4>
                                {localItems.length > 1 && (
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="sm"
                                        onClick={() => removeItem(index)}
                                    >
                                        <Trash2 className="h-4 w-4 text-destructive" />
                                    </Button>
                                )}
                            </div>

                            {/* Categoría */}
                            <CategoryCombobox
                                label="Categoría"
                                value={item.category_id}
                                onChange={(value) => updateItem(index, 'category_id', value)}
                                categories={categories.filter(
                                    (category) =>
                                        !localItems.some(
                                            (i, idx) =>
                                                idx !== index && i.category_id === category.id,
                                        ),
                                )}
                                placeholder={PLACEHOLDERS.selectCategory}
                                error={errors[`items.${index}.category_id`]}
                                required
                            />

                            {/* Tipo de servicio */}
                            <FormField
                                label="Tipo de Servicio"
                                error={errors[`items.${index}.service_type`]}
                                required
                            >
                                <Select
                                    value={item.service_type}
                                    onValueChange={(value: TwoForOneItem['service_type']) =>
                                        updateItem(index, 'service_type', value)
                                    }
                                >
                                    <SelectTrigger>
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="both">
                                            <div className="flex items-center gap-2">
                                                <div className="flex gap-1">
                                                    <Store className="h-4 w-4" />
                                                    <Truck className="h-4 w-4" />
                                                </div>
                                                Delivery y Pickup
                                            </div>
                                        </SelectItem>
                                        <SelectItem value="delivery_only">
                                            <div className="flex items-center gap-2">
                                                <Truck className="h-4 w-4" />
                                                Solo Delivery
                                            </div>
                                        </SelectItem>
                                        <SelectItem value="pickup_only">
                                            <div className="flex items-center gap-2">
                                                <Store className="h-4 w-4" />
                                                Solo Pickup
                                            </div>
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </FormField>

                            {/* Tipo de vigencia */}
                            <FormField
                                label="Vigencia"
                                error={errors[`items.${index}.validity_type`]}
                                required
                            >
                                <Select
                                    value={item.validity_type}
                                    onValueChange={(value: TwoForOneItem['validity_type']) =>
                                        updateItem(index, 'validity_type', value)
                                    }
                                >
                                    <SelectTrigger>
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="permanent">Permanente</SelectItem>
                                        <SelectItem value="date_range">Rango de Fechas</SelectItem>
                                        <SelectItem value="time_range">Rango de Horario</SelectItem>
                                        <SelectItem value="date_time_range">
                                            Fechas + Horario
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </FormField>

                            {/* Fechas (si aplica) */}
                            {(item.validity_type === 'date_range' ||
                                item.validity_type === 'date_time_range') && (
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <FormField
                                        label="Fecha de Inicio"
                                        error={errors[`items.${index}.valid_from`]}
                                        required
                                    >
                                        <Input
                                            type="date"
                                            value={item.valid_from}
                                            onChange={(e) =>
                                                updateItem(index, 'valid_from', e.target.value)
                                            }
                                        />
                                    </FormField>
                                    <FormField
                                        label="Fecha de Fin"
                                        error={errors[`items.${index}.valid_until`]}
                                        required
                                    >
                                        <Input
                                            type="date"
                                            value={item.valid_until}
                                            onChange={(e) =>
                                                updateItem(index, 'valid_until', e.target.value)
                                            }
                                        />
                                    </FormField>
                                </div>
                            )}

                            {/* Horarios (si aplica) */}
                            {(item.validity_type === 'time_range' ||
                                item.validity_type === 'date_time_range') && (
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <FormField
                                        label="Hora de Inicio"
                                        error={errors[`items.${index}.time_from`]}
                                        required
                                    >
                                        <Input
                                            type="time"
                                            value={item.time_from}
                                            onChange={(e) =>
                                                updateItem(index, 'time_from', e.target.value)
                                            }
                                        />
                                    </FormField>
                                    <FormField
                                        label="Hora de Fin"
                                        error={errors[`items.${index}.time_until`]}
                                        required
                                    >
                                        <Input
                                            type="time"
                                            value={item.time_until}
                                            onChange={(e) =>
                                                updateItem(index, 'time_until', e.target.value)
                                            }
                                        />
                                    </FormField>
                                </div>
                            )}
                        </div>
                    ))}

                    {/* Botón agregar */}
                    <Button
                        type="button"
                        variant="outline"
                        onClick={addItem}
                        className="w-full"
                    >
                        <Plus className="h-4 w-4 mr-2" />
                        Agregar Categoría
                    </Button>
                </div>
            </FormSection>
        </CreatePageLayout>
    );
}
