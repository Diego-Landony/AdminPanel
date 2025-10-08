import { router } from '@inertiajs/react';
import React, { useState } from 'react';
import { CURRENCY } from '@/constants/ui-constants';
import { Plus, Trash2, Store, Truck, Calendar } from 'lucide-react';

import { EditPageLayout } from '@/components/edit-page-layout';
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
import { EditPageSkeleton } from '@/components/skeletons';
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

interface PromotionItem {
    id: number;
    product_id: number | null;
    special_price_capital: number | null;
    special_price_interior: number | null;
    service_type: 'both' | 'delivery_only' | 'pickup_only' | null;
    validity_type: 'permanent' | 'date_range' | 'time_range' | 'date_time_range' | 'weekdays' | null;
    valid_from: string | null;
    valid_until: string | null;
    time_from: string | null;
    time_until: string | null;
    weekdays: number[] | null;
}

interface Promotion {
    id: number;
    name: string;
    description: string;
    type: string;
    is_active: boolean;
    items: PromotionItem[];
}

interface EditPromotionPageProps {
    promotion: Promotion;
    products: Product[];
    categories: any[];
}

interface LocalItem {
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

export default function EditPromotion({ promotion, products, categories }: EditPromotionPageProps) {
    const [formData, setFormData] = useState({
        is_active: promotion.is_active,
        name: promotion.name,
        description: promotion.description || '',
        type: promotion.type,
    });

    const [localItems, setLocalItems] = useState<LocalItem[]>(
        promotion.items.map((item) => {
            const hasDates = !!(item.valid_from || item.valid_until);
            const hasTimes = !!(item.time_from || item.time_until);

            return {
                id: generateUniqueId(),
                product_id: item.product_id,
                special_price_capital: String(item.special_price_capital || ''),
                special_price_interior: String(item.special_price_interior || ''),
                service_type: item.service_type || 'both',
                weekdays: item.weekdays || [],
                has_schedule: hasDates || hasTimes, // Detectar si ya tiene programación
                valid_from: item.valid_from || '',
                valid_until: item.valid_until || '',
                time_from: item.time_from || '',
                time_until: item.time_until || '',
            };
        })
    );

    const [errors, setErrors] = useState<Record<string, string>>({});
    const [processing, setProcessing] = useState(false);

    const addItem = () => {
        const newItem: LocalItem = {
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
        setLocalItems([...localItems, newItem]);
    };

    const removeItem = (index: number) => {
        setLocalItems(localItems.filter((_, i) => i !== index));
    };

    const updateItem = (index: number, field: keyof Omit<LocalItem, 'id'>, value: any) => {
        const updated = [...localItems];
        updated[index] = { ...updated[index], [field]: value };
        setLocalItems(updated);
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        setProcessing(true);

        const submitData = {
            ...formData,
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
        };

        router.put(route('menu.promotions.update', promotion.id), submitData, {
            onError: (errors) => {
                setErrors(errors);
                setProcessing(false);
            },
            onSuccess: () => {
                setProcessing(false);
            },
        });
    };

    return (
        <EditPageLayout
            title="Editar Promoción - Sub del Día"
            backHref={route('menu.promotions.daily-special.index')}
            backLabel="Volver"
            onSubmit={handleSubmit}
            submitLabel="Guardar"
            processing={processing}
            pageTitle={`Editar: ${promotion.name}`}
            loading={processing}
            loadingSkeleton={EditPageSkeleton}
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
                        checked={formData.is_active}
                        onCheckedChange={(checked) =>
                            setFormData({ ...formData, is_active: checked })
                        }
                    />
                </div>

                <FormField label="Nombre" error={errors.name} required>
                    <Input
                        type="text"
                        value={formData.name}
                        onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                        placeholder="Ej: Sub del Día Lunes - Sub Italiano"
                    />
                </FormField>

                <FormField label="Descripción" error={errors.description}>
                    <Textarea
                        value={formData.description}
                        onChange={(e) =>
                            setFormData({ ...formData, description: e.target.value })
                        }
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
                                products={products.filter(
                                    (product) =>
                                        !localItems.some(
                                            (i, idx) =>
                                                idx !== index && i.product_id === product.id,
                                        ),
                                )}
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

                                {/* Días de la semana - SIEMPRE VISIBLE Y REQUERIDO */}
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
        </EditPageLayout>
    );
}
