import { router } from '@inertiajs/react';
import React, { useState } from 'react';
import { PLACEHOLDERS } from '@/constants/ui-constants';
import { Plus, Trash2, Store, Truck } from 'lucide-react';
import { route } from 'ziggy-js';

import { EditPageLayout } from '@/components/edit-page-layout';
import { FormSection } from '@/components/form-section';
import { FormField } from '@/components/ui/form-field';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Button } from '@/components/ui/button';
import { Switch } from '@/components/ui/switch';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { ProductCombobox } from '@/components/ProductCombobox';
import { EditPageSkeleton } from '@/components/skeletons';
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

interface PromotionItem {
    id: number;
    product_id: number | null;
    variant_id: number | null;
    discount_percentage: string | null;
    service_type: 'both' | 'delivery_only' | 'pickup_only' | null;
    validity_type: 'permanent' | 'date_range' | 'time_range' | 'date_time_range' | null;
    valid_from: string | null;
    valid_until: string | null;
    time_from: string | null;
    time_until: string | null;
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
}

interface LocalItem {
    id: string;
    product_id: number | null;
    variant_ids: number[]; // Changed from variant_id to support multiple variants
    discount_percentage: string;
    service_type: 'both' | 'delivery_only' | 'pickup_only';
    validity_type: 'permanent' | 'date_range' | 'time_range' | 'date_time_range';
    valid_from: string;
    valid_until: string;
    time_from: string;
    time_until: string;
}

export default function EditPercentagePromotion({ promotion, products }: EditPromotionPageProps) {
    const [formData, setFormData] = useState({
        is_active: promotion.is_active,
        name: promotion.name,
        description: promotion.description || '',
        type: promotion.type,
    });

    const [localItems, setLocalItems] = useState<LocalItem[]>(
        promotion.items.map((item) => ({
            id: generateUniqueId(),
            product_id: item.product_id,
            variant_ids: item.variant_id ? [item.variant_id] : [], // Convert single variant to array
            discount_percentage: item.discount_percentage || '',
            service_type: item.service_type || 'both',
            validity_type: item.validity_type || 'permanent',
            valid_from: item.valid_from || '',
            valid_until: item.valid_until || '',
            time_from: item.time_from || '',
            time_until: item.time_until || '',
        })),
    );

    const [errors, setErrors] = useState<Record<string, string>>({});
    const [processing, setProcessing] = useState(false);

    const addItem = () => {
        const newItem: LocalItem = {
            id: generateUniqueId(),
            product_id: null,
            variant_ids: [], // Changed from variant_id to variant_ids
            discount_percentage: '',
            service_type: 'both',
            validity_type: 'permanent',
            valid_from: '',
            valid_until: '',
            time_from: '',
            time_until: '',
        };
        setLocalItems([...localItems, newItem]);
    };

    const removeItem = (id: string) => {
        setLocalItems(localItems.filter((item) => item.id !== id));
    };

    const updateItem = (id: string, field: keyof LocalItem, value: string | number | number[] | null) => {
        setLocalItems(
            localItems.map((item) => (item.id === id ? { ...item, [field]: value } : item)),
        );
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        setProcessing(true);

        // Expandir cada item con variant_ids a múltiples items (uno por variante)
        const expandedItems = localItems.flatMap(({ id: _id, variant_ids, ...rest }: LocalItem) => {
            // Si tiene variantes seleccionadas, crear un item por cada variante
            if (variant_ids.length > 0) {
                return variant_ids.map((variant_id) => ({
                    ...rest,
                    variant_id: variant_id,
                }));
            }

            // Si no tiene variantes (producto simple), crear un solo item
            return [
                {
                    ...rest,
                    variant_id: null,
                },
            ];
        });

        router.put(
            route('menu.promotions.update', promotion.id),
            {
                ...formData,
                items: expandedItems,
            },
            {
                onError: (errors) => {
                    setErrors(errors);
                    setProcessing(false);
                },
                onSuccess: () => {
                    setProcessing(false);
                },
            },
        );
    };

    const getItemError = (index: number, field: string) => {
        return errors[`items.${index}.${field}`];
    };

    if (!products) {
        return <EditPageSkeleton />;
    }

    return (
        <EditPageLayout
            title="Editar Promoción de Porcentaje"
            description="Modifica los detalles de la promoción de porcentaje"
            backHref={route('menu.promotions.percentage.index')}
            backLabel="Volver"
            onSubmit={handleSubmit}
            submitLabel="Guardar"
            processing={processing}
            pageTitle={`Editar: ${promotion.name}`}
            loading={processing}
            loadingSkeleton={EditPageSkeleton}
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
                            checked={formData.is_active}
                            onCheckedChange={(checked) =>
                                setFormData({ ...formData, is_active: checked })
                            }
                        />
                    </div>

                    <FormField label="Nombre" required error={errors.name}>
                        <Input
                            value={formData.name}
                            onChange={(e) =>
                                setFormData({ ...formData, name: e.target.value })
                            }
                            placeholder={PLACEHOLDERS.name}
                            required
                        />
                    </FormField>

                    <FormField label="Descripción" error={errors.description}>
                        <Textarea
                            value={formData.description}
                            onChange={(e) =>
                                setFormData({ ...formData, description: e.target.value })
                            }
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
                                            (product) => {
                                                // Si el producto NO tiene variantes, eliminar si ya está en uso
                                                if (!product.has_variants) {
                                                    return !localItems.some(
                                                        (i) =>
                                                            i.id !== item.id &&
                                                            i.product_id === product.id,
                                                    );
                                                }
                                                // Si el producto TIENE variantes, siempre permitir (se valida por variante)
                                                return true;
                                            }
                                        )}
                                        value={item.product_id}
                                        onChange={(value) => {
                                            updateItem(item.id, 'product_id', value);
                                            // Resetear variantes al cambiar producto
                                            updateItem(item.id, 'variant_ids', [] as number[]);
                                        }}
                                        placeholder={PLACEHOLDERS.search}
                                    />
                                </FormField>

                                {/* Selector de Variantes con Checkboxes */}
                                {item.product_id && (() => {
                                    const selectedProduct = products.find(p => p.id === item.product_id);
                                    const hasVariants = selectedProduct?.has_variants && selectedProduct?.variants && selectedProduct.variants.length > 0;

                                    if (!hasVariants) {
                                        return null;
                                    }

                                    return (
                                        <div className="space-y-2 rounded-lg border border-border bg-card p-4">
                                            {selectedProduct.variants?.map((variant) => (
                                                <div key={variant.id} className="flex items-center space-x-2">
                                                    <Checkbox
                                                        id={`variant-${item.id}-${variant.id}`}
                                                        checked={item.variant_ids.includes(variant.id)}
                                                        onCheckedChange={(checked) => {
                                                            const newVariantIds = checked
                                                                ? [...item.variant_ids, variant.id]
                                                                : item.variant_ids.filter((vid) => vid !== variant.id);
                                                            updateItem(item.id, 'variant_ids', newVariantIds as number[]);
                                                        }}
                                                    />
                                                    <label
                                                        htmlFor={`variant-${item.id}-${variant.id}`}
                                                        className="text-sm cursor-pointer"
                                                    >
                                                        {variant.name} {variant.size && `- ${variant.size}`}
                                                    </label>
                                                </div>
                                            ))}
                                        </div>
                                    );
                                })()}

                                {/* Porcentaje de Descuento */}
                                <FormField
                                    label="Porcentaje"
                                    required
                                    error={getItemError(index, 'discount_percentage')}
                                >
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
                                        required
                                    />
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
        </EditPageLayout>
    );
}
