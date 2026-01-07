import { showNotification } from '@/hooks/useNotifications';
import { router, useForm } from '@inertiajs/react';
import { Award, Package, Plus, Store, Truck } from 'lucide-react';
import React, { useCallback, useEffect, useMemo, useRef, useState } from 'react';

import { NOTIFICATIONS, PLACEHOLDERS } from '@/constants/ui-constants';

import { BadgeTypeSelector, type BadgeType } from '@/components/badge-type-selector';
import { CreatePageLayout } from '@/components/create-page-layout';
import { FormSection } from '@/components/form-section';
import { ConfirmationDialog } from '@/components/promotions/ConfirmationDialog';
import { PromotionItemEditor } from '@/components/promotions/PromotionItemEditor';
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
    badgeTypes: BadgeType[];
}

interface LocalTwoForOneItem {
    id: string;
    category_id: number | null;
    variant_id: number | null;
    selected_product_ids: number[];
    selected_combo_ids: number[];
    discount_percentage: string;
}

export default function CreateTwoForOnePromotion({ products, categories, combos, badgeTypes }: CreatePromotionPageProps) {
    const { data, setData, processing, errors } = useForm({
        is_active: true,
        name: '',
        description: '',
        type: 'two_for_one' as const,
        items: [] as LocalTwoForOneItem[],
        service_type: 'both' as 'both' | 'delivery_only' | 'pickup_only',
        validity_type: 'permanent' as 'permanent' | 'date_range' | 'time_range' | 'date_time_range',
        valid_from: '',
        valid_until: '',
        time_from: '',
        time_until: '',
        badge_type_id: null as number | null,
        show_badge_on_menu: true,
    });

    const [localItems, setLocalItems] = useState<LocalTwoForOneItem[]>([
        {
            id: generateUniqueId(),
            category_id: null,
            variant_id: null,
            selected_product_ids: [],
            selected_combo_ids: [],
            discount_percentage: '',
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

    const addItem = useCallback(() => {
        const newItem: LocalTwoForOneItem = {
            id: generateUniqueId(),
            category_id: null,
            variant_id: null,
            selected_product_ids: [],
            selected_combo_ids: [],
            discount_percentage: '',
        };
        setLocalItems((prev) => {
            const updated = [...prev, newItem];
            setData('items', updated);
            return updated;
        });

        setTimeout(() => {
            lastItemRef.current?.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }, 100);
    }, [setData]);

    const removeItem = useCallback(
        (id: string) => {
            if (localItems.length === 1) {
                showNotification.error(NOTIFICATIONS.error.minItemRequired);
                return;
            }
            setLocalItems((prev) => {
                const updated = prev.filter((item) => item.id !== id);
                setData('items', updated);
                return updated;
            });
        },
        [localItems.length, setData]
    );

    const applyUpdate = useCallback(
        (id: string, field: keyof LocalTwoForOneItem, value: number | number[] | string | null) => {
            setLocalItems((prev) => {
                const updated = prev.map((item) => {
                    if (item.id !== id) return item;

                    const updatedItem = { ...item, [field]: value };

                    if (field === 'category_id' && value !== item.category_id) {
                        updatedItem.variant_id = null;
                        updatedItem.selected_product_ids = [];
                        updatedItem.selected_combo_ids = [];
                    }

                    if (field === 'variant_id' && value !== item.variant_id) {
                        updatedItem.selected_product_ids = [];
                    }

                    return updatedItem;
                });

                setData('items', updated);
                return updated;
            });
        },
        [setData]
    );

    const updateItem = useCallback(
        (id: string, field: keyof LocalTwoForOneItem, value: number | number[] | string | null) => {
            const currentItem = localItems.find((item) => item.id === id);
            if (!currentItem) return;

            if (field === 'category_id' && value !== currentItem.category_id && (currentItem.selected_product_ids.length > 0 || currentItem.selected_combo_ids.length > 0)) {
                setConfirmDialog({
                    open: true,
                    title: '¿Cambiar de categoría?',
                    description: 'Se perderán los productos/combos seleccionados y la variante si continúas.',
                    onConfirm: () => applyUpdate(id, field, value),
                    onCancel: () => {},
                });
                return;
            }

            if (field === 'variant_id' && value !== currentItem.variant_id && currentItem.selected_product_ids.length > 0) {
                setConfirmDialog({
                    open: true,
                    title: '¿Cambiar de variante?',
                    description: 'Se perderán los productos seleccionados si continúas.',
                    onConfirm: () => applyUpdate(id, field, value),
                    onCancel: () => {},
                });
                return;
            }

            applyUpdate(id, field, value);
        },
        [localItems, applyUpdate]
    );

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        interface SubmitItem {
            product_id: number;
            variant_id: number | null;
            category_id: number;
            service_type: 'both' | 'delivery_only' | 'pickup_only';
            validity_type: 'permanent' | 'date_range' | 'time_range' | 'date_time_range';
            valid_from: string;
            valid_until: string;
            time_from: string;
            time_until: string;
            [key: string]: unknown;
        }

        const expandedItems: SubmitItem[] = localItems.flatMap<SubmitItem>((item) => {
            const globalConfig = {
                service_type: data.service_type,
                validity_type: data.validity_type,
                valid_from: data.valid_from,
                valid_until: data.valid_until,
                time_from: data.time_from,
                time_until: data.time_until,
            };

            const productItems = item.selected_product_ids.map<SubmitItem>((product_id) => ({
                product_id,
                variant_id: item.variant_id,
                category_id: item.category_id!,
                ...globalConfig,
            }));

            const comboItems = item.selected_combo_ids.map<SubmitItem>((combo_id) => {
                const combo = combos.find((c) => c.id === combo_id);
                return {
                    product_id: combo_id,
                    variant_id: null,
                    category_id: combo?.category_id || 0,
                    ...globalConfig,
                };
            });

            return [...productItems, ...comboItems];
        });

        router.post(route('menu.promotions.store'), {
            is_active: data.is_active,
            name: data.name,
            description: data.description,
            type: data.type,
            badge_type_id: data.badge_type_id,
            show_badge_on_menu: data.show_badge_on_menu,
            // eslint-disable-next-line @typescript-eslint/no-explicit-any
            items: expandedItems as any,
        });
    };

    const getItemError = (index: number, field: string) => {
        const error = errors[`items.${index}.${field}` as keyof typeof errors];
        return error as string | undefined;
    };

    const hasInactiveProducts = useMemo(() => {
        return localItems.some((item) => {
            const selectedProducts = products.filter((p) => item.selected_product_ids.includes(p.id));
            const selectedCombos = combos.filter((c) => item.selected_combo_ids?.includes(c.id));
            return selectedProducts.some((p) => !p.is_active) || selectedCombos.some((c) => !c.is_active);
        });
    }, [localItems, products, combos]);

    if (!products || !categories) {
        return <CreatePageSkeleton />;
    }

    return (
        <CreatePageLayout
            title="Crear Promoción 2x1"
            description="Crea una nueva promoción de dos por uno"
            backHref={route('menu.promotions.two-for-one.index')}
            backLabel="Volver"
            onSubmit={handleSubmit}
            submitLabel="Guardar"
            processing={processing}
            pageTitle="Crear Promoción - 2x1"
            loading={processing}
            loadingSkeleton={CreatePageSkeleton}
        >
            <FormSection icon={Package} title="Información Básica">
                <div className="space-y-4">
                    <div className="flex items-center justify-between rounded-lg border border-border bg-card p-4">
                        <Label htmlFor="is-active" className="text-base">
                            Promoción activa
                        </Label>
                        <Switch id="is-active" checked={data.is_active} onCheckedChange={(checked) => setData('is_active', checked)} />
                    </div>

                    <FormField label="Nombre" required error={errors.name}>
                        <Input value={data.name} onChange={(e) => setData('name', e.target.value)} placeholder={PLACEHOLDERS.name} required />
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

            {/* Insignia/Badge */}
            <div className="rounded-lg border bg-card p-6">
                <FormSection
                    icon={Award}
                    title="Insignia"
                    description="Selecciona una insignia para mostrar en los productos de esta promoción"
                >
                    <div className="space-y-4">
                        <BadgeTypeSelector
                            value={data.badge_type_id}
                            onChange={(value) => setData('badge_type_id', value)}
                            badgeTypes={badgeTypes}
                            error={errors.badge_type_id}
                            showLabels={false}
                        />

                        <div className="flex items-center space-x-2">
                            <Checkbox
                                id="show_badge_on_menu"
                                checked={data.show_badge_on_menu}
                                onCheckedChange={(checked) => setData('show_badge_on_menu', checked as boolean)}
                            />
                            <Label htmlFor="show_badge_on_menu" className="cursor-pointer text-sm font-medium leading-none">
                                Mostrar insignia en el menú
                            </Label>
                        </div>
                    </div>
                </FormSection>
            </div>

            {hasInactiveProducts && (
                <div className="rounded-lg border border-amber-200 bg-amber-50 p-4 dark:border-amber-800 dark:bg-amber-950/20">
                    <p className="text-sm font-medium text-amber-800 dark:text-amber-200">
                        ⚠ Advertencia: Esta promoción tiene productos inactivos seleccionados. La promoción no se aplicará a esos productos hasta que se activen.
                    </p>
                </div>
            )}

            <FormSection icon={Store} title="Configuración Global">
                <div className="space-y-4">
                    <FormField label="Tipo de servicio" required error={errors.service_type}>
                        <Select value={data.service_type} onValueChange={(value) => setData('service_type', value as typeof data.service_type)}>
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

                    <FormField label="Vigencia" required error={errors.validity_type}>
                        <Select value={data.validity_type} onValueChange={(value) => setData('validity_type', value as typeof data.validity_type)}>
                            <SelectTrigger>
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="permanent">Permanente</SelectItem>
                                <SelectItem value="date_range">Rango de Fechas</SelectItem>
                                <SelectItem value="time_range">Rango de Horario</SelectItem>
                                <SelectItem value="date_time_range">Fechas + Horario</SelectItem>
                            </SelectContent>
                        </Select>
                    </FormField>

                    {(data.validity_type === 'date_range' || data.validity_type === 'date_time_range') && (
                        <div className="grid gap-4 sm:grid-cols-2">
                            <FormField label="Fecha Inicio" required error={errors.valid_from}>
                                <Input type="date" value={data.valid_from} onChange={(e) => setData('valid_from', e.target.value)} required />
                            </FormField>

                            <FormField label="Fecha Fin" required error={errors.valid_until}>
                                <Input type="date" value={data.valid_until} onChange={(e) => setData('valid_until', e.target.value)} required />
                            </FormField>
                        </div>
                    )}

                    {(data.validity_type === 'time_range' || data.validity_type === 'date_time_range') && (
                        <div className="grid gap-4 sm:grid-cols-2">
                            <FormField label="Hora Inicio" required error={errors.time_from}>
                                <Input type="time" value={data.time_from} onChange={(e) => setData('time_from', e.target.value)} required />
                            </FormField>

                            <FormField label="Hora Fin" required error={errors.time_until}>
                                <Input type="time" value={data.time_until} onChange={(e) => setData('time_until', e.target.value)} required />
                            </FormField>
                        </div>
                    )}
                </div>
            </FormSection>

            <FormSection icon={Package} title="Items de la Promoción">
                <div className="space-y-6">
                    {localItems.map((item, index) => {
                        const excludedVariantIds = localItems
                            .filter((i) => i.id !== item.id && i.variant_id !== null)
                            .map((i) => i.variant_id!);

                        return (
                            <div key={item.id} ref={index === localItems.length - 1 ? lastItemRef : null}>
                                <PromotionItemEditor
                                    item={item}
                                    index={index}
                                    categories={categories}
                                    products={products}
                                    combos={combos}
                                    onUpdate={updateItem}
                                    onRemove={removeItem}
                                    canRemove={localItems.length > 1}
                                    getItemError={getItemError}
                                    excludedVariantIds={excludedVariantIds}
                                    showDiscount={false}
                                />
                            </div>
                        );
                    })}

                    <Button type="button" variant="outline" onClick={addItem} className="w-full">
                        <Plus className="mr-2 h-4 w-4" />
                        Agregar Item
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
