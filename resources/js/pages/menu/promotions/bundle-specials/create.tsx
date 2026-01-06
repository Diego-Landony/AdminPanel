import { showNotification } from '@/hooks/useNotifications';
import { closestCenter, DndContext, DragEndEvent, KeyboardSensor, PointerSensor, useSensor, useSensors } from '@dnd-kit/core';
import { arrayMove, SortableContext, sortableKeyboardCoordinates, verticalListSortingStrategy } from '@dnd-kit/sortable';
import { router } from '@inertiajs/react';
import { useMemo, useState } from 'react';

import { ComboItemCard } from '@/components/combos/ComboItemCard';
import { CreatePageLayout } from '@/components/create-page-layout';
import { FormSection } from '@/components/form-section';
import { ImageUpload } from '@/components/ImageUpload';
import { CreateProductsSkeleton } from '@/components/skeletons';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { FormField } from '@/components/ui/form-field';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Switch } from '@/components/ui/switch';
import { Textarea } from '@/components/ui/textarea';
import { WeekdaySelector } from '@/components/WeekdaySelector';
import { CURRENCY, FORM_SECTIONS, NOTIFICATIONS, PLACEHOLDERS } from '@/constants/ui-constants';
import { generateUniqueItemId, prepareComboDataForSubmit, validateMinimumComboStructure } from '@/utils/comboHelpers';
import { Banknote, Calendar, Gift, Image, Package, Plus } from 'lucide-react';

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
    id: string;
    product_id: number;
    variant_id?: number | null;
    sort_order: number;
}

interface ComboItem {
    id: string;
    is_choice_group: boolean;
    choice_label?: string;
    product_id?: number | null;
    variant_id?: number | null;
    quantity: number;
    sort_order: number;
    options?: ChoiceOption[];
}

interface CreateBundleSpecialPageProps {
    products: Product[];
}

interface BundleSpecialFormData {
    name: string;
    description: string;
    is_active: boolean;
    special_bundle_price_capital: string;
    special_bundle_price_interior: string;
    validity_type: 'permanent' | 'date_range' | 'time_range' | 'date_time_range';
    valid_from: string;
    valid_until: string;
    time_from: string;
    time_until: string;
    weekdays: number[];
    items: ComboItem[];
}

