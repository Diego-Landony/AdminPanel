import { showNotification } from '@/hooks/useNotifications';
import { router } from '@inertiajs/react';
import { Calendar, Plus, Store, Trash2, Truck } from 'lucide-react';
import React, { useEffect, useRef, useState } from 'react';

import { CURRENCY, NOTIFICATIONS, PLACEHOLDERS } from '@/constants/ui-constants';

import { ProductOrComboSelector } from '@/components/ProductOrComboSelector';
import { ConfirmationDialog } from '@/components/promotions/ConfirmationDialog';
import { VariantSelector } from '@/components/promotions/VariantSelector';
import { WeekdaySelector } from '@/components/WeekdaySelector';
import { EditPageLayout } from '@/components/edit-page-layout';
import { FormSection } from '@/components/form-section';
import { EditPageSkeleton } from '@/components/skeletons';
import { Button } from '@/components/ui/button';
import { FormField } from '@/components/ui/form-field';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Switch } from '@/components/ui/switch';
import { Textarea } from '@/components/ui/textarea';
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
    is_active: boolean;
    category?: {
        id: number;
        name: string;
    };
    variants?: ProductVariant[];
}

interface PromotionItem {
    id: number;
    product_id: number | null;
    category_id: number | null;
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
    category?: {
        id: number;
        name: string;
        is_combo_category?: boolean;
    };
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
    is_combo_category?: boolean;
}

interface Combo {
    id: number;
    name: string;
    category_id: number;
    is_active: boolean;
    precio_pickup_capital: number;
    precio_domicilio_capital: number;
    precio_pickup_interior: number;
    precio_domicilio_interior: number;
    category?: {
        id: number;
        name: string;
    };
}

interface EditPromotionPageProps {
    promotion: Promotion;
    products: Product[];
    categories: Category[];
    combos: Combo[];
}

interface LocalItem {
    id: string;
    product_id: string;
    item_type: 'product' | 'combo' | null;
    variant_id: number | null;
    special_price_capital: string;
    special_price_interior: string;
    service_type: 'both' | 'delivery_only' | 'pickup_only';
    weekdays: number[];
    has_schedule: boolean;
    valid_from: string;
    valid_until: string;
    time_from: string;
    time_until: string;
}

