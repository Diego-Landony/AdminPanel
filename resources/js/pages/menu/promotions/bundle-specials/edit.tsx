import { closestCenter, DndContext, DragEndEvent, KeyboardSensor, PointerSensor, useSensor, useSensors } from '@dnd-kit/core';
import { arrayMove, SortableContext, sortableKeyboardCoordinates, verticalListSortingStrategy } from '@dnd-kit/sortable';
import { router } from '@inertiajs/react';
import { useMemo, useState } from 'react';

import { BadgeTypeSelector, type BadgeType } from '@/components/badge-type-selector';
import { ComboItemCard } from '@/components/combos/ComboItemCard';
import { EditPageLayout } from '@/components/edit-page-layout';
import { FormSection } from '@/components/form-section';
import { ImageUpload } from '@/components/ImageUpload';
import { EditProductsSkeleton } from '@/components/skeletons';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { FormField } from '@/components/ui/form-field';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Switch } from '@/components/ui/switch';
import { Textarea } from '@/components/ui/textarea';
import { WeekdaySelector } from '@/components/WeekdaySelector';
import { CURRENCY, FORM_SECTIONS, PLACEHOLDERS } from '@/constants/ui-constants';
import { generateUniqueItemId, prepareComboDataForSubmit, validateMinimumComboStructure } from '@/utils/comboHelpers';
import { AlertCircle, Banknote, Calendar, Gift, Image, Package, Plus } from 'lucide-react';

interface ProductVariant {
    id: number;
    product_id: number;
    name: string;
    size: string;
    precio_pickup_capital: number;
}

interface Product {
    id: number;
    name: string;
    has_variants: boolean;
    is_active: boolean;
    variants?: ProductVariant[];
    category?: {
        id: number;
        name: string;
    };
}

interface ChoiceOption {
    id: number;
    product_id: number;
    variant_id?: number | null;
    sort_order: number;
    product: {
        id: number;
        name: string;
        is_active: boolean;
    };
    variant?: {
        id: number;
        name: string;
    } | null;
}

interface BundlePromotionItem {
    id: number | string;
    is_choice_group: boolean;
    choice_label?: string | null;
    product_id?: number | null;
    variant_id?: number | null;
    quantity: number;
    sort_order: number;
    product?: {
        id: number;
        name: string;
        is_active: boolean;
    } | null;
    variant?: {
        id: number;
        name: string;
        size: string;
    } | null;
    options?: ChoiceOption[];
}

interface Combinado {
    id: number;
    name: string;
    description: string | null;
    image_url: string | null;
    is_active: boolean;
    special_bundle_price_pickup_capital: number | null;
    special_bundle_price_delivery_capital: number | null;
    special_bundle_price_pickup_interior: number | null;
    special_bundle_price_delivery_interior: number | null;
    valid_from: string | null;
    valid_until: string | null;
    time_from: string | null;
    time_until: string | null;
    weekdays: number[] | null;
    bundle_items: BundlePromotionItem[];
    badge_type_id: number | null;
    show_badge_on_menu: boolean;
}

interface EditBundleSpecialPageProps {
    combinado: Combinado;
    products: Product[];
    badgeTypes: BadgeType[];
}

interface LocalBundleItem {
    id: string;
    is_choice_group: boolean;
    choice_label?: string;
    product_id?: number | null;
    variant_id?: number | null;
    quantity: number;
    sort_order: number;
    options?: {
        id: string;
        product_id: number;
        variant_id?: number | null;
        sort_order: number;
    }[];
}

