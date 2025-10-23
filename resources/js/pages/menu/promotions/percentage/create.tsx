import { useForm, router } from '@inertiajs/react';
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
import { Checkbox } from '@/components/ui/checkbox';
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

interface ProductVariant {
    id: number;
    name: string;
    size: string;
    precio_pickup_capital: number;
    precio_pickup_interior: number;
}

interface Product {
    id: number;
    name: string;
    category_id: number;
    has_variants: boolean;
    category?: {
        id: number;
        name: string;
    };
    variants?: ProductVariant[];
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
    product_id: string;
    variant_ids: string[]; // Ahora es un array de IDs de variantes
    discount_percentage: string;
    service_type: 'both' | 'delivery_only' | 'pickup_only';
    validity_type: 'permanent' | 'date_range' | 'time_range' | 'date_time_range';
    valid_from: string;
    valid_until: string;
    time_from: string;
    time_until: string;
}

export default function CreatePercentage({ products, categories }: CreatePromotionPageProps) {
    const { data, setData, processing, errors } = useForm({
        is_active: true,
        name: '',
        description: '',
        type: 'percentage_discount' as const,
        items: [] as PercentageItem[],
    });

    const [localItems, setLocalItems] = useState<PercentageItem[]>([
        {
            id: generateUniqueId(),
            product_id: '',
            variant_ids: [],
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
            product_id: '',
            variant_ids: [],
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

    const updateItem = (id: string, field: keyof PercentageItem, value: string | number | null) => {
        const updated = localItems.map((item) =>
            item.id === id ? { ...item, [field]: value } : item,
        );
        setLocalItems(updated);
        setData('items', updated);
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        interface SubmitItem {
            product_id: string;
            variant_id: string | null;
            discount_percentage: string;
            service_type: 'both' | 'delivery_only' | 'pickup_only';
            validity_type: 'permanent' | 'date_range' | 'time_range' | 'date_time_range';
            valid_from: string;
            valid_until: string;
            time_from: string;
            time_until: string;
        }

        // Expandir cada item con variant_ids a múltiples items (uno por variante)
        const expandedItems: SubmitItem[] = localItems.flatMap<SubmitItem>(({ id: _id, variant_ids, ...rest }) => {
            // Si tiene variantes seleccionadas, crear un item por cada variante
            if (variant_ids.length > 0) {
                return variant_ids.map<SubmitItem>(variant_id => ({
                    product_id: rest.product_id,
                    variant_id: variant_id,
                    discount_percentage: rest.discount_percentage,
                    service_type: rest.service_type,
                    validity_type: rest.validity_type,
                    valid_from: rest.valid_from,
                    valid_until: rest.valid_until,
                    time_from: rest.time_from,
                    time_until: rest.time_until,
                }));
            }

            // Si no tiene variantes (producto simple), crear un solo item
            return [{
                product_id: rest.product_id,
                variant_id: null,
                discount_percentage: rest.discount_percentage,
                service_type: rest.service_type,
                validity_type: rest.validity_type,
                valid_from: rest.valid_from,
                valid_until: rest.valid_until,
                time_from: rest.time_from,
                time_until: rest.time_until,
            }] as SubmitItem[];
        });

        router.post(
            route('menu.promotions.store'),
            {
                is_active: data.is_active,
                name: data.name,
                description: data.description,
                type: data.type,
                items: expandedItems,
                // eslint-disable-next-line @typescript-eslint/no-explicit-any
            } as any,
        );
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
            description="Crea una nueva promoción con descuento por porcentaje"
            backHref={route('menu.promotions.percentage.index')}
            backLabel="Volver"
            onSubmit={handleSubmit}
            submitLabel="Guardar"
            processing={processing}
            pageTitle="Crear Promoción - Porcentaje"
            loading={processing}
            loadingSkeleton={CreatePageSkeleton}
        >
            {/* Información Básica */}
            <FormSection title="Información Básica">
                <div className="space-y-4">
                    {/* Switch de Promoción Activa */}
                    <div className="flex items-center justify-between rounded-lg border border-border bg-card p-4">
                        <Label htmlFor="is-active" className="text-base">
                            Promoción activa
                        </Label>
                        <Switch
                            id="is-active"
                            checked={data.is_active}
                            onCheckedChange={(checked) => setData('is_active', checked)}
                        />
                    </div>

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
                                <ProductCombobox
                                    label="Producto"
                                    value={item.product_id ? Number(item.product_id) : null}
                                    onChange={(value) => {
                                        // Actualizar producto y resetear variantes seleccionadas
                                        const updated = localItems.map((i) =>
                                            i.id === item.id
                                                ? { ...i, product_id: value ? String(value) : '', variant_ids: [] }
                                                : i
                                        );
                                        setLocalItems(updated);
                                        setData('items', updated);
                                    }}
                                    products={products.filter(
                                        (product) => {
                                            // Si el producto NO tiene variantes, eliminar si ya está en uso
                                            if (!product.has_variants) {
                                                return !localItems.some(
                                                    (i) =>
                                                        i.id !== item.id &&
                                                        i.product_id === String(product.id),
                                                );
                                            }
                                            // Si el producto TIENE variantes, siempre permitir (se valida por variante)
                                            return true;
                                        }
                                    )}
                                    placeholder={PLACEHOLDERS.search}
                                    error={getItemError(index, 'product_id')}
                                    required
                                />

                                {/* Selector de Variantes con Checkboxes (solo si el producto tiene variantes) */}
                                {item.product_id && (() => {
                                    const selectedProduct = products.find(p => p.id === Number(item.product_id));
                                    const hasVariants = selectedProduct?.has_variants && selectedProduct?.variants && selectedProduct.variants.length > 0;

                                    if (!hasVariants) return null;

                                    return (
                                        <FormField
                                            label="Variantes"
                                            error={getItemError(index, 'variant_ids')}
                                            required
                                        >
                                            <div className="space-y-2 rounded-lg border border-border bg-card p-4">
                                                {selectedProduct.variants?.map((variant) => (
                                                    <div key={variant.id} className="flex items-center space-x-2">
                                                        <Checkbox
                                                            id={`variant-${item.id}-${variant.id}`}
                                                            checked={item.variant_ids.includes(String(variant.id))}
                                                            onCheckedChange={(checked) => {
                                                                const updated = localItems.map((i) => {
                                                                    if (i.id !== item.id) return i;

                                                                    const newVariantIds = checked
                                                                        ? [...i.variant_ids, String(variant.id)]
                                                                        : i.variant_ids.filter(vid => vid !== String(variant.id));

                                                                    return { ...i, variant_ids: newVariantIds };
                                                                });
                                                                setLocalItems(updated);
                                                                setData('items', updated);
                                                            }}
                                                        />
                                                        <label
                                                            htmlFor={`variant-${item.id}-${variant.id}`}
                                                            className="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70 cursor-pointer"
                                                        >
                                                            {variant.name} {variant.size && `- ${variant.size}`}
                                                        </label>
                                                    </div>
                                                ))}
                                            </div>
                                        </FormField>
                                    );
                                })()}

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
                                            placeholder={PLACEHOLDERS.percentage}
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
        </CreatePageLayout>
    );
}
