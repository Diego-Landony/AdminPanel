import { showNotification } from '@/hooks/useNotifications';
import { useForm } from '@inertiajs/react';
import { useState } from 'react';

import { CreatePageLayout } from '@/components/create-page-layout';
import { FormSection } from '@/components/form-section';
import { CreateProductsSkeleton } from '@/components/skeletons';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { FormField } from '@/components/ui/form-field';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { ENTITY_ICONS } from '@/constants/section-icons';
import { NOTIFICATIONS } from '@/constants/ui-constants';
import { Calendar, Banknote, Layers, Package, Percent, Plus, Star, Tag, Trash2, X } from 'lucide-react';

interface Category {
    id: number;
    name: string;
    slug: string;
}

interface Product {
    id: number;
    name: string;
    slug: string;
}

interface CreatePromotionPageProps {
    products: Product[];
    categories: Category[];
}

const DAYS_OF_WEEK = [
    { value: 0, label: 'Domingo' },
    { value: 1, label: 'Lunes' },
    { value: 2, label: 'Martes' },
    { value: 3, label: 'Miércoles' },
    { value: 4, label: 'Jueves' },
    { value: 5, label: 'Viernes' },
    { value: 6, label: 'Sábado' },
];

interface PromotionItem {
    product_id?: number | null;
    category_id?: number | null;
}

/**
 * Página para crear una promoción
 */
