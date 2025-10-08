import { useForm } from '@inertiajs/react';
import React, { useState } from 'react';
import { PLACEHOLDERS } from '@/constants/ui-constants';
import { Plus, Trash2, Store, Truck, Percent } from 'lucide-react';

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

interface PercentageItem {
    id: string;
    product_id: number | null;
    discount_percentage: string;
    service_type: 'both' | 'delivery_only' | 'pickup_only';
    validity_type: 'permanent' | 'date_range' | 'time_range' | 'date_time_range';
    valid_from: string;
    valid_until: string;
    time_from: string;
    time_until: string;
}

export default function CreatePercentage({ products, categories }: CreatePromotionPageProps) {
    const { data, setData, post, processing, errors } = useForm({
        is_active: true,
        name: '',
        description: '',
        type: 'percentage_discount' as const,
        items: [] as PercentageItem[],
    });

    const [localItems, setLocalItems] = useState<PercentageItem[]>([
        {
            id: generateUniqueId(),
            product_id: null,
            discount_percentage: '',
            service_type: 'both',
            validity_type: 'permanent',
            valid_from: '',
            valid_until: '',
            time_from: '',
            time_until: '',
        },
    ]);

    const addItem = () => {
        const newItem: PercentageItem = {
            id: generateUniqueId(),
            product_id: null,
            discount_percentage: '',
            service_type: 'both',
            validity_type: 'permanent',
            valid_from: '',
            valid_until: '',
            time_from: '',
            time_until: '',
        };
        const updated = [...localItems, newItem];
        setLocalItems(updated);
        setData('items', updated);
    };

    const removeItem = (id: string) => {
        const updated = localItems.filter((item) => item.id !== id);
        setLocalItems(updated);
        setData('items', updated);
    };

    const updateItem = (id: string, field: keyof PercentageItem, value: any) => {
        const updated = localItems.map((item) =>
            item.id === id ? { ...item, [field]: value } : item,
        );
        setLocalItems(updated);
        setData('items', updated);
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        post(route('menu.promotions.store'), {
            transform: (data) => ({
                ...data,
                items: localItems.map(({ id, ...rest }) => rest),
            }),
        });
    };

    const getItemError = (index: number, field: string) => {
        const error = errors[`items.${index}.${field}` as keyof typeof errors];
        return error as string | undefined;
    };

    if (!products || !categories) {
        return <CreatePageSkeleton />;
    }

    return (
        <CreatePageLayout
            title="Crear Promoción de Porcentaje"
            backHref={route('menu.promotions.percentage.index')}
            backLabel="Volver"
            onSubmit={handleSubmit}
            submitLabel="Guardar"
            processing={processing}
        >
            {/* Información Básica */}
            <FormSection title="Información Básica">
                <div className="space-y-4">
                    <FormField label="Nombre" required error={errors.name}>
                        <Input
                            value={data.name}
                            onChange={(e) => setData('name', e.target.value)}
                            placeholder={PLACEHOLDERS.name}
                            required
                        />
                    </FormField>

                    <FormField label="Descripción" error={errors.description}>
                        <Textarea
                            value={data.description || ''}
                            onChange={(e) => setData('description', e.target.value)}
                            placeholder={PLACEHOLDERS.description}
                            rows={3}
                        />
                    </FormField>
                </div>
            </FormSection>

            {/* Items de la Promoción */}
            <FormSection title="Productos con Descuento">
                <div className="space-y-6">
                    {localItems.map((item, index) => (
                        <div
                            key={item.id}
                            className="relative rounded-lg border border-border bg-card p-6"
                        >
                            {/* Botón Eliminar */}
                            {localItems.length > 1 && (
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="sm"
                                    onClick={() => removeItem(item.id)}
                                    className="absolute right-2 top-2"
                                >
                                    <Trash2 className="h-4 w-4" />
                                </Button>
                            )}

                            <div className="space-y-4">
                                <h4 className="font-medium">Producto {index + 1}</h4>

                                {/* Selector de Producto */}
                                <FormField
                                    label="Producto"
                                    required
                                    error={getItemError(index, 'product_id')}
                                >
                                    <ProductCombobox
                                        products={products.filter(
                                            (product) =>
                                                !localItems.some(
                                                    (i) =>
                                                        i.id !== item.id &&
                                                        i.product_id === product.id,
                                                ),
                                        )}
                                        value={item.product_id}
                                        onChange={(value) =>
                                            updateItem(item.id, 'product_id', value)
                                        }
                                        placeholder={PLACEHOLDERS.productSearch}
                                    />
                                </FormField>

                                {/* Porcentaje de Descuento */}
                                <FormField
                                    label="Porcentaje"
                                    required
                                    error={getItemError(index, 'discount_percentage')}
                                >
                                    <div className="relative">
                                        <Input
                                            type="number"
                                            min="1"
                                            max="100"
                                            step="0.01"
                                            value={item.discount_percentage}
                                            onChange={(e) =>
                                                updateItem(
                                                    item.id,
                                                    'discount_percentage',
                                                    e.target.value,
                                                )
                                            }
                                            placeholder="15"
                                            className="pr-8"
                                            required
                                        />
                                        <Percent className="absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                                    </div>
                                </FormField>

                                {/* Tipo de Servicio */}
                                <FormField
                                    label="Tipo de servicio"
                                    required
                                    error={getItemError(index, 'service_type')}
                                >
                                    <Select
                                        value={item.service_type}
                                        onValueChange={(value) =>
                                            updateItem(item.id, 'service_type', value)
                                        }
                                    >
                                        <SelectTrigger>
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="both">
                                                <div className="flex items-center gap-2">
                                                    <Truck className="h-4 w-4" />
                                                    <Store className="h-4 w-4" />
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

                                {/* Tipo de Vigencia */}
                                <FormField
                                    label="Vigencia"
                                    required
                                    error={getItemError(index, 'validity_type')}
                                >
                                    <Select
                                        value={item.validity_type}
                                        onValueChange={(value) =>
                                            updateItem(item.id, 'validity_type', value)
                                        }
                                    >
                                        <SelectTrigger>
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="permanent">Permanente</SelectItem>
                                            <SelectItem value="date_range">
                                                Rango de Fechas
                                            </SelectItem>
                                            <SelectItem value="time_range">Rango de Horario</SelectItem>
                                            <SelectItem value="date_time_range">
                                                Fechas + Horario
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                </FormField>

                                {/* Campos condicionales según validity_type */}
                                {(item.validity_type === 'date_range' ||
                                    item.validity_type === 'date_time_range') && (
                                    <div className="grid gap-4 sm:grid-cols-2">
                                        <FormField
                                            label="Fecha Inicio"
                                            required
                                            error={getItemError(index, 'valid_from')}
                                        >
                                            <Input
                                                type="date"
                                                value={item.valid_from}
                                                onChange={(e) =>
                                                    updateItem(
                                                        item.id,
                                                        'valid_from',
                                                        e.target.value,
                                                    )
                                                }
                                                required
                                            />
                                        </FormField>

                                        <FormField
                                            label="Fecha Fin"
                                            required
                                            error={getItemError(index, 'valid_until')}
                                        >
                                            <Input
                                                type="date"
                                                value={item.valid_until}
                                                onChange={(e) =>
                                                    updateItem(
                                                        item.id,
                                                        'valid_until',
                                                        e.target.value,
                                                    )
                                                }
                                                required
                                            />
                                        </FormField>
                                    </div>
                                )}

                                {(item.validity_type === 'time_range' ||
                                    item.validity_type === 'date_time_range') && (
                                    <div className="grid gap-4 sm:grid-cols-2">
                                        <FormField
                                            label="Hora Inicio"
                                            required
                                            error={getItemError(index, 'time_from')}
                                        >
                                            <Input
                                                type="time"
                                                value={item.time_from}
                                                onChange={(e) =>
                                                    updateItem(
                                                        item.id,
                                                        'time_from',
                                                        e.target.value,
                                                    )
                                                }
                                                required
                                            />
                                        </FormField>

                                        <FormField
                                            label="Hora Fin"
                                            required
                                            error={getItemError(index, 'time_until')}
                                        >
                                            <Input
                                                type="time"
                                                value={item.time_until}
                                                onChange={(e) =>
                                                    updateItem(
                                                        item.id,
                                                        'time_until',
                                                        e.target.value,
                                                    )
                                                }
                                                required
                                            />
                                        </FormField>
                                    </div>
                                )}
                            </div>
                        </div>
                    ))}

                    <Button
                        type="button"
                        variant="outline"
                        onClick={addItem}
                        className="w-full"
                    >
                        <Plus className="mr-2 h-4 w-4" />
                        Agregar producto
                    </Button>
                </div>
            </FormSection>

            {/* Estado */}
            <FormSection title="Estado">
                <div className="flex items-center justify-between rounded-lg border p-4">
                    <div className="space-y-0.5">
                        <Label htmlFor="is-active" className="text-base">
                            Promoción activa
                        </Label>
                        <div className="text-sm text-muted-foreground">
                            Solo las promociones activas se aplicarán en el cálculo de precios
                        </div>
                    </div>
                    <Switch
                        id="is-active"
                        checked={data.is_active}
                        onCheckedChange={(checked) => setData('is_active', checked)}
                    />
                </div>
            </FormSection>
        </CreatePageLayout>
    );
}
