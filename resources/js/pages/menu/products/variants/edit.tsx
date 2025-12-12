import { PLACEHOLDERS } from '@/constants/ui-constants';
import { router } from '@inertiajs/react';
import { useState } from 'react';

import { EditPageLayout } from '@/components/edit-page-layout';
import { FormSection } from '@/components/form-section';
import { EditProductsSkeleton } from '@/components/skeletons';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Switch } from '@/components/ui/switch';
import { FormField } from '@/components/ui/form-field';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import { Banknote, Calendar, Package, Star } from 'lucide-react';

interface Product {
    id: number;
    name: string;
}

interface ProductVariant {
    id: number;
    product_id: number;
    sku: string;
    name: string;
    size: string;
    precio_pickup_capital: number;
    precio_domicilio_capital: number;
    precio_pickup_interior: number;
    precio_domicilio_interior: number;
    is_daily_special: boolean;
    daily_special_days: number[] | null;
    daily_special_precio_pickup_capital: number | null;
    daily_special_precio_domicilio_capital: number | null;
    daily_special_precio_pickup_interior: number | null;
    daily_special_precio_domicilio_interior: number | null;
    is_active: boolean;
}

interface EditVariantPageProps {
    product: Product;
    variant: ProductVariant;
    daysOfWeek: Array<{ value: number; label: string }>;
}

interface FormData {
    // Precios regulares
    precio_pickup_capital: string | number;
    precio_domicilio_capital: string | number;
    precio_pickup_interior: string | number;
    precio_domicilio_interior: string | number;

    // Sub del Día
    is_daily_special: boolean;
    daily_special_days: number[];

    // Precios especiales
    daily_special_precio_pickup_capital: string | number;
    daily_special_precio_domicilio_capital: string | number;
    daily_special_precio_pickup_interior: string | number;
    daily_special_precio_domicilio_interior: string | number;

    // Estado
    is_active: boolean;
}

