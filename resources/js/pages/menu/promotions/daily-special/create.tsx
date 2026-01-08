import { showNotification } from '@/hooks/useNotifications';
import { router, useForm } from '@inertiajs/react';
import { Calendar, Plus, Store, Trash2, Truck } from 'lucide-react';
import React, { useEffect, useRef, useState } from 'react';

import { CURRENCY, NOTIFICATIONS, PLACEHOLDERS } from '@/constants/ui-constants';

import { ProductOrComboSelector } from '@/components/ProductOrComboSelector';
import { ConfirmationDialog } from '@/components/promotions/ConfirmationDialog';
import { VariantSelector } from '@/components/promotions/VariantSelector';
import { WeekdaySelector } from '@/components/WeekdaySelector';
import { CreatePageLayout } from '@/components/create-page-layout';
import { FormSection } from '@/components/form-section';
import { CreatePageSkeleton } from '@/components/skeletons';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
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

interface CreatePromotionPageProps {
    products: Product[];
    categories: Category[];
    combos: Combo[];
}

interface DailySpecialItem {
    id: string;
    product_id: string;
    variant_id: number | null;
    item_type: 'product' | 'combo' | null;
    special_price_pickup_capital: string;
    special_price_delivery_capital: string;
    special_price_pickup_interior: string;
    special_price_delivery_interior: string;
    weekdays: number[];
    has_schedule: boolean;
    valid_from: string;
    valid_until: string;
    time_from: string;
    time_until: string;
}

interface SubmitDailySpecialItem {
    product_id: string;
    variant_id: number | null;
    category_id: number;
    special_price_pickup_capital: number;
    special_price_delivery_capital: number;
    special_price_pickup_interior: number;
    special_price_delivery_interior: number;
    weekdays: number[];
    validity_type: string;
    valid_from: string;
    valid_until: string;
    [key: string]: unknown;
    time_from: string;
    time_until: string;
}

