import { router } from '@inertiajs/react';
import { useState } from 'react';

import { EditPageLayout } from '@/components/edit-page-layout';
import { FormSection } from '@/components/form-section';
import { EditProductsSkeleton } from '@/components/skeletons';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { FormField } from '@/components/ui/form-field';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { ENTITY_ICONS } from '@/constants/section-icons';
import { Calendar, Banknote, Layers, Package, Percent, Plus, Star, Tag, Trash2 } from 'lucide-react';

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

interface PromotionItem {
    id?: number;
    product_id?: number | null;
    category_id?: number | null;
}

interface Promotion {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    type: 'two_for_one' | 'percentage_discount';
    discount_value: number | null;
    applies_to: 'product' | 'category';
    is_permanent: boolean;
    valid_from: string | null;
    valid_until: string | null;
    has_time_restriction: boolean;
    time_from: string | null;
    time_until: string | null;
    active_days: number[] | null;
    is_active: boolean;
    items: PromotionItem[];
}

interface EditPromotionPageProps {
    promotion: Promotion;
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

interface FormData {
    name: string;
    slug: string;
    description: string;
    type: 'two_for_one' | 'percentage_discount';
    discount_value: string | number;
    applies_to: 'product' | 'category';
    is_permanent: boolean;
    valid_from: string;
    valid_until: string;
    has_time_restriction: boolean;
    time_from: string;
    time_until: string;
    active_days: number[];
    is_active: boolean;
    items: PromotionItem[];
}

/**
 * Página para editar una promoción
 */
export default function PromotionEdit({ promotion, products, categories }: EditPromotionPageProps) {
    const [formData, setFormData] = useState<FormData>({
        name: promotion.name,
        slug: promotion.slug,
        description: promotion.description || '',
        type: promotion.type,
        discount_value: promotion.discount_value || '',
        applies_to: promotion.applies_to,
        is_permanent: promotion.is_permanent,
        valid_from: promotion.valid_from || '',
        valid_until: promotion.valid_until || '',
        has_time_restriction: promotion.has_time_restriction,
        time_from: promotion.time_from || '',
        time_until: promotion.time_until || '',
        active_days: promotion.active_days || [],
        is_active: promotion.is_active,
        items: promotion.items.map(item => ({
            id: item.id,
            product_id: item.product_id,
            category_id: item.category_id,
        })),
    });
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [isSubmitting, setIsSubmitting] = useState(false);

    const handleInputChange = (field: keyof FormData, value: any) => {
        setFormData((prev) => ({
            ...prev,
            [field]: value,
        }));

        if (errors[field]) {
            setErrors((prev) => {
                const newErrors = { ...prev };
                delete newErrors[field];
                return newErrors;
            });
        }
    };

    const toggleDay = (day: number) => {
        handleInputChange('active_days',
            formData.active_days.includes(day)
                ? formData.active_days.filter(d => d !== day)
                : [...formData.active_days, day]
        );
    };

    const addItem = () => {
        handleInputChange('items', [
            ...formData.items,
            {
                product_id: null,
                category_id: null,
            },
        ]);
    };

    const removeItem = (index: number) => {
        handleInputChange('items', formData.items.filter((_, i) => i !== index));
    };

    const updateItem = (index: number, field: keyof PromotionItem, value: any) => {
        const newItems = [...formData.items];
        newItems[index] = { ...newItems[index], [field]: value };
        handleInputChange('items', newItems);
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        setIsSubmitting(true);

        const submitData = {
            ...formData,
            discount_value: formData.discount_value ? parseFloat(formData.discount_value.toString()) : null,
            active_days: formData.active_days.length > 0 ? formData.active_days : null,
        };

        router.put(`/menu/promotions/${promotion.id}`, submitData, {
            onSuccess: () => {
                // La redirección la maneja el controlador
            },
            onError: (errors) => {
                setErrors(errors as Record<string, string>);
                setIsSubmitting(false);
            },
        });
    };

    return (
        <EditPageLayout
            title="Editar Promoción"
            description={`Modifica los datos de la promoción "${promotion.name}"`}
            backHref={route('menu.promotions.index')}
            backLabel="Volver a Promociones"
            onSubmit={handleSubmit}
            submitLabel="Guardar Cambios"
            processing={isSubmitting}
            pageTitle={`Editar ${promotion.name}`}
            loading={false}
            loadingSkeleton={EditProductsSkeleton}
        >
            <FormSection icon={Tag} title="Información de la Promoción">
                <FormField label="Nombre" error={errors.name} required>
                    <Input
                        id="name"
                        type="text"
                        value={formData.name}
                        onChange={(e) => handleInputChange('name', e.target.value)}
                        placeholder="ej: Sub del Día Lunes, 20% OFF en Combos, 2x1 en Cookies"
                    />
                </FormField>

                <FormField label="Slug" error={errors.slug}>
                    <Input
                        id="slug"
                        type="text"
                        value={formData.slug}
                        onChange={(e) => handleInputChange('slug', e.target.value)}
                        placeholder="Se genera automáticamente si se deja vacío"
                    />
                </FormField>

                <FormField label="Descripción" error={errors.description}>
                    <Textarea
                        id="description"
                        value={formData.description}
                        onChange={(e) => handleInputChange('description', e.target.value)}
                        placeholder="Descripción de la promoción"
                        rows={3}
                    />
                </FormField>
            </FormSection>

            <FormSection icon={Percent} title="Tipo de Promoción">
                <FormField label="Tipo de promoción" error={errors.type} required>
                    <Select value={formData.type} onValueChange={(value: any) => handleInputChange('type', value)}>
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

                {formData.type === 'percentage_discount' && (
                    <FormField label="Porcentaje de descuento" error={errors.discount_value} required>
                        <div className="relative">
                            <Input
                                id="discount_value"
                                type="number"
                                min="0"
                                max="100"
                                step="0.01"
                                value={formData.discount_value}
                                onChange={(e) => handleInputChange('discount_value', e.target.value)}
                                className="pr-8"
                                placeholder="0"
                            />
                            <span className="absolute top-3 right-3 text-sm text-muted-foreground">%</span>
                        </div>
                    </FormField>
                )}

                <FormField label="Se aplica a" error={errors.applies_to} required>
                    <Select value={formData.applies_to} onValueChange={(value: any) => handleInputChange('applies_to', value)}>
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
                        checked={formData.is_permanent}
                        onCheckedChange={(checked) => handleInputChange('is_permanent', checked as boolean)}
                    />
                    <Label htmlFor="is_permanent" className="text-sm leading-none font-medium peer-disabled:cursor-not-allowed peer-disabled:opacity-70">
                        Promoción permanente (sin fecha de expiración)
                    </Label>
                </div>

                {!formData.is_permanent && (
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <FormField label="Fecha inicio" error={errors.valid_from}>
                            <Input
                                id="valid_from"
                                type="date"
                                value={formData.valid_from}
                                onChange={(e) => handleInputChange('valid_from', e.target.value)}
                            />
                        </FormField>

                        <FormField label="Fecha fin" error={errors.valid_until}>
                            <Input
                                id="valid_until"
                                type="date"
                                value={formData.valid_until}
                                onChange={(e) => handleInputChange('valid_until', e.target.value)}
                            />
                        </FormField>
                    </div>
                )}
            </FormSection>

            <FormSection icon={Calendar} title="Restricciones de Tiempo">
                <div className="flex items-center space-x-2 mb-4">
                    <Checkbox
                        id="has_time_restriction"
                        checked={formData.has_time_restriction}
                        onCheckedChange={(checked) => handleInputChange('has_time_restriction', checked as boolean)}
                    />
                    <Label htmlFor="has_time_restriction" className="text-sm leading-none font-medium peer-disabled:cursor-not-allowed peer-disabled:opacity-70">
                        Aplicar restricción horaria
                    </Label>
                </div>

                {formData.has_time_restriction && (
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <FormField label="Hora inicio" error={errors.time_from}>
                            <Input
                                id="time_from"
                                type="time"
                                value={formData.time_from}
                                onChange={(e) => handleInputChange('time_from', e.target.value)}
                            />
                        </FormField>

                        <FormField label="Hora fin" error={errors.time_until}>
                            <Input
                                id="time_until"
                                type="time"
                                value={formData.time_until}
                                onChange={(e) => handleInputChange('time_until', e.target.value)}
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
                                variant={formData.active_days.includes(day.value) ? 'default' : 'outline'}
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
                    Agrega los {formData.applies_to === 'product' ? 'productos' : 'categorías'} a los que aplica esta promoción.
                </p>

                {formData.items.length === 0 && (
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
                    {formData.items.map((item, index) => (
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

                            {formData.applies_to === 'product' && (
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

                            {formData.applies_to === 'category' && (
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

                {formData.items.length > 0 && (
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
                        checked={formData.is_active}
                        onCheckedChange={(checked) => handleInputChange('is_active', checked as boolean)}
                    />
                    <Label htmlFor="is_active" className="text-sm leading-none font-medium peer-disabled:cursor-not-allowed peer-disabled:opacity-70">
                        Promoción activa
                    </Label>
                </div>
                <p className="text-sm text-muted-foreground mt-2">
                    Solo las promociones activas se aplicarán en el cálculo de precios.
                </p>
            </FormSection>
        </EditPageLayout>
    );
}
