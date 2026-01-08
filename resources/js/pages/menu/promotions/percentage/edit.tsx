import { showNotification } from '@/hooks/useNotifications';
import { router } from '@inertiajs/react';
import { Banknote, Image, Package, Plus, Store } from 'lucide-react';
import React, { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { route } from 'ziggy-js';

import { NOTIFICATIONS, PLACEHOLDERS } from '@/constants/ui-constants';

import { BadgeTypeSelector, type BadgeType } from '@/components/badge-type-selector';
import { EditPageLayout } from '@/components/edit-page-layout';
import { FormSection } from '@/components/form-section';
import { ImageUpload } from '@/components/ImageUpload';
import { ConfirmationDialog } from '@/components/promotions/ConfirmationDialog';
import { PromotionItemEditor } from '@/components/promotions/PromotionItemEditor';
import { EditPageSkeleton } from '@/components/skeletons';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
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

interface PromotionItem {
    id: number;
    product_id: number | null;
    variant_id: number | null;
    category_id: number | null;
    discount_percentage: string | null;
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
    image_url: string | null;
    type: string;
    is_active: boolean;
    badge_type_id: number | null;
    show_badge_on_menu: boolean;
    items: PromotionItem[];
}

interface EditPromotionPageProps {
    promotion: Promotion;
    products: Product[];
    categories: Category[];
    combos: Combo[];
    badgeTypes: BadgeType[];
}

interface LocalPromotionItem {
    id: string;
    category_id: number | null;
    variant_id: number | null;
    selected_product_ids: number[];
    selected_combo_ids: number[];
    discount_percentage: string;
    hasDeletedProducts?: boolean;
    hasDeletedVariant?: boolean;
    deletedProductCount?: number;
}

function groupPromotionItems(items: PromotionItem[], products: Product[], combos: Combo[], categories: Category[]): LocalPromotionItem[] {
    if (items.length === 0) return [];

    const grouped: LocalPromotionItem[] = [];
    const processed = new Set<number>();

    // Recolectar todos los variant_ids disponibles
    const availableVariantIds = new Set<number>();
    products.forEach((p) => {
        p.variants?.forEach((v) => availableVariantIds.add(v.id));
    });

    items.forEach((item, index) => {
        if (processed.has(index) || !item.product_id) return;

        // Determinar si el item es un combo basándose en la categoría
        const itemCategory = categories.find((c) => c.id === item.category_id);
        const isCombo = itemCategory?.is_combo_category ?? false;

        const groupKey = `${item.variant_id || 'null'}_${item.discount_percentage}_${isCombo ? 'combo' : 'product'}`;

        const relatedItems = items
            .map((it, idx) => ({ item: it, index: idx }))
            .filter(({ item: it, index: idx }) => {
                if (processed.has(idx) || !it.product_id) return false;
                const itCategory = categories.find((c) => c.id === it.category_id);
                const itIsCombo = itCategory?.is_combo_category ?? false;
                return `${it.variant_id || 'null'}_${it.discount_percentage}_${itIsCombo ? 'combo' : 'product'}` === groupKey;
            });

        if (isCombo) {
            // Agrupar combos
            const comboIds = relatedItems.map(({ item: it }) => it.product_id!);
            const existingComboIds = comboIds.filter((cid) => combos.some((c) => c.id === cid));
            const deletedComboCount = comboIds.length - existingComboIds.length;

            relatedItems.forEach(({ index: idx }) => processed.add(idx));

            grouped.push({
                id: generateUniqueId(),
                category_id: item.category_id,
                variant_id: null,
                selected_product_ids: [],
                selected_combo_ids: existingComboIds,
                discount_percentage: item.discount_percentage || '',
                hasDeletedProducts: deletedComboCount > 0,
                hasDeletedVariant: false,
                deletedProductCount: deletedComboCount,
            });
        } else {
            // Agrupar productos
            const productIds = relatedItems.map(({ item: it }) => it.product_id!);
            const existingProductIds = productIds.filter((pid) => products.some((p) => p.id === pid));
            const deletedProductCount = productIds.length - existingProductIds.length;
            const hasDeletedProducts = deletedProductCount > 0;
            const hasDeletedVariant = item.variant_id !== null && !availableVariantIds.has(item.variant_id);

            const productCategories = existingProductIds
                .map((pid) => products.find((p) => p.id === pid)?.category_id)
                .filter((cid) => cid !== undefined);

            const allSameCategory = productCategories.length > 0 && productCategories.every((cid) => cid === productCategories[0]);

            if (allSameCategory) {
                relatedItems.forEach(({ index: idx }) => processed.add(idx));

                grouped.push({
                    id: generateUniqueId(),
                    category_id: productCategories[0]!,
                    variant_id: item.variant_id,
                    selected_product_ids: existingProductIds,
                    selected_combo_ids: [],
                    discount_percentage: item.discount_percentage || '',
                    hasDeletedProducts,
                    hasDeletedVariant,
                    deletedProductCount,
                });
            } else {
                processed.add(index);

                const product = products.find((p) => p.id === item.product_id);

                grouped.push({
                    id: generateUniqueId(),
                    category_id: product?.category_id || null,
                    variant_id: item.variant_id,
                    selected_product_ids: product ? [item.product_id!] : [],
                    selected_combo_ids: [],
                    discount_percentage: item.discount_percentage || '',
                    hasDeletedProducts: !product,
                    hasDeletedVariant,
                    deletedProductCount: !product ? 1 : 0,
                });
            }
        }
    });

    return grouped;
}

export default function EditPercentagePromotion({ promotion, products, categories, combos, badgeTypes }: EditPromotionPageProps) {
    const firstItem = promotion.items[0];

    const [formData, setFormData] = useState({
        is_active: promotion.is_active,
        name: promotion.name,
        description: promotion.description || '',
        type: promotion.type,
        validity_type: (firstItem?.validity_type || 'permanent') as 'permanent' | 'date_range' | 'time_range' | 'date_time_range',
        valid_from: firstItem?.valid_from || '',
        valid_until: firstItem?.valid_until || '',
        time_from: firstItem?.time_from || '',
        time_until: firstItem?.time_until || '',
        badge_type_id: promotion.badge_type_id,
    });

    const [localItems, setLocalItems] = useState<LocalPromotionItem[]>(() => groupPromotionItems(promotion.items, products, combos, categories));

    const [image, setImage] = useState<File | null>(null);
    const [currentImageUrl, setCurrentImageUrl] = useState<string | null>(promotion.image_url);

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

    // Scroll al primer error cuando aparezcan errores de validación
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
        const newItem: LocalPromotionItem = {
            id: generateUniqueId(),
            category_id: null,
            variant_id: null,
            selected_product_ids: [],
            selected_combo_ids: [],
            discount_percentage: '',
        };
        setLocalItems((prev) => [...prev, newItem]);

        // Scroll automático al nuevo item
        setTimeout(() => {
            lastItemRef.current?.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }, 100);
    }, []);

    const removeItem = useCallback((id: string) => {
        setLocalItems((prev) => {
            if (prev.length === 1) {
                showNotification.error(NOTIFICATIONS.error.minItemRequired);
                return prev;
            }
            return prev.filter((item) => item.id !== id);
        });
    }, []);

    const applyUpdate = useCallback((id: string, field: keyof LocalPromotionItem, value: number | number[] | string | null) => {
        setLocalItems((prev) =>
            prev.map((item) => {
                if (item.id !== id) return item;

                const updatedItem = { ...item, [field]: value };

                if (field === 'category_id' && value !== item.category_id) {
                    updatedItem.variant_id = null;
                    updatedItem.selected_product_ids = [];
                }

                if (field === 'variant_id' && value !== item.variant_id) {
                    updatedItem.selected_product_ids = [];
                }

                return updatedItem;
            })
        );
    }, []);

    const updateItem = useCallback(
        (id: string, field: keyof LocalPromotionItem, value: number | number[] | string | null) => {
            const currentItem = localItems.find((item) => item.id === id);
            if (!currentItem) return;

            // Caso 1: Cambiar categoría con productos seleccionados
            if (field === 'category_id' && value !== currentItem.category_id && currentItem.selected_product_ids.length > 0) {
                setConfirmDialog({
                    open: true,
                    title: '¿Cambiar de categoría?',
                    description: 'Se perderán los productos seleccionados y la variante si continúas.',
                    onConfirm: () => applyUpdate(id, field, value),
                    onCancel: () => {},
                });
                return;
            }

            // Caso 2: Cambiar variante con productos seleccionados
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

            // Aplicar actualización sin confirmación
            applyUpdate(id, field, value);
        },
        [localItems, applyUpdate]
    );

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        setProcessing(true);

        const expandedItems = localItems.flatMap((item) => {
            const globalConfig = {
                validity_type: formData.validity_type,
                valid_from: formData.valid_from,
                valid_until: formData.valid_until,
                time_from: formData.time_from,
                time_until: formData.time_until,
            };

            const productItems = item.selected_product_ids.map((product_id) => ({
                product_id,
                variant_id: item.variant_id,
                category_id: item.category_id!,
                discount_percentage: item.discount_percentage,
                ...globalConfig,
            }));

            const comboItems = item.selected_combo_ids.map((combo_id) => {
                const combo = combos.find((c) => c.id === combo_id);
                return {
                    product_id: combo_id,
                    variant_id: null,
                    category_id: combo?.category_id || 0,
                    discount_percentage: item.discount_percentage,
                    ...globalConfig,
                };
            });

            return [...productItems, ...comboItems];
        });

        const formDataObj = new FormData();
        formDataObj.append('_method', 'PUT');
        formDataObj.append('is_active', formData.is_active ? '1' : '0');
        formDataObj.append('name', formData.name);
        formDataObj.append('description', formData.description || '');
        formDataObj.append('type', formData.type);
        if (formData.badge_type_id) {
            formDataObj.append('badge_type_id', String(formData.badge_type_id));
            formDataObj.append('show_badge_on_menu', '1');
        }

        // Agregar items
        expandedItems.forEach((item, index) => {
            formDataObj.append(`items[${index}][product_id]`, String(item.product_id));
            if (item.variant_id) formDataObj.append(`items[${index}][variant_id]`, String(item.variant_id));
            formDataObj.append(`items[${index}][category_id]`, String(item.category_id));
            formDataObj.append(`items[${index}][discount_percentage]`, item.discount_percentage);
            formDataObj.append(`items[${index}][validity_type]`, item.validity_type);
            if (item.valid_from) formDataObj.append(`items[${index}][valid_from]`, item.valid_from);
            if (item.valid_until) formDataObj.append(`items[${index}][valid_until]`, item.valid_until);
            if (item.time_from) formDataObj.append(`items[${index}][time_from]`, item.time_from);
            if (item.time_until) formDataObj.append(`items[${index}][time_until]`, item.time_until);
        });

        // Agregar imagen si existe
        if (image) {
            formDataObj.append('image', image);
        }

        router.post(route('menu.promotions.update', promotion.id), formDataObj, {
            forceFormData: true,
            onError: (errors) => {
                setErrors(errors);
                setProcessing(false);
            },
            onSuccess: () => {
                setProcessing(false);
            },
        });
    };

    const getItemError = (index: number, field: string) => {
        return errors[`items.${index}.${field}`];
    };

    const hasInactiveProducts = useMemo(() => {
        return localItems.some((item) => {
            const selectedProducts = products.filter((p) => item.selected_product_ids.includes(p.id));
            return selectedProducts.some((p) => !p.is_active);
        });
    }, [localItems, products]);

    if (!products || !categories) {
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
            <div className="space-y-8">
                <Card>
                    <CardContent className="pt-6">
                        <FormSection icon={Package} title="Información Básica" description="Configura el nombre y descripción de la promoción">
                            <div className="space-y-4">
                                <div className="flex items-center justify-between rounded-lg border border-border bg-card p-4">
                                    <Label htmlFor="is-active" className="text-base">
                                        Promoción activa
                                    </Label>
                                    <Switch
                                        id="is-active"
                                        checked={formData.is_active}
                                        onCheckedChange={(checked) => setFormData({ ...formData, is_active: checked })}
                                    />
                                </div>

                                <FormField label="Nombre" required error={errors.name}>
                                    <Input
                                        value={formData.name}
                                        onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                                        placeholder={PLACEHOLDERS.name}
                                        required
                                    />
                                </FormField>

                                <FormField label="Descripción" error={errors.description}>
                                    <Textarea
                                        value={formData.description}
                                        onChange={(e) => setFormData({ ...formData, description: e.target.value })}
                                        placeholder={PLACEHOLDERS.description}
                                        rows={3}
                                    />
                                </FormField>

                                <BadgeTypeSelector
                                    value={formData.badge_type_id}
                                    onChange={(value) => setFormData({ ...formData, badge_type_id: value })}
                                    badgeTypes={badgeTypes}
                                    error={errors.badge_type_id}
                                />
                            </div>
                        </FormSection>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent className="pt-6">
                        <FormSection icon={Image} title="Imagen de la Promoción" description="Imagen que se mostrará en la app">
                            <ImageUpload
                                label="Imagen"
                                currentImage={currentImageUrl}
                                onImageChange={(file) => setImage(file)}
                                error={errors.image}
                            />
                        </FormSection>
                    </CardContent>
                </Card>

            {hasInactiveProducts && (
                <div className="rounded-lg border border-amber-200 bg-amber-50 p-4 dark:border-amber-800 dark:bg-amber-950/20">
                    <p className="text-sm font-medium text-amber-800 dark:text-amber-200">
                        ⚠ Advertencia: Esta promoción tiene productos inactivos seleccionados. La promoción no se aplicará a esos productos hasta que se activen.
                    </p>
                </div>
            )}

                <Card>
                    <CardContent className="pt-6">
                        <FormSection icon={Store} title="Configuración Global" description="Configura la disponibilidad">
                <div className="space-y-4">
                    <FormField label="Vigencia" required error={errors.validity_type}>
                        <Select
                            value={formData.validity_type}
                            onValueChange={(value) => setFormData({ ...formData, validity_type: value as typeof formData.validity_type })}
                        >
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

                    {(formData.validity_type === 'date_range' || formData.validity_type === 'date_time_range') && (
                        <div className="grid gap-4 sm:grid-cols-2">
                            <FormField label="Fecha Inicio" required error={errors.valid_from}>
                                <Input
                                    type="date"
                                    value={formData.valid_from}
                                    onChange={(e) => setFormData({ ...formData, valid_from: e.target.value })}
                                    required
                                />
                            </FormField>

                            <FormField label="Fecha Fin" required error={errors.valid_until}>
                                <Input
                                    type="date"
                                    value={formData.valid_until}
                                    onChange={(e) => setFormData({ ...formData, valid_until: e.target.value })}
                                    required
                                />
                            </FormField>
                        </div>
                    )}

                    {(formData.validity_type === 'time_range' || formData.validity_type === 'date_time_range') && (
                        <div className="grid gap-4 sm:grid-cols-2">
                            <FormField label="Hora Inicio" required error={errors.time_from}>
                                <Input
                                    type="time"
                                    value={formData.time_from}
                                    onChange={(e) => setFormData({ ...formData, time_from: e.target.value })}
                                    required
                                />
                            </FormField>

                            <FormField label="Hora Fin" required error={errors.time_until}>
                                <Input
                                    type="time"
                                    value={formData.time_until}
                                    onChange={(e) => setFormData({ ...formData, time_until: e.target.value })}
                                    required
                                />
                            </FormField>
                        </div>
                    )}
                </div>
                        </FormSection>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent className="pt-6">
                        <FormSection icon={Banknote} title="Items de la Promoción" description="Gestiona los productos con descuento">
                <div className="space-y-6">
                    {localItems.map((item, index) => {
                        const excludedVariantIds = localItems
                            .filter((i) => i.id !== item.id && i.variant_id !== null)
                            .map((i) => i.variant_id!);

                        return (
                            <div key={item.id} ref={index === localItems.length - 1 ? lastItemRef : null} className="space-y-3">
                                {item.hasDeletedVariant && (
                                    <div className="rounded-lg border border-red-200 bg-red-50 p-4 dark:border-red-800 dark:bg-red-950/20">
                                        <p className="text-sm font-medium text-red-800 dark:text-red-200">
                                            ⚠ La variante de este item ya no existe. Debes seleccionar una variante válida o eliminar este item.
                                        </p>
                                    </div>
                                )}

                                {item.hasDeletedProducts && (
                                    <div className="rounded-lg border border-amber-200 bg-amber-50 p-4 dark:border-amber-800 dark:bg-amber-950/20">
                                        <p className="text-sm font-medium text-amber-800 dark:text-amber-200">
                                            ⚠ {item.deletedProductCount}{' '}
                                            {item.deletedProductCount === 1 ? 'producto ya no existe' : 'productos ya no existen'}. Los productos
                                            eliminados han sido removidos automáticamente de la selección.
                                        </p>
                                    </div>
                                )}

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
                    </CardContent>
                </Card>
            </div>

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