export default function CreatePromotion({ products, combos }: CreatePromotionPageProps) {
    const { data, setData, processing, errors } = useForm({
        is_active: true,
        name: '',
        description: '',
        type: 'daily_special' as const,
        items: [] as Array<Omit<DailySpecialItem, 'id'>>,
    });

    // Cast errors to allow dynamic indexing
    const dynamicErrors = errors as Record<string, string | undefined>;

    const [localItems, setLocalItems] = useState<DailySpecialItem[]>([
        {
            id: generateUniqueId(),
            product_id: '',
            variant_id: null,
            item_type: null,
            special_price_pickup_capital: '',
            special_price_delivery_capital: '',
            special_price_pickup_interior: '',
            special_price_delivery_interior: '',
            weekdays: [],
            has_schedule: false,
            valid_from: '',
            valid_until: '',
            time_from: '',
            time_until: '',
        },
    ]);

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
        const newItem: DailySpecialItem = {
            id: generateUniqueId(),
            product_id: '',
            variant_id: null,
            item_type: null,
            special_price_pickup_capital: '',
            special_price_delivery_capital: '',
            special_price_pickup_interior: '',
            special_price_delivery_interior: '',
            weekdays: [],
            has_schedule: false,
            valid_from: '',
            valid_until: '',
            time_from: '',
            time_until: '',
        };
        const updated = [...localItems, newItem];
        setLocalItems(updated);
        setData('items', updated as unknown as DailySpecialItem[]);

        setTimeout(() => {
            lastItemRef.current?.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }, 100);
    };

    const removeItem = (index: number) => {
        if (localItems.length === 1) {
            showNotification.error(NOTIFICATIONS.error.minItemRequired);
            return;
        }
        const updated = localItems.filter((_, i) => i !== index);
        setLocalItems(updated);
        setData('items', updated as unknown as DailySpecialItem[]);
    };

    const updateItem = (index: number, field: keyof Omit<DailySpecialItem, 'id'>, value: string | number | number[] | boolean | null) => {
        const currentItem = localItems[index];

        if (field === 'product_id' && value !== currentItem.product_id) {
            const hasData = currentItem.variant_id ||
                currentItem.special_price_pickup_capital || currentItem.special_price_delivery_capital ||
                currentItem.special_price_pickup_interior || currentItem.special_price_delivery_interior;

            if (hasData) {
                setConfirmDialog({
                    open: true,
                    title: '¿Cambiar de producto/combo?',
                    description: 'Se perderán la variante y precios seleccionados si continúas.',
                    onConfirm: () => {
                        const updated = [...localItems];
                        updated[index] = {
                            ...updated[index],
                            product_id: value as string,
                            variant_id: null,
                            special_price_pickup_capital: '',
                            special_price_delivery_capital: '',
                            special_price_pickup_interior: '',
                            special_price_delivery_interior: '',
                        };
                        setLocalItems(updated);
                        setData('items', updated as unknown as DailySpecialItem[]);
                        setConfirmDialog({ ...confirmDialog, open: false });
                    },
                    onCancel: () => setConfirmDialog({ ...confirmDialog, open: false }),
                });
                return;
            }
        }

        const updated = [...localItems];
        updated[index] = { ...updated[index], [field]: value };
        setLocalItems(updated);
        setData('items', updated as unknown as DailySpecialItem[]);
    };

    const updateProductOrCombo = (index: number, id: number | null, type: 'product' | 'combo') => {
        const currentItem = localItems[index];
        const hasData = currentItem.variant_id ||
            currentItem.special_price_pickup_capital || currentItem.special_price_delivery_capital ||
            currentItem.special_price_pickup_interior || currentItem.special_price_delivery_interior;

        if (hasData && (String(id) !== currentItem.product_id || type !== currentItem.item_type)) {
            setConfirmDialog({
                open: true,
                title: '¿Cambiar de producto/combo?',
                description: 'Se perderán la variante y precios seleccionados si continúas.',
                onConfirm: () => {
                    const updated = [...localItems];
                    updated[index] = {
                        ...updated[index],
                        product_id: id ? String(id) : '',
                        item_type: type,
                        variant_id: type === 'combo' ? null : updated[index].variant_id,
                        special_price_pickup_capital: '',
                        special_price_delivery_capital: '',
                        special_price_pickup_interior: '',
                        special_price_delivery_interior: '',
                    };
                    setLocalItems(updated);
                    setData('items', updated as unknown as DailySpecialItem[]);
                    setConfirmDialog({ ...confirmDialog, open: false });
                },
                onCancel: () => setConfirmDialog({ ...confirmDialog, open: false }),
            });
            return;
        }

        const updated = [...localItems];
        updated[index] = {
            ...updated[index],
            product_id: id ? String(id) : '',
            item_type: type,
            variant_id: type === 'combo' ? null : updated[index].variant_id,
        };
        setLocalItems(updated);
        setData('items', updated as unknown as DailySpecialItem[]);
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        const expandedItems: SubmitDailySpecialItem[] = localItems.map(({ id: _id, has_schedule: _has_schedule, item_type: _item_type, ...item }) => {
            const currentItem = localItems.find((i) => i.product_id === item.product_id);
            let category_id = 0;

            if (currentItem?.item_type === 'product') {
                const product = products.find((p) => p.id === Number(item.product_id));
                category_id = product?.category_id || 0;
            } else if (currentItem?.item_type === 'combo') {
                const combo = combos.find((c) => c.id === Number(item.product_id));
                category_id = combo?.category_id || 0;
            }

            const hasDates = item.valid_from || item.valid_until;
            const hasTimes = item.time_from || item.time_until;

            let validity_type: string;
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
                product_id: item.product_id,
                variant_id: item.variant_id,
                category_id,
                validity_type,
                special_price_pickup_capital: parseFloat(item.special_price_pickup_capital) || 0,
                special_price_delivery_capital: parseFloat(item.special_price_delivery_capital) || 0,
                special_price_pickup_interior: parseFloat(item.special_price_pickup_interior) || 0,
                special_price_delivery_interior: parseFloat(item.special_price_delivery_interior) || 0,
                weekdays: item.weekdays,
                valid_from: item.valid_from,
                valid_until: item.valid_until,
                time_from: item.time_from,
                time_until: item.time_until,
            };
        });

        const transformedData = {
            is_active: data.is_active,
            name: data.name,
            description: data.description,
            type: data.type,
            items: expandedItems,
        };

        // eslint-disable-next-line @typescript-eslint/no-explicit-any
        router.post(route('menu.promotions.store'), transformedData as any);
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
                    <Label htmlFor="is_active" className="text-base">
                        Promoción activa
                    </Label>
                    <Switch id="is_active" checked={data.is_active} onCheckedChange={(checked) => setData('is_active', checked)} />
                </div>

                <FormField label="Nombre" error={errors.name} required>
                    <Input type="text" value={data.name} onChange={(e) => setData('name', e.target.value)} placeholder={PLACEHOLDERS.name} />
                </FormField>

                <FormField label="Descripción" error={errors.description}>
                    <Textarea
                        value={data.description}
                        onChange={(e) => setData('description', e.target.value)}
                        placeholder={PLACEHOLDERS.description}
                        rows={2}
                    />
                </FormField>
            </FormSection>

            {/* PRODUCTOS */}
            <FormSection title="Productos">
                <div className="space-y-4">
                    {localItems.map((item, index) => {
                        const selectedProduct = item.item_type === 'product' && item.product_id
                            ? products.find((p) => p.id === Number(item.product_id))
                            : null;
                        const hasVariants = selectedProduct?.variants && selectedProduct.variants.length > 0;

                        const excludedVariantIds = localItems
                            .filter((i, idx) => idx !== index && i.product_id === item.product_id && i.variant_id !== null)
                            .map((i) => i.variant_id!);

                        return (
                            <div
                                key={item.id}
                                ref={index === localItems.length - 1 ? lastItemRef : null}
                                className="relative space-y-4 rounded-lg border border-border p-4"
                            >
                                <div className="mb-2 flex items-center justify-between">
                                    <h4 className="text-sm font-medium">Producto/Combo {index + 1}</h4>
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
                                    error={dynamicErrors[`items.${index}.product_id`]}
                                    required
                                />

                                {hasVariants && item.item_type === 'product' && (
                                    <VariantSelector
                                        variants={selectedProduct.variants!.filter((v) => !excludedVariantIds.includes(v.id))}
                                        value={item.variant_id}
                                        onChange={(variantId) => updateItem(index, 'variant_id', variantId)}
                                        error={dynamicErrors[`items.${index}.variant_id`]}
                                        required
                                    />
                                )}

                            {/* Precios - Capital */}
                            <div className="space-y-2">
                                <p className="text-sm font-medium text-muted-foreground">Precios Capital</p>
                                <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                                    <FormField label="Pickup Capital" error={dynamicErrors[`items.${index}.special_price_pickup_capital`]} required>
                                        <div className="relative">
                                            <Store className="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                                            <Input
                                                type="number"
                                                step="0.01"
                                                min="0"
                                                value={item.special_price_pickup_capital}
                                                onChange={(e) => updateItem(index, 'special_price_pickup_capital', e.target.value)}
                                                className="pl-9"
                                                placeholder={PLACEHOLDERS.price}
                                            />
                                        </div>
                                    </FormField>

                                    <FormField label="Delivery Capital" error={dynamicErrors[`items.${index}.special_price_delivery_capital`]} required>
                                        <div className="relative">
                                            <Truck className="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                                            <Input
                                                type="number"
                                                step="0.01"
                                                min="0"
                                                value={item.special_price_delivery_capital}
                                                onChange={(e) => updateItem(index, 'special_price_delivery_capital', e.target.value)}
                                                className="pl-9"
                                                placeholder={PLACEHOLDERS.price}
                                            />
                                        </div>
                                    </FormField>
                                </div>
                            </div>

                            {/* Precios - Interior */}
                            <div className="space-y-2">
                                <p className="text-sm font-medium text-muted-foreground">Precios Interior</p>
                                <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                                    <FormField label="Pickup Interior" error={dynamicErrors[`items.${index}.special_price_pickup_interior`]} required>
                                        <div className="relative">
                                            <Store className="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                                            <Input
                                                type="number"
                                                step="0.01"
                                                min="0"
                                                value={item.special_price_pickup_interior}
                                                onChange={(e) => updateItem(index, 'special_price_pickup_interior', e.target.value)}
                                                className="pl-9"
                                                placeholder={PLACEHOLDERS.price}
                                            />
                                        </div>
                                    </FormField>

                                    <FormField label="Delivery Interior" error={dynamicErrors[`items.${index}.special_price_delivery_interior`]} required>
                                        <div className="relative">
                                            <Truck className="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                                            <Input
                                                type="number"
                                                step="0.01"
                                                min="0"
                                                value={item.special_price_delivery_interior}
                                                onChange={(e) => updateItem(index, 'special_price_delivery_interior', e.target.value)}
                                                className="pl-9"
                                                placeholder={PLACEHOLDERS.price}
                                            />
                                        </div>
                                    </FormField>
                                </div>
                            </div>

                            {/* VIGENCIA */}
                            <div className="space-y-4 rounded-lg border border-border bg-muted/30 p-4">
                                <div className="flex items-center gap-2">
                                    <Calendar className="h-4 w-4 text-muted-foreground" />
                                    <h5 className="text-sm font-medium">Vigencia</h5>
                                </div>

                                {/* Días de la semana */}
                                <WeekdaySelector
                                    value={item.weekdays}
                                    onChange={(days) => updateItem(index, 'weekdays', days)}
                                    error={dynamicErrors[`items.${index}.weekdays`]}
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
                                                <FormField label="Desde" error={dynamicErrors[`items.${index}.valid_from`]}>
                                                    <Input
                                                        type="date"
                                                        value={item.valid_from}
                                                        onChange={(e) => updateItem(index, 'valid_from', e.target.value)}
                                                    />
                                                </FormField>
                                                <FormField label="Hasta" error={dynamicErrors[`items.${index}.valid_until`]}>
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
                                                <FormField label="Desde" error={dynamicErrors[`items.${index}.time_from`]}>
                                                    <Input
                                                        type="time"
                                                        value={item.time_from}
                                                        onChange={(e) => updateItem(index, 'time_from', e.target.value)}
                                                    />
                                                </FormField>
                                                <FormField label="Hasta" error={dynamicErrors[`items.${index}.time_until`]}>
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
        </CreatePageLayout>
    );
}
