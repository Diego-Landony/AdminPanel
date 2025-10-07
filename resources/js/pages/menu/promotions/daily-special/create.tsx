import { useForm } from '@inertiajs/react';
import React, { useState } from 'react';
import { PLACEHOLDERS, CURRENCY } from '@/constants/ui-constants';
import { Plus, Trash2, Store, Truck, Calendar } from 'lucide-react';

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
import { ProductCombobox } from '@/components/ProductCombobox';
import { WeekdaySelector } from '@/components/WeekdaySelector';
import { CreatePageSkeleton } from '@/components/skeletons';
import { generateUniqueId } from '@/utils/generateId';

interface Product {
    id: number;
    name: string;
    category_id: number;
    category?: {
        id: number;
        name: string;
    };
}

interface Category {
    id: number;
    name: string;
}

interface CreatePromotionPageProps {
    products: Product[];
    categories: Category[];
}

interface DailySpecialItem {
    id: string;
    product_id: number | null;
    special_price_capital: string;
    special_price_interior: string;
    service_type: 'both' | 'delivery_only' | 'pickup_only';
    weekdays: number[]; // Siempre requerido
    has_schedule: boolean; // Indica si tiene programación adicional
    valid_from: string;
    valid_until: string;
    time_from: string;
    time_until: string;
}