export default function EditPromotion({ promotion, products, combos }: EditPromotionPageProps) {
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

            // Determinar si es combo basado en la categoría cargada
            const isCombo = item.category?.is_combo_category ?? false;

            return {
                id: generateUniqueId(),
                product_id: item.product_id ? String(item.product_id) : '',
                item_type: isCombo ? 'combo' : 'product',
                variant_id: item.variant_id,
                special_price_capital: String(item.special_price_capital || ''),
                special_price_interior: String(item.special_price_interior || ''),
                service_type: item.service_type || 'both',
                weekdays: item.weekdays || [],
                has_schedule: hasDates || hasTimes,
                valid_from: item.valid_from || '',
                valid_until: item.valid_until || '',
                time_from: item.time_from || '',
                time_until: item.time_until || '',
            };
        }),
    );

    const [errors, setErrors] = useState<Record<string, string>>({});
    const [processing, setProcessing] = useState(false);

    const [confirmDialog, setConfirmDialog] = useState<{
        open: boolean;
        title: string;
        description: string;
        onConfirm: () => void;
        onCancel: () => void;
    }>({
        open: false,
        title: '',
        description: '',
        onConfirm: () => {},
        onCancel: () => {},
    });

    const lastItemRef = useRef<HTMLDivElement>(null);

    useEffect(() => {
        if (Object.keys(errors).length > 0) {
            const firstErrorKey = Object.keys(errors)[0];
            const errorElement = document.querySelector(`[name="${firstErrorKey}"]`) || document.querySelector(`[data-error="${firstErrorKey}"]`);

            if (errorElement) {
                errorElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }
    }, [errors]);

    const addItem = () => {
        const newItem: LocalItem = {
            id: generateUniqueId(),
            product_id: '',
            item_type: null,
            variant_id: null,
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

        setTimeout(() => {
            lastItemRef.current?.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }, 100);
    };

    const removeItem = (index: number) => {
        if (localItems.length === 1) {
            showNotification.error(NOTIFICATIONS.error.minItemRequired);
            return;
        }
        setLocalItems(localItems.filter((_, i) => i !== index));
    };

    const updateProductOrCombo = (index: number, value: number | null, type: 'product' | 'combo') => {
        const currentItem = localItems[index];
        const hasData = currentItem.variant_id || currentItem.special_price_capital || currentItem.special_price_interior;

        const performUpdate = () => {
            const updated = [...localItems];
            updated[index] = {
                ...updated[index],
                product_id: value ? String(value) : '',
                item_type: type,
                variant_id: null,
                special_price_capital: '',
                special_price_interior: '',
            };
            setLocalItems(updated);
        };

        if (value !== Number(currentItem.product_id) || type !== currentItem.item_type) {
            if (hasData) {
                setConfirmDialog({
                    open: true,
                    title: '¿Cambiar de producto/combo?',
                    description: 'Se perderán la variante y precios seleccionados si continúas.',
                    onConfirm: () => {
                        performUpdate();
                        setConfirmDialog({ ...confirmDialog, open: false });
                    },
                    onCancel: () => setConfirmDialog({ ...confirmDialog, open: false }),
                });
                return;
            }
            performUpdate();
        }
    };

    const updateItem = (index: number, field: keyof Omit<LocalItem, 'id'>, value: string | number | number[] | boolean | null) => {
        const updated = [...localItems];
        updated[index] = { ...updated[index], [field]: value };
        setLocalItems(updated);
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        setProcessing(true);

        const expandedItems = localItems.map(({ id: _id, has_schedule: _has_schedule, item_type, ...item }) => {
            // Buscar en productos o combos según el tipo
            const entity = item_type === 'combo'
                ? combos.find((c) => c.id === Number(item.product_id))
                : products.find((p) => p.id === Number(item.product_id));

            const hasDates = item.valid_from || item.valid_until;
            const hasTimes = item.time_from || item.time_until;

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
                ...item,
                category_id: entity?.category_id || 0,
                validity_type,
                special_price_capital: parseFloat(item.special_price_capital) || 0,
                special_price_interior: parseFloat(item.special_price_interior) || 0,
            };
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
                        onCheckedChange={(checked) => setFormData({ ...formData, is_active: checked })}
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
                        onChange={(e) => setFormData({ ...formData, description: e.target.value })}
                        placeholder={PLACEHOLDERS.description}
                        rows={2}
                    />
                </FormField>
            </FormSection>

            {/* PRODUCTOS Y COMBOS */}
            <FormSection title="Productos y Combos">
                <div className="space-y-4">
                    {localItems.map((item, index) => {
                        const isProduct = item.item_type === 'product';
                        const isCombo = item.item_type === 'combo';
                        const selectedProduct = isProduct && item.product_id ? products.find((p) => p.id === Number(item.product_id)) : null;
                        const _selectedCombo = isCombo && item.product_id ? combos.find((c) => c.id === Number(item.product_id)) : null;
                        const hasVariants = isProduct && selectedProduct?.variants && selectedProduct.variants.length > 0;

                        const excludedVariantIds = localItems
                            .filter((i, idx) => idx !== index && i.product_id === item.product_id && i.variant_id !== null && i.item_type === 'product')
                            .map((i) => i.variant_id!);

                        return (
                            <div
                                key={item.id}
                                ref={index === localItems.length - 1 ? lastItemRef : null}
                                className="relative space-y-4 rounded-lg border border-border p-4"
                            >
                                <div className="mb-2 flex items-center justify-between">
                                    <h4 className="text-sm font-medium">
                                        {isCombo ? 'Combo' : 'Producto'} {index + 1}
                                    </h4>
                                    {localItems.length > 1 && (
                                        <Button type="button" variant="ghost" size="sm" onClick={() => removeItem(index)}>
                                            <Trash2 className="h-4 w-4 text-destructive" />
                                        </Button>
                                    )}
                                </div>

                                <ProductOrComboSelector
                                    label="Producto o Combo"
                                    value={item.product_id ? Number(item.product_id) : null}
                                    onChange={(value, type) => updateProductOrCombo(index, value, type)}
                                    products={products}
                                    combos={combos}
                                    type={item.item_type}
                                    placeholder={PLACEHOLDERS.selectProduct}
                                    error={errors[`items.${index}.product_id`]}
                                    required
                                />

                                {hasVariants && (
                                    <VariantSelector
                                        variants={selectedProduct.variants!.filter((v) => !excludedVariantIds.includes(v.id))}
                                        value={item.variant_id}
                                        onChange={(variantId) => updateItem(index, 'variant_id', variantId)}
                                        error={errors[`items.${index}.variant_id`]}
                                        required
                                    />
                                )}

                            {/* Precios */}
                            <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <FormField label="Precio Capital" error={errors[`items.${index}.special_price_capital`]} required>
                                    <div className="relative">
                                        <span className="absolute top-1/2 left-3 -translate-y-1/2 text-muted-foreground">{CURRENCY.symbol}</span>
                                        <Input
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            value={item.special_price_capital}
                                            onChange={(e) => updateItem(index, 'special_price_capital', e.target.value)}
                                            className="pl-7"
                                            placeholder={PLACEHOLDERS.price}
                                        />
                                    </div>
                                </FormField>

                                <FormField label="Precio Interior" error={errors[`items.${index}.special_price_interior`]} required>
                                    <div className="relative">
                                        <span className="absolute top-1/2 left-3 -translate-y-1/2 text-muted-foreground">{CURRENCY.symbol}</span>
                                        <Input
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            value={item.special_price_interior}
                                            onChange={(e) => updateItem(index, 'special_price_interior', e.target.value)}
                                            className="pl-7"
                                            placeholder={PLACEHOLDERS.price}
                                        />
                                    </div>
                                </FormField>
                            </div>

                            {/* Tipo de Servicio */}
                            <FormField label="Tipo de servicio" error={errors[`items.${index}.service_type`]} required>
                                <Select
                                    value={item.service_type}
                                    onValueChange={(value: 'both' | 'delivery_only' | 'pickup_only') => updateItem(index, 'service_type', value)}
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
                            <div className="space-y-4 rounded-lg border border-border bg-muted/30 p-4">
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
                                        onChange={(e) => updateItem(index, 'has_schedule', e.target.checked)}
                                        className="h-4 w-4 rounded border-gray-300 text-primary focus:ring-primary"
                                    />
                                    <label
                                        htmlFor={`has_schedule_${index}`}
                                        className="cursor-pointer text-sm leading-none font-medium peer-disabled:cursor-not-allowed peer-disabled:opacity-70"
                                    >
                                        Restringir por fechas u horarios
                                    </label>
                                </div>

                                {/* Campos condicionales */}
                                {item.has_schedule && (
                                    <div className="space-y-4 border-l-2 border-primary/20 pl-6">
                                        {/* Rango de fechas */}
                                        <div className="space-y-2">
                                            <p className="text-sm font-medium">Fechas</p>
                                            <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                                                <FormField label="Desde" error={errors[`items.${index}.valid_from`]}>
                                                    <Input
                                                        type="date"
                                                        value={item.valid_from}
                                                        onChange={(e) => updateItem(index, 'valid_from', e.target.value)}
                                                    />
                                                </FormField>
                                                <FormField label="Hasta" error={errors[`items.${index}.valid_until`]}>
                                                    <Input
                                                        type="date"
                                                        value={item.valid_until}
                                                        onChange={(e) => updateItem(index, 'valid_until', e.target.value)}
                                                    />
                                                </FormField>
                                            </div>
                                        </div>

                                        {/* Horario */}
                                        <div className="space-y-2">
                                            <p className="text-sm font-medium">Horarios</p>
                                            <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                                                <FormField label="Desde" error={errors[`items.${index}.time_from`]}>
                                                    <Input
                                                        type="time"
                                                        value={item.time_from}
                                                        onChange={(e) => updateItem(index, 'time_from', e.target.value)}
                                                    />
                                                </FormField>
                                                <FormField label="Hasta" error={errors[`items.${index}.time_until`]}>
                                                    <Input
                                                        type="time"
                                                        value={item.time_until}
                                                        onChange={(e) => updateItem(index, 'time_until', e.target.value)}
                                                    />
                                                </FormField>
                                            </div>
                                        </div>
                                    </div>
                                )}
                            </div>
                        </div>
                        );
                    })}

                    <Button type="button" variant="outline" onClick={addItem} className="w-full">
                        <Plus className="mr-2 h-4 w-4" />
                        Agregar otro producto
                    </Button>
                </div>
            </FormSection>

            <ConfirmationDialog
                open={confirmDialog.open}
                onOpenChange={(open) => setConfirmDialog({ ...confirmDialog, open })}
                title={confirmDialog.title}
                description={confirmDialog.description}
                confirmLabel="Cambiar"
                cancelLabel="Cancelar"
                onConfirm={confirmDialog.onConfirm}
                onCancel={confirmDialog.onCancel}
            />
        </EditPageLayout>
    );
}