export default function BundleSpecialEdit({ combinado, products, badgeTypes }: EditBundleSpecialPageProps) {
    // Map server data to local format
    const initialItems: LocalBundleItem[] = combinado.bundle_items.map((item) => ({
        id: `item-${item.id}`,
        is_choice_group: item.is_choice_group,
        choice_label: item.choice_label || undefined,
        product_id: item.product_id || null,
        variant_id: item.variant_id || null,
        quantity: item.quantity,
        sort_order: item.sort_order,
        options:
            item.options?.map((opt) => ({
                id: `option-${opt.id}`,
                product_id: opt.product_id,
                variant_id: opt.variant_id || null,
                sort_order: opt.sort_order,
            })) || [],
    }));

    // Determinar el validity_type inicial basándose en los datos
    const getInitialValidityType = (): 'permanent' | 'date_range' | 'time_range' | 'date_time_range' => {
        const hasDates = !!combinado.valid_from || !!combinado.valid_until;
        const hasTimes = !!combinado.time_from || !!combinado.time_until;

        if (hasDates && hasTimes) return 'date_time_range';
        if (hasDates) return 'date_range';
        if (hasTimes) return 'time_range';
        return 'permanent';
    };

    const [formData, setFormData] = useState({
        name: combinado.name,
        description: combinado.description || '',
        is_active: combinado.is_active,
        special_bundle_price_pickup_capital: String(combinado.special_bundle_price_pickup_capital || ''),
        special_bundle_price_delivery_capital: String(combinado.special_bundle_price_delivery_capital || ''),
        special_bundle_price_pickup_interior: String(combinado.special_bundle_price_pickup_interior || ''),
        special_bundle_price_delivery_interior: String(combinado.special_bundle_price_delivery_interior || ''),
        validity_type: getInitialValidityType(),
        valid_from: combinado.valid_from || '',
        valid_until: combinado.valid_until || '',
        time_from: combinado.time_from || '',
        time_until: combinado.time_until || '',
        weekdays: combinado.weekdays || [],
        badge_type_id: combinado.badge_type_id,
    });

    const [errors, setErrors] = useState<Record<string, string>>({});
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [localItems, setLocalItems] = useState<LocalBundleItem[]>(initialItems);
    const [enableWeekdays, setEnableWeekdays] = useState(!!combinado.weekdays && combinado.weekdays.length > 0);
    const [image, setImage] = useState<File | null>(null);
    const [currentImageUrl, setCurrentImageUrl] = useState<string | null>(combinado.image_url);

    const sensors = useSensors(
        useSensor(PointerSensor),
        useSensor(KeyboardSensor, {
            coordinateGetter: sortableKeyboardCoordinates,
        }),
    );

    const addItem = () => {
        const newItem: LocalBundleItem = {
            id: generateUniqueItemId(),
            is_choice_group: false,
            product_id: null,
            variant_id: null,
            quantity: '',
            sort_order: localItems.length + 1,
            options: [],
        };
        setLocalItems([...localItems, newItem]);
    };

    const removeItem = (index: number) => {
        setLocalItems(localItems.filter((_, i) => i !== index));
    };

    const updateItem = (
        index: number,
        field: string,
        value: string | number | boolean | { id: string; product_id: number; variant_id?: number | null; sort_order: number }[] | null,
    ) => {
        const updated = [...localItems];
        updated[index] = { ...updated[index], [field]: value };
        setLocalItems(updated);
    };

    const batchUpdateItem = (index: number, updates: Partial<LocalBundleItem>) => {
        const updated = [...localItems];
        updated[index] = { ...updated[index], ...updates };
        setLocalItems(updated);
    };

    const handleDragEnd = (event: DragEndEvent) => {
        const { active, over } = event;

        if (over && active.id !== over.id) {
            const oldIndex = localItems.findIndex((item) => item.id === active.id);
            const newIndex = localItems.findIndex((item) => item.id === over.id);
            setLocalItems(arrayMove(localItems, oldIndex, newIndex));
        }
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        const validation = validateMinimumComboStructure(localItems);
        if (!validation.valid) {
            setErrors({ items: validation.errors[0] });
            return;
        }

        setIsSubmitting(true);

        const preparedItems = prepareComboDataForSubmit(localItems);

        const formDataObj = new FormData();
        formDataObj.append('_method', 'PUT');
        formDataObj.append('name', formData.name);
        formDataObj.append('description', formData.description || '');
        formDataObj.append('is_active', formData.is_active ? '1' : '0');
        formDataObj.append('special_bundle_price_pickup_capital', formData.special_bundle_price_pickup_capital);
        formDataObj.append('special_bundle_price_delivery_capital', formData.special_bundle_price_delivery_capital);
        formDataObj.append('special_bundle_price_pickup_interior', formData.special_bundle_price_pickup_interior);
        formDataObj.append('special_bundle_price_delivery_interior', formData.special_bundle_price_delivery_interior);
        formDataObj.append('validity_type', formData.validity_type);
        if (formData.valid_from) formDataObj.append('valid_from', formData.valid_from);
        if (formData.valid_until) formDataObj.append('valid_until', formData.valid_until);
        if (formData.time_from) formDataObj.append('time_from', formData.time_from);
        if (formData.time_until) formDataObj.append('time_until', formData.time_until);

        const weekdaysValue = enableWeekdays && formData.weekdays.length > 0 ? formData.weekdays : null;
        if (weekdaysValue) {
            weekdaysValue.forEach((day, index) => {
                formDataObj.append(`weekdays[${index}]`, String(day));
            });
        }

        // Agregar badge type
        if (formData.badge_type_id) {
            formDataObj.append('badge_type_id', String(formData.badge_type_id));
            formDataObj.append('show_badge_on_menu', '1');
        }

        // Agregar items - Incluir IDs para preservar integridad referencial
        preparedItems.forEach((item, itemIndex) => {
            // Extraer ID numérico si existe (formato: item-123)
            const itemId = item.id?.toString().match(/^item-(\d+)$/)?.[1];
            if (itemId) {
                formDataObj.append(`items[${itemIndex}][id]`, itemId);
            }

            formDataObj.append(`items[${itemIndex}][is_choice_group]`, item.is_choice_group ? '1' : '0');
            formDataObj.append(`items[${itemIndex}][quantity]`, String(item.quantity));
            formDataObj.append(`items[${itemIndex}][sort_order]`, String(item.sort_order));
            if (item.choice_label) formDataObj.append(`items[${itemIndex}][choice_label]`, item.choice_label);
            if (item.product_id) formDataObj.append(`items[${itemIndex}][product_id]`, String(item.product_id));
            if (item.variant_id) formDataObj.append(`items[${itemIndex}][variant_id]`, String(item.variant_id));

            if (item.options) {
                item.options.forEach((option, optIndex) => {
                    // Extraer ID numérico de opción si existe (formato: option-123)
                    const optionId = option.id?.toString().match(/^option-(\d+)$/)?.[1];
                    if (optionId) {
                        formDataObj.append(`items[${itemIndex}][options][${optIndex}][id]`, optionId);
                    }

                    formDataObj.append(`items[${itemIndex}][options][${optIndex}][product_id]`, String(option.product_id));
                    if (option.variant_id) formDataObj.append(`items[${itemIndex}][options][${optIndex}][variant_id]`, String(option.variant_id));
                    formDataObj.append(`items[${itemIndex}][options][${optIndex}][sort_order]`, String(option.sort_order));
                });
            }
        });

        // Agregar imagen si existe
        if (image) {
            formDataObj.append('image', image);
        }

        router.post(route('menu.promotions.bundle-specials.update', combinado.id), formDataObj, {
            forceFormData: true,
            onSuccess: () => {
                // Redirección manejada por el controlador
            },
            onError: (errors) => {
                setErrors(errors as Record<string, string>);
                setIsSubmitting(false);
            },
        });
    };

    // Detect inactive products in both fixed items and choice groups
    const inactiveItems = useMemo(() => {
        const inactive: Array<{ type: 'fixed' | 'choice'; productName: string; groupLabel?: string }> = [];

        localItems.forEach((item) => {
            if (item.is_choice_group && item.options) {
                // Check choice group options
                item.options.forEach((option) => {
                    const product = products.find((p) => p.id === option.product_id);
                    if (product && !product.is_active) {
                        inactive.push({
                            type: 'choice',
                            productName: product.name,
                            groupLabel: item.choice_label || 'Grupo sin nombre',
                        });
                    }
                });
            } else if (item.product_id) {
                // Check fixed items
                const product = products.find((p) => p.id === item.product_id);
                if (product && !product.is_active) {
                    inactive.push({
                        type: 'fixed',
                        productName: product.name,
                    });
                }
            }
        });

        return inactive;
    }, [localItems, products]);

    // Check if combinado is expired
    const isExpired = useMemo(() => {
        if (!combinado.valid_until) return false;
        const now = new Date();
        const validUntil = new Date(combinado.valid_until + 'T23:59:59');
        return now > validUntil;
    }, [combinado.valid_until]);

    return (
        <EditPageLayout
            title="Editar Combinado"
            description={`Modifica los datos del combinado "${combinado.name}"`}
            backHref={route('menu.promotions.bundle-specials.index')}
            backLabel="Volver"
            onSubmit={handleSubmit}
            submitLabel="Guardar"
            processing={isSubmitting}
            pageTitle={`Editar ${combinado.name}`}
            loading={false}
            loadingSkeleton={EditProductsSkeleton}
        >
            <div className="space-y-8">
                {isExpired && (
                    <div className="rounded-lg border border-red-200 bg-red-50 p-4 dark:border-red-800 dark:bg-red-950/20">
                        <div className="flex gap-3">
                            <AlertCircle className="h-5 w-5 text-red-800 dark:text-red-200" />
                            <div className="flex-1">
                                <h3 className="font-semibold text-red-800 dark:text-red-200">Combinado Expirado</h3>
                                <p className="mt-1 text-sm text-red-800 dark:text-red-200">
                                    Este combinado ha superado su fecha de vigencia y no está disponible para los clientes.
                                </p>
                            </div>
                        </div>
                    </div>
                )}

                {inactiveItems.length > 0 && (
                    <div className="rounded-lg border border-amber-200 bg-amber-50 p-4 dark:border-amber-800 dark:bg-amber-950/20">
                        <div className="flex gap-3">
                            <AlertCircle className="h-5 w-5 text-amber-800 dark:text-amber-200" />
                            <div className="flex-1">
                                <h3 className="font-semibold text-amber-800 dark:text-amber-200">Productos Inactivos Detectados</h3>
                                <p className="mt-1 text-sm text-amber-800 dark:text-amber-200">
                                    Este combinado tiene productos inactivos que no estarán disponibles para los clientes:
                                </p>
                                <ul className="mt-2 list-inside list-disc space-y-1 text-sm text-amber-800 dark:text-amber-200">
                                    {inactiveItems.map((item, index) => (
                                        <li key={index}>
                                            <span className="font-medium">{item.productName}</span>
                                            {item.type === 'choice' && item.groupLabel && <> en "{item.groupLabel}"</>}
                                        </li>
                                    ))}
                                </ul>
                            </div>
                        </div>
                    </div>
                )}

                <Card>
                    <CardContent className="pt-6">
                        <FormSection icon={Gift} title="Información Básica" description="Datos principales del combinado">
                            <div className="space-y-6">
                                <div className="flex items-center justify-between rounded-lg border p-4">
                                    <Label htmlFor="is_active" className="text-base">
                                        Combinado activo
                                    </Label>
                                    <Switch
                                        id="is_active"
                                        checked={formData.is_active}
                                        onCheckedChange={(checked) => setFormData({ ...formData, is_active: checked as boolean })}
                                    />
                                </div>

                                <FormField label="Nombre" error={errors.name} required>
                                    <Input
                                        id="name"
                                        type="text"
                                        value={formData.name}
                                        onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                                    />
                                </FormField>

                                <FormField label="Descripción" error={errors.description}>
                                    <Textarea
                                        id="description"
                                        value={formData.description}
                                        onChange={(e) => setFormData({ ...formData, description: e.target.value })}
                                        rows={2}
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

                <Card>
                    <CardContent className="pt-6">
                        <FormSection icon={Banknote} title={FORM_SECTIONS.specialPrices.title} description={FORM_SECTIONS.specialPrices.description}>
                            <div className="space-y-6">
                                {/* Capital */}
                                <div className="space-y-3">
                                    <h4 className="text-sm font-medium text-muted-foreground">Zona Capital</h4>
                                    <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                                        <FormField label="Pickup Capital" error={errors.special_bundle_price_pickup_capital} required>
                                            <div className="relative">
                                                <span className="absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground">{CURRENCY.symbol}</span>
                                                <Input
                                                    id="special_bundle_price_pickup_capital"
                                                    type="number"
                                                    step="0.01"
                                                    min="0"
                                                    value={formData.special_bundle_price_pickup_capital}
                                                    onChange={(e) => setFormData({ ...formData, special_bundle_price_pickup_capital: e.target.value })}
                                                    placeholder={PLACEHOLDERS.price}
                                                    className="pl-8"
                                                />
                                            </div>
                                        </FormField>

                                        <FormField label="Delivery Capital" error={errors.special_bundle_price_delivery_capital} required>
                                            <div className="relative">
                                                <span className="absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground">{CURRENCY.symbol}</span>
                                                <Input
                                                    id="special_bundle_price_delivery_capital"
                                                    type="number"
                                                    step="0.01"
                                                    min="0"
                                                    value={formData.special_bundle_price_delivery_capital}
                                                    onChange={(e) => setFormData({ ...formData, special_bundle_price_delivery_capital: e.target.value })}
                                                    placeholder={PLACEHOLDERS.price}
                                                    className="pl-8"
                                                />
                                            </div>
                                        </FormField>
                                    </div>
                                </div>

                                {/* Interior */}
                                <div className="space-y-3">
                                    <h4 className="text-sm font-medium text-muted-foreground">Zona Interior</h4>
                                    <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                                        <FormField label="Pickup Interior" error={errors.special_bundle_price_pickup_interior} required>
                                            <div className="relative">
                                                <span className="absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground">{CURRENCY.symbol}</span>
                                                <Input
                                                    id="special_bundle_price_pickup_interior"
                                                    type="number"
                                                    step="0.01"
                                                    min="0"
                                                    value={formData.special_bundle_price_pickup_interior}
                                                    onChange={(e) => setFormData({ ...formData, special_bundle_price_pickup_interior: e.target.value })}
                                                    placeholder={PLACEHOLDERS.price}
                                                    className="pl-8"
                                                />
                                            </div>
                                        </FormField>

                                        <FormField label="Delivery Interior" error={errors.special_bundle_price_delivery_interior} required>
                                            <div className="relative">
                                                <span className="absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground">{CURRENCY.symbol}</span>
                                                <Input
                                                    id="special_bundle_price_delivery_interior"
                                                    type="number"
                                                    step="0.01"
                                                    min="0"
                                                    value={formData.special_bundle_price_delivery_interior}
                                                    onChange={(e) => setFormData({ ...formData, special_bundle_price_delivery_interior: e.target.value })}
                                                    placeholder={PLACEHOLDERS.price}
                                                    className="pl-8"
                                                />
                                            </div>
                                        </FormField>
                                    </div>
                                </div>
                            </div>
                        </FormSection>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent className="pt-6">
                        <FormSection
                            icon={Calendar}
                            title={FORM_SECTIONS.temporalValidity.title}
                            description={FORM_SECTIONS.temporalValidity.description}
                        >
                            <div className="space-y-4">
                                <FormField label="Vigencia" required error={errors.validity_type}>
                                    <Select value={formData.validity_type} onValueChange={(value) => setFormData({ ...formData, validity_type: value as typeof formData.validity_type })}>
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
                                            <Input type="date" value={formData.valid_from} onChange={(e) => setFormData({ ...formData, valid_from: e.target.value })} required />
                                        </FormField>

                                        <FormField label="Fecha Fin" required error={errors.valid_until}>
                                            <Input type="date" value={formData.valid_until} onChange={(e) => setFormData({ ...formData, valid_until: e.target.value })} required />
                                        </FormField>
                                    </div>
                                )}

                                {(formData.validity_type === 'time_range' || formData.validity_type === 'date_time_range') && (
                                    <div className="grid gap-4 sm:grid-cols-2">
                                        <FormField label="Hora Inicio" required error={errors.time_from}>
                                            <Input type="time" value={formData.time_from} onChange={(e) => setFormData({ ...formData, time_from: e.target.value })} required />
                                        </FormField>

                                        <FormField label="Hora Fin" required error={errors.time_until}>
                                            <Input type="time" value={formData.time_until} onChange={(e) => setFormData({ ...formData, time_until: e.target.value })} required />
                                        </FormField>
                                    </div>
                                )}

                                <div className="space-y-4">
                                    <div className="flex items-center justify-between rounded-lg border p-4">
                                        <Label htmlFor="enable_weekdays" className="text-base">
                                            Limitar por días de la semana
                                        </Label>
                                        <Switch id="enable_weekdays" checked={enableWeekdays} onCheckedChange={setEnableWeekdays} />
                                    </div>

                                    {enableWeekdays && (
                                        <div className="rounded-lg border p-4">
                                            <WeekdaySelector
                                                value={formData.weekdays}
                                                onChange={(days) => setFormData({ ...formData, weekdays: days })}
                                                error={errors.weekdays}
                                            />
                                        </div>
                                    )}
                                </div>
                            </div>
                        </FormSection>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent className="pt-6">
                        <FormSection icon={Package} title={FORM_SECTIONS.combinadoItems.title} description={FORM_SECTIONS.combinadoItems.description}>
                            <div className="space-y-4">
                                <div className="flex items-center justify-between rounded-lg border border-muted bg-muted/50 px-4 py-2">
                                    <p className="text-sm text-muted-foreground">Un combinado debe tener al menos 2 items</p>
                                    <span className="text-xs font-medium text-muted-foreground">Actual: {localItems.length}</span>
                                </div>

                                {localItems.length > 0 ? (
                                    <DndContext sensors={sensors} collisionDetection={closestCenter} onDragEnd={handleDragEnd}>
                                        <SortableContext items={localItems.map((item) => item.id)} strategy={verticalListSortingStrategy}>
                                            <div className="space-y-4">
                                                {localItems.map((item, index) => (
                                                    <ComboItemCard
                                                        key={item.id}
                                                        item={item}
                                                        index={index}
                                                        products={products}
                                                        onUpdate={(field, value) => updateItem(index, field, value)}
                                                        onBatchUpdate={(updates) => batchUpdateItem(index, updates)}
                                                        onRemove={() => removeItem(index)}
                                                        errors={errors}
                                                        canDelete={true}
                                                    />
                                                ))}
                                            </div>
                                        </SortableContext>
                                    </DndContext>
                                ) : (
                                    <div className="rounded-lg border border-dashed border-muted-foreground/25 p-8 text-center">
                                        <p className="text-sm text-muted-foreground">No hay items en el combinado</p>
                                        <p className="mt-1 text-xs text-muted-foreground">Agrega al menos 2 items para el combinado</p>
                                    </div>
                                )}

                                <Button type="button" variant="outline" onClick={addItem} className="w-full">
                                    <Plus className="mr-2 h-4 w-4" />
                                    Agregar Item
                                </Button>

                                {errors.items && <p className="mt-2 text-sm text-destructive">{errors.items}</p>}
                            </div>
                        </FormSection>
                    </CardContent>
                </Card>
            </div>
        </EditPageLayout>
    );
}