export default function CreatePromotion({ products, categories }: CreatePromotionPageProps) {
    const { data, setData, post, processing, errors } = useForm({
        is_active: true,
        name: '',
        description: '',
        type: 'daily_special' as const,
        items: [] as DailySpecialItem[],
    });

    const [localItems, setLocalItems] = useState<DailySpecialItem[]>([
        {
            id: generateUniqueId(),
            product_id: null,
            special_price_capital: '',
            special_price_interior: '',
            service_type: 'both',
            weekdays: [],
            has_schedule: false,
            valid_from: '',
            valid_until: '',
            time_from: '',
            time_until: '',
        },
    ]);

    const addItem = () => {
        const newItem: DailySpecialItem = {
            id: generateUniqueId(),
            product_id: null,
            special_price_capital: '',
            special_price_interior: '',
            service_type: 'both',
            weekdays: [],
            has_schedule: false,
            valid_from: '',
            valid_until: '',
            time_from: '',
            time_until: '',
        };
        const updated = [...localItems, newItem];
        setLocalItems(updated);
        setData('items', updated.map(({ id, ...rest }) => rest));
    };

    const removeItem = (index: number) => {
        const updated = localItems.filter((_, i) => i !== index);
        setLocalItems(updated);
        setData('items', updated.map(({ id, ...rest }) => rest));
    };

    const updateItem = (
        index: number,
        field: keyof Omit<DailySpecialItem, 'id'>,
        value: any
    ) => {
        const updated = [...localItems];
        updated[index] = { ...updated[index], [field]: value };
        setLocalItems(updated);
        setData('items', updated.map(({ id, ...rest }) => rest));
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        // useForm().post() envía automáticamente el estado 'data'
        // Usamos transform para modificar los datos antes de enviar
        post(route('menu.promotions.store'), {
            transform: (data) => ({
                ...data,
                items: localItems.map(({ id, has_schedule, ...rest }) => {
                    // Calcular validity_type automáticamente basado en los campos
                    const hasDates = rest.valid_from || rest.valid_until;
                    const hasTimes = rest.time_from || rest.time_until;

                    let validity_type;
                    if (hasDates && hasTimes) {
                        validity_type = 'date_time_range';
                    } else if (hasDates) {
                        validity_type = 'date_range';
                    } else if (hasTimes) {
                        validity_type = 'time_range';
                    } else {
                        validity_type = 'weekdays';
                    }

                    return {
                        ...rest,
                        validity_type,
                        special_price_capital: parseFloat(rest.special_price_capital) || 0,
                        special_price_interior: parseFloat(rest.special_price_interior) || 0,
                    };
                }),
            }),
        });
    };

    return (
        <CreatePageLayout
            title="Nueva Promoción - Sub del Día"
            backHref={route('menu.promotions.daily-special.index')}
            backLabel="Volver"
            onSubmit={handleSubmit}
            submitLabel="Guardar"
            processing={processing}
            pageTitle="Crear Promoción - Sub del Día"
            loading={processing}
            loadingSkeleton={CreatePageSkeleton}
        >
            {/* INFORMACIÓN BÁSICA */}
            <FormSection title="Información de la Promoción">
                {/* Switch Activo */}
                <div className="flex items-center justify-between rounded-lg border p-4">
                    <div className="space-y-0.5">
                        <Label htmlFor="is_active" className="text-base">
                            Promoción activa
                        </Label>
                        <div className="text-sm text-muted-foreground">
                            Solo las promociones activas se aplicarán en el cálculo de precios
                        </div>
                    </div>
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
                        placeholder={PLACEHOLDERS.promotionName}
                    />
                </FormField>

                <FormField label="Descripción" error={errors.description}>
                    <Textarea
                        value={data.description}
                        onChange={(e) => setData('description', e.target.value)}
                        placeholder="Descripción opcional de la promoción"
                        rows={2}
                    />
                </FormField>
            </FormSection>

            {/* PRODUCTOS */}
            <FormSection title="Productos">
                <div className="space-y-4">
                    {localItems.map((item, index) => (
                        <div
                            key={item.id}
                            className="border border-border rounded-lg p-4 space-y-4 relative"
                        >
                            {/* Header */}
                            <div className="flex items-center justify-between mb-2">
                                <h4 className="text-sm font-medium">Producto {index + 1}</h4>
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

                            {/* Producto */}
                            <ProductCombobox
                                label="Producto"
                                value={item.product_id}
                                onChange={(value) => updateItem(index, 'product_id', value)}
                                products={products}
                                placeholder="Buscar producto..."
                                error={errors[`items.${index}.product_id`]}
                                required
                            />

                            {/* Precios */}
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <FormField
                                    label="Precio Capital"
                                    error={errors[`items.${index}.special_price_capital`]}
                                    required
                                >
                                    <div className="relative">
                                        <span className="absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground">
                                            {CURRENCY.symbol}
                                        </span>
                                        <Input
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            value={item.special_price_capital}
                                            onChange={(e) =>
                                                updateItem(
                                                    index,
                                                    'special_price_capital',
                                                    e.target.value
                                                )
                                            }
                                            className="pl-7"
                                            placeholder="0.00"
                                        />
                                    </div>
                                </FormField>

                                <FormField
                                    label="Precio Interior"
                                    error={errors[`items.${index}.special_price_interior`]}
                                    required
                                >
                                    <div className="relative">
                                        <span className="absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground">
                                            {CURRENCY.symbol}
                                        </span>
                                        <Input
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            value={item.special_price_interior}
                                            onChange={(e) =>
                                                updateItem(
                                                    index,
                                                    'special_price_interior',
                                                    e.target.value
                                                )
                                            }
                                            className="pl-7"
                                            placeholder="0.00"
                                        />
                                    </div>
                                </FormField>
                            </div>

                            {/* Tipo de Servicio */}
                            <FormField
                                label="Tipo de servicio"
                                error={errors[`items.${index}.service_type`]}
                                required
                            >
                                <Select
                                    value={item.service_type}
                                    onValueChange={(value: any) =>
                                        updateItem(index, 'service_type', value)
                                    }
                                >
                                    <SelectTrigger>
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="both">
                                            <div className="flex items-center gap-2">
                                                <Store className="h-4 w-4" />
                                                <Truck className="h-4 w-4" />
                                                <span>Delivery y Pickup</span>
                                            </div>
                                        </SelectItem>
                                        <SelectItem value="delivery_only">
                                            <div className="flex items-center gap-2">
                                                <Truck className="h-4 w-4" />
                                                <span>Solo Delivery</span>
                                            </div>
                                        </SelectItem>
                                        <SelectItem value="pickup_only">
                                            <div className="flex items-center gap-2">
                                                <Store className="h-4 w-4" />
                                                <span>Solo Pickup</span>
                                            </div>
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </FormField>

                            {/* VIGENCIA */}
                            <div className="space-y-4 rounded-lg border border-border p-4 bg-muted/30">
                                <div className="flex items-center gap-2">
                                    <Calendar className="h-4 w-4 text-muted-foreground" />
                                    <h5 className="text-sm font-medium">Vigencia</h5>
                                </div>

                                {/* Días de la semana */}
                                <WeekdaySelector
                                    value={item.weekdays}
                                    onChange={(days) => updateItem(index, 'weekdays', days)}
                                    error={errors[`items.${index}.weekdays`]}
                                    label="Días activos"
                                    required
                                />

                                {/* Checkbox: Restricciones */}
                                <div className="flex items-center space-x-2">
                                    <input
                                        type="checkbox"
                                        id={`has_schedule_${index}`}
                                        checked={item.has_schedule}
                                        onChange={(e) =>
                                            updateItem(index, 'has_schedule', e.target.checked)
                                        }
                                        className="h-4 w-4 rounded border-gray-300 text-primary focus:ring-primary"
                                    />
                                    <label
                                        htmlFor={`has_schedule_${index}`}
                                        className="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70 cursor-pointer"
                                    >
                                        Restringir por fechas u horarios
                                    </label>
                                </div>

                                {/* Campos condicionales */}
                                {item.has_schedule && (
                                    <div className="space-y-4 pl-6 border-l-2 border-primary/20">
                                        {/* Rango de fechas */}
                                        <div className="space-y-2">
                                            <p className="text-sm font-medium">Fechas</p>
                                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                <FormField
                                                    label="Desde"
                                                    error={errors[`items.${index}.valid_from`]}
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
                                                    label="Hasta"
                                                    error={errors[`items.${index}.valid_until`]}
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
                                        </div>

                                        {/* Horario */}
                                        <div className="space-y-2">
                                            <p className="text-sm font-medium">Horarios</p>
                                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                <FormField
                                                    label="Desde"
                                                    error={errors[`items.${index}.time_from`]}
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
                                                    label="Hasta"
                                                    error={errors[`items.${index}.time_until`]}
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
                                        </div>
                                    </div>
                                )}
                            </div>
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
                        Agregar otro producto
                    </Button>
                </div>
            </FormSection>
        </CreatePageLayout>
    );
}