export default function BundleSpecialCreate({ products }: CreateBundleSpecialPageProps) {
    const [processing, setProcessing] = useState(false);
    const [errors, setErrors] = useState<Record<string, string>>({});

    const [data, setDataState] = useState<BundleSpecialFormData>({
        name: '',
        description: '',
        is_active: true,
        special_bundle_price_capital: '',
        special_bundle_price_interior: '',
        validity_type: 'permanent',
        valid_from: '',
        valid_until: '',
        time_from: '',
        time_until: '',
        weekdays: [],
        items: [],
    });

    const [image, setImage] = useState<File | null>(null);

    const setData = <K extends keyof BundleSpecialFormData>(key: K, value: BundleSpecialFormData[K]) => {
        setDataState((prev) => ({ ...prev, [key]: value }));
        if (errors[key]) {
            setErrors((prev) => {
                const newErrors = { ...prev };
                delete newErrors[key];
                return newErrors;
            });
        }
    };

    const [localItems, setLocalItems] = useState<ComboItem[]>([]);
    const [enableWeekdays, setEnableWeekdays] = useState(false);

    const hasInactiveProducts = useMemo(() => {
        return localItems.some((item) => {
            if (item.is_choice_group && item.options) {
                return item.options.some((option) => {
                    const product = products.find((p) => p.id === option.product_id);
                    return product && !product.is_active;
                });
            } else if (item.product_id) {
                const product = products.find((p) => p.id === item.product_id);
                return product && !product.is_active;
            }
            return false;
        });
    }, [localItems, products]);

    const sensors = useSensors(
        useSensor(PointerSensor),
        useSensor(KeyboardSensor, {
            coordinateGetter: sortableKeyboardCoordinates,
        }),
    );

    const addItem = () => {
        const newItem: ComboItem = {
            id: generateUniqueItemId(),
            is_choice_group: false,
            product_id: null,
            variant_id: null,
            quantity: 1,
            sort_order: localItems.length + 1,
            options: [],
        };
        const updated = [...localItems, newItem];
        setLocalItems(updated);
        setData('items', updated);
    };

    const removeItem = (index: number) => {
        const updated = localItems.filter((_, i) => i !== index);
        setLocalItems(updated);
        setData('items', updated);
    };

    const updateItem = (index: number, field: string, value: string | number | boolean | ChoiceOption[] | null) => {
        const updated = [...localItems];
        updated[index] = { ...updated[index], [field]: value };
        setLocalItems(updated);
        setData('items', updated);
    };

    const batchUpdateItem = (index: number, updates: Partial<ComboItem>) => {
        const updated = [...localItems];
        updated[index] = { ...updated[index], ...updates };
        setLocalItems(updated);
        setData('items', updated);
    };

    const handleDragEnd = (event: DragEndEvent) => {
        const { active, over } = event;

        if (over && active.id !== over.id) {
            setLocalItems((items) => {
                const oldIndex = items.findIndex((item) => item.id === active.id);
                const newIndex = items.findIndex((item) => item.id === over.id);

                const newItems = arrayMove(items, oldIndex, newIndex);
                setData('items', newItems);
                return newItems;
            });
        }
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        const validation = validateMinimumComboStructure(localItems);

        if (!validation.valid) {
            showNotification.error(validation.errors[0]);
            return;
        }

        setProcessing(true);

        const preparedItems = prepareComboDataForSubmit(localItems);

        const formData = new FormData();
        formData.append('name', data.name);
        formData.append('description', data.description || '');
        formData.append('is_active', data.is_active ? '1' : '0');
        formData.append('special_bundle_price_capital', data.special_bundle_price_capital);
        formData.append('special_bundle_price_interior', data.special_bundle_price_interior);
        formData.append('validity_type', data.validity_type);
        if (data.valid_from) formData.append('valid_from', data.valid_from);
        if (data.valid_until) formData.append('valid_until', data.valid_until);
        if (data.time_from) formData.append('time_from', data.time_from);
        if (data.time_until) formData.append('time_until', data.time_until);

        const weekdaysValue = enableWeekdays && data.weekdays.length > 0 ? data.weekdays : null;
        if (weekdaysValue) {
            weekdaysValue.forEach((day, index) => {
                formData.append(`weekdays[${index}]`, String(day));
            });
        }

        // Agregar items como JSON
        preparedItems.forEach((item, itemIndex) => {
            formData.append(`items[${itemIndex}][is_choice_group]`, item.is_choice_group ? '1' : '0');
            formData.append(`items[${itemIndex}][quantity]`, String(item.quantity));
            formData.append(`items[${itemIndex}][sort_order]`, String(item.sort_order));
            if (item.choice_label) formData.append(`items[${itemIndex}][choice_label]`, item.choice_label);
            if (item.product_id) formData.append(`items[${itemIndex}][product_id]`, String(item.product_id));
            if (item.variant_id) formData.append(`items[${itemIndex}][variant_id]`, String(item.variant_id));

            if (item.options) {
                item.options.forEach((option, optIndex) => {
                    formData.append(`items[${itemIndex}][options][${optIndex}][product_id]`, String(option.product_id));
                    if (option.variant_id) formData.append(`items[${itemIndex}][options][${optIndex}][variant_id]`, String(option.variant_id));
                    formData.append(`items[${itemIndex}][options][${optIndex}][sort_order]`, String(option.sort_order));
                });
            }
        });

        // Agregar imagen si existe
        if (image) {
            formData.append('image', image);
        }

        router.post(route('menu.promotions.bundle-specials.store'), formData, {
            forceFormData: true,
            onSuccess: () => {
                setLocalItems([]);
            },
            onError: (newErrors: Record<string, string>) => {
                setErrors(newErrors);
                console.error('Errores de validación:', newErrors);

                if (Object.keys(newErrors).length === 0) {
                    showNotification.error(NOTIFICATIONS.error.dataLoading);
                } else {
                    const firstErrorKey = Object.keys(newErrors)[0];
                    const firstError = newErrors[firstErrorKey];
                    showNotification.error(firstError);
                }
            },
            onFinish: () => {
                setProcessing(false);
            },
        });
    };

    return (
        <CreatePageLayout
            title="Nuevo Combinado"
            description="Crea una oferta especial temporal con vigencia limitada"
            backHref={route('menu.promotions.bundle-specials.index')}
            backLabel="Volver"
            onSubmit={handleSubmit}
            submitLabel="Guardar"
            processing={processing}
            pageTitle="Crear Combinado"
            loading={processing}
            loadingSkeleton={CreateProductsSkeleton}
        >
            <div className="space-y-8">
                <Card>
                    <CardContent className="pt-6">
                        <FormSection icon={Gift} title="Información Básica" description="Datos principales del combinado">
                            <div className="space-y-6">
                                <div className="flex items-center justify-between rounded-lg border p-4">
                                    <Label htmlFor="is_active" className="text-base">
                                        Combinado activo
                                    </Label>
                                    <Switch id="is_active" checked={data.is_active} onCheckedChange={(checked) => setData('is_active', checked as boolean)} />
                                </div>

                                <FormField label="Nombre" error={errors.name} required>
                                    <Input
                                        id="name"
                                        type="text"
                                        value={data.name}
                                        onChange={(e) => setData('name', e.target.value)}
                                    />
                                </FormField>

                                <FormField label="Descripción" error={errors.description}>
                                    <Textarea
                                        id="description"
                                        value={data.description}
                                        onChange={(e) => setData('description', e.target.value)}
                                        rows={2}
                                    />
                                </FormField>
                            </div>
                        </FormSection>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent className="pt-6">
                        <FormSection icon={Image} title="Imagen de la Promoción" description="Imagen que se mostrará en la app">
                            <ImageUpload
                                label="Imagen"
                                onImageChange={(file) => setImage(file)}
                                error={errors.image}
                            />
                        </FormSection>
                    </CardContent>
                </Card>

                {hasInactiveProducts && (
                    <div className="rounded-lg border border-amber-200 bg-amber-50 p-4 dark:border-amber-800 dark:bg-amber-950/20">
                        <p className="text-sm font-medium text-amber-800 dark:text-amber-200">
                            ⚠ Advertencia: Este combinado tiene productos inactivos seleccionados. El combinado no estará disponible para los clientes hasta
                            que se activen todos los productos.
                        </p>
                    </div>
                )}

                <Card>
                    <CardContent className="pt-6">
                        <FormSection icon={Banknote} title={FORM_SECTIONS.specialPrices.title} description={FORM_SECTIONS.specialPrices.description}>
                            <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <FormField label="Precio Capital" error={errors.special_bundle_price_capital} required>
                                    <div className="relative">
                                        <span className="absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground">{CURRENCY.symbol}</span>
                                        <Input
                                            id="special_bundle_price_capital"
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            value={data.special_bundle_price_capital}
                                            onChange={(e) => setData('special_bundle_price_capital', e.target.value)}
                                            placeholder={PLACEHOLDERS.price}
                                            className="pl-8"
                                        />
                                    </div>
                                </FormField>

                                <FormField label="Precio Interior" error={errors.special_bundle_price_interior} required>
                                    <div className="relative">
                                        <span className="absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground">{CURRENCY.symbol}</span>
                                        <Input
                                            id="special_bundle_price_interior"
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            value={data.special_bundle_price_interior}
                                            onChange={(e) => setData('special_bundle_price_interior', e.target.value)}
                                            placeholder={PLACEHOLDERS.price}
                                            className="pl-8"
                                        />
                                    </div>
                                </FormField>
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

                                <div className="space-y-4">
                                    <div className="flex items-center justify-between rounded-lg border p-4">
                                        <Label htmlFor="enable_weekdays" className="text-base">
                                            Limitar por días de la semana
                                        </Label>
                                        <Switch id="enable_weekdays" checked={enableWeekdays} onCheckedChange={setEnableWeekdays} />
                                    </div>

                                    {enableWeekdays && (
                                        <div className="rounded-lg border p-4">
                                            <WeekdaySelector value={data.weekdays} onChange={(days) => setData('weekdays', days)} error={errors.weekdays} />
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
                                        <p className="mt-1 text-xs text-muted-foreground">Agrega al menos 2 items para crear el combinado</p>
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
        </CreatePageLayout>
    );
}