export default function PromotionCreate({ products, categories }: CreatePromotionPageProps) {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        slug: '',
        description: '',
        type: 'two_for_one' as 'two_for_one' | 'percentage_discount',
        discount_value: '',
        applies_to: 'product' as 'product' | 'category',
        is_permanent: true,
        valid_from: '',
        valid_until: '',
        has_time_restriction: false,
        time_from: '',
        time_until: '',
        active_days: [] as number[],
        is_active: true,
        items: [] as PromotionItem[],
    });

    const toggleDay = (day: number) => {
        setData('active_days',
            data.active_days.includes(day)
                ? data.active_days.filter(d => d !== day)
                : [...data.active_days, day]
        );
    };

    const addItem = () => {
        setData('items', [
            ...data.items,
            {
                product_id: null,
                category_id: null,
            },
        ]);
    };

    const removeItem = (index: number) => {
        setData('items', data.items.filter((_, i) => i !== index));
    };

    const updateItem = (index: number, field: keyof PromotionItem, value: any) => {
        const newItems = [...data.items];
        newItems[index] = { ...newItems[index], [field]: value };
        setData('items', newItems);
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        const submitData = {
            ...data,
            discount_value: data.discount_value ? parseFloat(data.discount_value) : null,
            active_days: data.active_days.length > 0 ? data.active_days : null,
        };

        post(route('menu.promotions.store'), {
            data: submitData,
            onError: (errors) => {
                if (Object.keys(errors).length === 0) {
                    showNotification.error(NOTIFICATIONS.error.server);
                }
            },
        });
    };

    return (
        <CreatePageLayout
            title="Nueva Promoción"
            backHref={route('menu.promotions.index')}
            backLabel="Volver a Promociones"
            onSubmit={handleSubmit}
            submitLabel="Crear Promoción"
            processing={processing}
            pageTitle="Crear Promoción"
            loading={processing}
            loadingSkeleton={CreateProductsSkeleton}
        >
            <FormSection icon={Tag} title="Información de la Promoción">
                <FormField label="Nombre" error={errors.name} required>
                    <Input
                        id="name"
                        type="text"
                        value={data.name}
                        onChange={(e) => setData('name', e.target.value)}
                        placeholder="ej: Sub del Día Lunes, 20% OFF en Combos, 2x1 en Cookies"
                    />
                </FormField>

                <FormField label="Slug" error={errors.slug}>
                    <Input
                        id="slug"
                        type="text"
                        value={data.slug}
                        onChange={(e) => setData('slug', e.target.value)}
                        placeholder="Se genera automáticamente si se deja vacío"
                    />
                </FormField>

                <FormField label="Descripción" error={errors.description}>
                    <Textarea
                        id="description"
                        value={data.description}
                        onChange={(e) => setData('description', e.target.value)}
                        placeholder="Descripción de la promoción"
                        rows={3}
                    />
                </FormField>
            </FormSection>

            <FormSection icon={Percent} title="Tipo de Promoción">
                <FormField label="Tipo de promoción" error={errors.type} required>
                    <Select value={data.type} onValueChange={(value: any) => setData('type', value)}>
                        <SelectTrigger>
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="percentage_discount">
                                <div className="flex items-center gap-2">
                                    <Percent className="h-4 w-4" />
                                    Descuento Porcentual
                                </div>
                            </SelectItem>
                            <SelectItem value="two_for_one">
                                <div className="flex items-center gap-2">
                                    <Star className="h-4 w-4" />
                                    2x1 (Compra 2, Paga 1)
                                </div>
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </FormField>

                {data.type === 'percentage_discount' && (
                    <FormField label="Porcentaje de descuento" error={errors.discount_value} required>
                        <div className="relative">
                            <Input
                                id="discount_value"
                                type="number"
                                min="0"
                                max="100"
                                step="0.01"
                                value={data.discount_value}
                                onChange={(e) => setData('discount_value', e.target.value)}
                                className="pr-8"
                                placeholder="0"
                            />
                            <span className="absolute top-3 right-3 text-sm text-muted-foreground">%</span>
                        </div>
                    </FormField>
                )}

                <FormField label="Se aplica a" error={errors.applies_to} required>
                    <Select value={data.applies_to} onValueChange={(value: any) => setData('applies_to', value)}>
                        <SelectTrigger>
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="product">
                                <div className="flex items-center gap-2">
                                    <Package className="h-4 w-4" />
                                    Producto
                                </div>
                            </SelectItem>
                            <SelectItem value="category">
                                <div className="flex items-center gap-2">
                                    <Layers className="h-4 w-4" />
                                    Categoría
                                </div>
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </FormField>
            </FormSection>

            <FormSection icon={Calendar} title="Período de Vigencia">
                <div className="flex items-center space-x-2 mb-4">
                    <Checkbox
                        id="is_permanent"
                        checked={data.is_permanent}
                        onCheckedChange={(checked) => setData('is_permanent', checked as boolean)}
                    />
                    <Label htmlFor="is_permanent" className="text-sm leading-none font-medium peer-disabled:cursor-not-allowed peer-disabled:opacity-70">
                        Promoción permanente (sin fecha de expiración)
                    </Label>
                </div>

                {!data.is_permanent && (
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <FormField label="Fecha inicio" error={errors.valid_from}>
                            <Input
                                id="valid_from"
                                type="date"
                                value={data.valid_from}
                                onChange={(e) => setData('valid_from', e.target.value)}
                            />
                        </FormField>

                        <FormField label="Fecha fin" error={errors.valid_until}>
                            <Input
                                id="valid_until"
                                type="date"
                                value={data.valid_until}
                                onChange={(e) => setData('valid_until', e.target.value)}
                            />
                        </FormField>
                    </div>
                )}
            </FormSection>

            <FormSection icon={Calendar} title="Restricciones de Tiempo">
                <div className="flex items-center space-x-2 mb-4">
                    <Checkbox
                        id="has_time_restriction"
                        checked={data.has_time_restriction}
                        onCheckedChange={(checked) => setData('has_time_restriction', checked as boolean)}
                    />
                    <Label htmlFor="has_time_restriction" className="text-sm leading-none font-medium peer-disabled:cursor-not-allowed peer-disabled:opacity-70">
                        Aplicar restricción horaria
                    </Label>
                </div>

                {data.has_time_restriction && (
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <FormField label="Hora inicio" error={errors.time_from}>
                            <Input
                                id="time_from"
                                type="time"
                                value={data.time_from}
                                onChange={(e) => setData('time_from', e.target.value)}
                            />
                        </FormField>

                        <FormField label="Hora fin" error={errors.time_until}>
                            <Input
                                id="time_until"
                                type="time"
                                value={data.time_until}
                                onChange={(e) => setData('time_until', e.target.value)}
                            />
                        </FormField>
                    </div>
                )}

                <FormField label="Días activos de la semana" error={errors.active_days}>
                    <div className="flex flex-wrap gap-2">
                        {DAYS_OF_WEEK.map((day) => (
                            <Button
                                key={day.value}
                                type="button"
                                variant={data.active_days.includes(day.value) ? 'default' : 'outline'}
                                size="sm"
                                onClick={() => toggleDay(day.value)}
                            >
                                {day.label}
                            </Button>
                        ))}
                    </div>
                    <p className="text-xs text-muted-foreground mt-2">
                        Si no seleccionas ningún día, la promoción aplicará todos los días
                    </p>
                </FormField>
            </FormSection>

            <FormSection icon={Package} title="Items de la Promoción">
                <p className="text-sm text-muted-foreground mb-4">
                    Agrega los {data.applies_to === 'product' ? 'productos' : 'categorías'} a los que aplica esta promoción.
                </p>

                {data.items.length === 0 && (
                    <div className="text-center py-8 border-2 border-dashed border-border rounded-lg">
                        <Package className="h-8 w-8 text-muted-foreground mx-auto mb-2" />
                        <p className="text-sm text-muted-foreground mb-4">No hay items agregados</p>
                        <Button type="button" onClick={addItem} size="sm">
                            <Plus className="h-4 w-4 mr-2" />
                            Agregar Item
                        </Button>
                    </div>
                )}

                <div className="space-y-4">
                    {data.items.map((item, index) => (
                        <div key={index} className="p-4 border border-border rounded-lg">
                            <div className="flex items-start justify-between mb-4">
                                <h4 className="text-sm font-medium">Item {index + 1}</h4>
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="sm"
                                    onClick={() => removeItem(index)}
                                >
                                    <Trash2 className="h-4 w-4" />
                                </Button>
                            </div>

                            {data.applies_to === 'product' && (
                                <FormField label="Producto" error={errors[`items.${index}.product_id`]} required>
                                    <Select
                                        value={item.product_id?.toString() || ''}
                                        onValueChange={(value) => updateItem(index, 'product_id', parseInt(value))}
                                    >
                                        <SelectTrigger>
                                            <SelectValue placeholder="Selecciona un producto" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {products.map((product) => (
                                                <SelectItem key={product.id} value={product.id.toString()}>
                                                    {product.name}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </FormField>
                            )}

                            {data.applies_to === 'category' && (
                                <FormField label="Categoría" error={errors[`items.${index}.category_id`]} required>
                                    <Select
                                        value={item.category_id?.toString() || ''}
                                        onValueChange={(value) => updateItem(index, 'category_id', parseInt(value))}
                                    >
                                        <SelectTrigger>
                                            <SelectValue placeholder="Selecciona una categoría" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {categories.map((category) => (
                                                <SelectItem key={category.id} value={category.id.toString()}>
                                                    {category.name}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </FormField>
                            )}
                        </div>
                    ))}
                </div>

                {data.items.length > 0 && (
                    <Button type="button" onClick={addItem} variant="outline" size="sm" className="mt-4">
                        <Plus className="h-4 w-4 mr-2" />
                        Agregar Otro Item
                    </Button>
                )}
            </FormSection>

            <FormSection icon={ENTITY_ICONS.menu.productInfo} title="Configuración">
                <div className="flex items-center space-x-2">
                    <Checkbox
                        id="is_active"
                        checked={data.is_active}
                        onCheckedChange={(checked) => setData('is_active', checked as boolean)}
                    />
                    <Label htmlFor="is_active" className="text-sm leading-none font-medium peer-disabled:cursor-not-allowed peer-disabled:opacity-70">
                        Promoción activa
                    </Label>
                </div>
                <p className="text-sm text-muted-foreground mt-2">
                    Solo las promociones activas se aplicarán en el cálculo de precios.
                </p>
            </FormSection>
        </CreatePageLayout>
    );
}
