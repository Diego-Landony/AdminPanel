import { router } from '@inertiajs/react';
import React, { useState } from 'react';
import { CURRENCY, PLACEHOLDERS } from '@/constants/ui-constants';
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

interface Category {
    id: number;
    name: string;
}

interface EditPromotionPageProps {
    promotion: Promotion;
    products: Product[];
    categories: Category[];
}

interface LocalItem {
    id: string;
    product_id: string;
    variant_ids: string[]; // Changed from variant_id to support multiple variants
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

export default function EditPromotion({ promotion, products }: EditPromotionPageProps) {
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
                product_id: item.product_id ? String(item.product_id) : '',
                variant_ids: item.variant_id ? [String(item.variant_id)] : [], // Convert single variant to array
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
            product_id: '',
            variant_ids: [], // Changed from variant_id to variant_ids
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

    const updateItem = (index: number, field: keyof Omit<LocalItem, 'id'>, value: string | number | number[] | boolean) => {
        const updated = [...localItems];
        updated[index] = { ...updated[index], [field]: value };
        setLocalItems(updated);
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        setProcessing(true);

        // Expandir cada item con variant_ids a múltiples items (uno por variante)
        const expandedItems = localItems.flatMap(({ id: _id, has_schedule: _has_schedule, variant_ids, ...rest }: LocalItem) => {
            // Si tiene variantes seleccionadas, crear un item por cada variante
            if (variant_ids.length > 0) {
                return variant_ids.map((variant_id) => {
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
                        variant_id: variant_id,
                        validity_type,
                        special_price_capital: parseFloat(rest.special_price_capital) || 0,
                        special_price_interior: parseFloat(rest.special_price_interior) || 0,
                    };
                });
            }

            // Si no tiene variantes (producto simple), crear un solo item
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

            return [
                {
                    ...rest,
                    variant_id: null,
                    validity_type,
                    special_price_capital: parseFloat(rest.special_price_capital) || 0,
                    special_price_interior: parseFloat(rest.special_price_interior) || 0,
                },
            ];
        });

        const submitData = {
            ...formData,
            items: expandedItems,
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
                    <Label htmlFor="is_active" className="text-base">
                        Promoción activa
                    </Label>
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
                        placeholder={PLACEHOLDERS.name}
                    />
                </FormField>

                <FormField label="Descripción" error={errors.description}>
                    <Textarea
                        value={formData.description}
                        onChange={(e) =>
                            setFormData({ ...formData, description: e.target.value })
                        }
                        placeholder={PLACEHOLDERS.description}
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
                                value={item.product_id ? Number(item.product_id) : null}
                                onChange={(value) => {
                                    // Actualizar product_id y resetear variant_ids en una sola operación
                                    const updated = [...localItems];
                                    updated[index] = {
                                        ...updated[index],
                                        product_id: value ? String(value) : '',
                                        variant_ids: [], // Resetear variantes cuando cambia el producto
                                    };
                                    setLocalItems(updated);
                                }}
                                products={products.filter((product) => {
                                    // Si el producto NO tiene variantes, eliminar si ya está en uso
                                    if (!product.has_variants) {
                                        return !localItems.some(
                                            (i, idx) => idx !== index && i.product_id === String(product.id)
                                        );
                                    }
                                    // Si el producto TIENE variantes, siempre permitir
                                    return true;
                                })}
                                placeholder={PLACEHOLDERS.selectProduct}
                                error={errors[`items.${index}.product_id`]}
                                required
                            />

                            {/* Selector de Variantes */}
                            {item.product_id && (() => {
                                const selectedProduct = products.find(p => p.id === Number(item.product_id));
                                const hasVariants = selectedProduct?.has_variants && selectedProduct?.variants && selectedProduct.variants.length > 0;

                                if (!hasVariants) return null;

                                return (
                                    <FormField
                                        label="Variante"
                                        error={errors[`items.${index}.variant_ids`]}
                                        required
                                    >
                                        <Select
                                            value={item.variant_ids[0] || ''}
                                            onValueChange={(value) => {
                                                const updated = [...localItems];
                                                updated[index] = { ...updated[index], variant_ids: [value] };
                                                setLocalItems(updated);
                                            }}
                                        >
                                            <SelectTrigger>
                                                <SelectValue placeholder="Selecciona una variante" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {selectedProduct.variants?.map((variant) => (
                                                    <SelectItem key={variant.id} value={variant.id.toString()}>
                                                        {variant.name} {variant.size && `- ${variant.size}`}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                    </FormField>
                                );
                            })()}

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
                                            placeholder={PLACEHOLDERS.price}
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
                                            placeholder={PLACEHOLDERS.price}
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
                                    onValueChange={(value: 'both' | 'delivery_only' | 'pickup_only') =>
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