export default function VariantEdit({ product, variant, daysOfWeek }: EditVariantPageProps) {
    const [formData, setFormData] = useState<FormData>({
        precio_pickup_capital: variant.precio_pickup_capital,
        precio_domicilio_capital: variant.precio_domicilio_capital,
        precio_pickup_interior: variant.precio_pickup_interior,
        precio_domicilio_interior: variant.precio_domicilio_interior,
        is_daily_special: variant.is_daily_special,
        daily_special_days: variant.daily_special_days || [],
        daily_special_precio_pickup_capital: variant.daily_special_precio_pickup_capital || '',
        daily_special_precio_domicilio_capital: variant.daily_special_precio_domicilio_capital || '',
        daily_special_precio_pickup_interior: variant.daily_special_precio_pickup_interior || '',
        daily_special_precio_domicilio_interior: variant.daily_special_precio_domicilio_interior || '',
        is_active: variant.is_active,
    });

    const [errors, setErrors] = useState<Record<string, string>>({});
    const [isSubmitting, setIsSubmitting] = useState(false);

    const handleInputChange = (field: keyof FormData, value: string | number | boolean | number[]) => {
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

    const handleDayToggle = (dayValue: number) => {
        setFormData((prev) => {
            const currentDays = prev.daily_special_days;
            const newDays = currentDays.includes(dayValue) ? currentDays.filter((d) => d !== dayValue) : [...currentDays, dayValue].sort();

            return {
                ...prev,
                daily_special_days: newDays,
            };
        });
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        setIsSubmitting(true);

        const submitData = {
            ...formData,
            precio_pickup_capital:
                typeof formData.precio_pickup_capital === 'string' ? parseFloat(formData.precio_pickup_capital) : formData.precio_pickup_capital,
            precio_domicilio_capital:
                typeof formData.precio_domicilio_capital === 'string'
                    ? parseFloat(formData.precio_domicilio_capital)
                    : formData.precio_domicilio_capital,
            precio_pickup_interior:
                typeof formData.precio_pickup_interior === 'string' ? parseFloat(formData.precio_pickup_interior) : formData.precio_pickup_interior,
            precio_domicilio_interior:
                typeof formData.precio_domicilio_interior === 'string'
                    ? parseFloat(formData.precio_domicilio_interior)
                    : formData.precio_domicilio_interior,
            daily_special_precio_pickup_capital:
                formData.daily_special_precio_pickup_capital !== ''
                    ? typeof formData.daily_special_precio_pickup_capital === 'string'
                        ? parseFloat(formData.daily_special_precio_pickup_capital)
                        : formData.daily_special_precio_pickup_capital
                    : null,
            daily_special_precio_domicilio_capital:
                formData.daily_special_precio_domicilio_capital !== ''
                    ? typeof formData.daily_special_precio_domicilio_capital === 'string'
                        ? parseFloat(formData.daily_special_precio_domicilio_capital)
                        : formData.daily_special_precio_domicilio_capital
                    : null,
            daily_special_precio_pickup_interior:
                formData.daily_special_precio_pickup_interior !== ''
                    ? typeof formData.daily_special_precio_pickup_interior === 'string'
                        ? parseFloat(formData.daily_special_precio_pickup_interior)
                        : formData.daily_special_precio_pickup_interior
                    : null,
            daily_special_precio_domicilio_interior:
                formData.daily_special_precio_domicilio_interior !== ''
                    ? typeof formData.daily_special_precio_domicilio_interior === 'string'
                        ? parseFloat(formData.daily_special_precio_domicilio_interior)
                        : formData.daily_special_precio_domicilio_interior
                    : null,
        };

        router.patch(`/menu/products/${product.id}/variants/${variant.id}`, submitData, {
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
            title={`Editar Variante: ${variant.size}`}
            description={`Producto: ${product.name} | SKU: ${variant.sku}`}
            backHref={route('menu.products.variants.index', product.id)}
            backLabel="Volver"
            onSubmit={handleSubmit}
            submitLabel="Guardar"
            processing={isSubmitting}
            pageTitle={`Editar ${variant.size}`}
            loading={false}
            loadingSkeleton={EditProductsSkeleton}
        >
            {/* Información de la Variante */}
            <Card className="mb-6">
                <CardHeader>
                    <CardTitle className="flex items-center gap-2">
                        <Package className="h-5 w-5" />
                        {variant.size}
                    </CardTitle>
                    <CardDescription>
                        SKU: <span className="font-mono">{variant.sku}</span> | Producto: {product.name}
                    </CardDescription>
                </CardHeader>
            </Card>

            {/* Precios Regulares */}
            <FormSection icon={Banknote} title="Precios Regulares" description="Precios normales de esta variante">
                <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <FormField label="Precio Pickup Capital" error={errors.precio_pickup_capital} required>
                        <Input
                            type="number"
                            step="0.01"
                            min="0"
                            value={formData.precio_pickup_capital}
                            onChange={(e) => handleInputChange('precio_pickup_capital', e.target.value)}
                            placeholder={PLACEHOLDERS.price}
                        />
                    </FormField>

                    <FormField label="Precio Domicilio Capital" error={errors.precio_domicilio_capital} required>
                        <Input
                            type="number"
                            step="0.01"
                            min="0"
                            value={formData.precio_domicilio_capital}
                            onChange={(e) => handleInputChange('precio_domicilio_capital', e.target.value)}
                            placeholder={PLACEHOLDERS.price}
                        />
                    </FormField>

                    <FormField label="Precio Pickup Interior" error={errors.precio_pickup_interior} required>
                        <Input
                            type="number"
                            step="0.01"
                            min="0"
                            value={formData.precio_pickup_interior}
                            onChange={(e) => handleInputChange('precio_pickup_interior', e.target.value)}
                            placeholder={PLACEHOLDERS.price}
                        />
                    </FormField>

                    <FormField label="Precio Domicilio Interior" error={errors.precio_domicilio_interior} required>
                        <Input
                            type="number"
                            step="0.01"
                            min="0"
                            value={formData.precio_domicilio_interior}
                            onChange={(e) => handleInputChange('precio_domicilio_interior', e.target.value)}
                            placeholder={PLACEHOLDERS.price}
                        />
                    </FormField>
                </div>
            </FormSection>

            {/* Sub del Día */}
            <FormSection icon={Star} title="Sub del Día" description="Configura si esta variante es un Sub del Día con precios especiales">
                <div className="space-y-6">
                    {/* Toggle Sub del Día */}
                    <div className="flex items-center space-x-2">
                        <Checkbox
                            id="is_daily_special"
                            checked={formData.is_daily_special}
                            onCheckedChange={(checked) => handleInputChange('is_daily_special', checked as boolean)}
                        />
                        <Label htmlFor="is_daily_special" className="text-sm leading-none font-medium">
                            Esta variante es un Sub del Día
                        </Label>
                    </div>

                    {formData.is_daily_special && (
                        <>
                            <Separator />

                            {/* Días de la semana */}
                            <div className="space-y-3">
                                <Label className="flex items-center gap-2">
                                    <Calendar className="h-4 w-4" />
                                    Días activos del Sub del Día
                                </Label>
                                <p className="text-sm text-muted-foreground">
                                    Selecciona los días en los que esta variante estará disponible como Sub del Día
                                </p>
                                <div className="grid grid-cols-2 gap-3 md:grid-cols-4">
                                    {daysOfWeek.map((day) => (
                                        <div
                                            key={day.value}
                                            className={`flex cursor-pointer items-center justify-center rounded-lg border-2 p-3 transition-all ${
                                                formData.daily_special_days.includes(day.value)
                                                    ? 'border-amber-500 bg-amber-50 dark:bg-amber-950'
                                                    : 'border-border hover:border-amber-300'
                                            } `}
                                            onClick={() => handleDayToggle(day.value)}
                                        >
                                            <span className="text-sm font-medium">{day.label}</span>
                                        </div>
                                    ))}
                                </div>
                                {formData.daily_special_days.length > 0 && (
                                    <div className="mt-2 flex flex-wrap gap-2">
                                        {formData.daily_special_days.sort().map((dayValue) => {
                                            const day = daysOfWeek.find((d) => d.value === dayValue);
                                            return (
                                                <Badge key={dayValue} variant="default" className="bg-amber-500">
                                                    {day?.label}
                                                </Badge>
                                            );
                                        })}
                                    </div>
                                )}
                            </div>

                            <Separator />

                            {/* Precios especiales */}
                            <div className="space-y-4">
                                <div>
                                    <Label>Precios Especiales del Sub del Día</Label>
                                    <p className="mt-1 text-sm text-muted-foreground">Deja en blanco para usar los precios regulares</p>
                                </div>

                                <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
                                    <FormField label="Precio Especial Pickup Capital" error={errors.daily_special_precio_pickup_capital}>
                                        <Input
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            value={formData.daily_special_precio_pickup_capital}
                                            onChange={(e) => handleInputChange('daily_special_precio_pickup_capital', e.target.value)}
                                            placeholder={PLACEHOLDERS.price}
                                        />
                                    </FormField>

                                    <FormField label="Precio Especial Domicilio Capital" error={errors.daily_special_precio_domicilio_capital}>
                                        <Input
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            value={formData.daily_special_precio_domicilio_capital}
                                            onChange={(e) => handleInputChange('daily_special_precio_domicilio_capital', e.target.value)}
                                            placeholder={PLACEHOLDERS.price}
                                        />
                                    </FormField>

                                    <FormField label="Precio Especial Pickup Interior" error={errors.daily_special_precio_pickup_interior}>
                                        <Input
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            value={formData.daily_special_precio_pickup_interior}
                                            onChange={(e) => handleInputChange('daily_special_precio_pickup_interior', e.target.value)}
                                            placeholder={PLACEHOLDERS.price}
                                        />
                                    </FormField>

                                    <FormField label="Precio Especial Domicilio Interior" error={errors.daily_special_precio_domicilio_interior}>
                                        <Input
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            value={formData.daily_special_precio_domicilio_interior}
                                            onChange={(e) => handleInputChange('daily_special_precio_domicilio_interior', e.target.value)}
                                            placeholder={PLACEHOLDERS.price}
                                        />
                                    </FormField>
                                </div>
                            </div>
                        </>
                    )}
                </div>
            </FormSection>

            {/* Estado */}
            <FormSection icon={Package} title="Estado" description="Configuración de activación">
                <div className="flex items-center justify-between rounded-lg border p-4">
                    <div className="space-y-0.5">
                        <Label htmlFor="is_active" className="text-sm font-medium">
                            Variante Activa
                        </Label>
                        <p className="text-xs text-muted-foreground">Las variantes inactivas no se mostrarán en el menú</p>
                    </div>
                    <Switch id="is_active" checked={formData.is_active} onCheckedChange={(checked) => handleInputChange('is_active', checked)} />
                </div>
            </FormSection>
        </EditPageLayout>
    );
}
