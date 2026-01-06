import { router } from '@inertiajs/react';
import { Palette, Star } from 'lucide-react';
import { useState } from 'react';

import { EditPageLayout } from '@/components/edit-page-layout';
import { FormSection } from '@/components/form-section';
import { EditCustomerTypesSkeleton } from '@/components/skeletons';
import { Card, CardContent } from '@/components/ui/card';
import { Switch } from '@/components/ui/switch';
import { FormField } from '@/components/ui/form-field';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { ENTITY_ICONS } from '@/constants/section-icons';
import { PLACEHOLDERS } from '@/constants/ui-constants';

const PRESET_COLORS = [
    { name: 'Gris', value: '#6B7280' },
    { name: 'Bronce', value: '#CD7F32' },
    { name: 'Plata', value: '#C0C0C0' },
    { name: 'Oro', value: '#FFD700' },
    { name: 'Platino', value: '#E5E4E2' },
    { name: 'Verde', value: '#22c55e' },
    { name: 'Azul', value: '#3b82f6' },
    { name: 'Morado', value: '#8b5cf6' },
];

/**
 * Interfaz para el tipo de cliente
 */
interface CustomerType {
    id: number;
    name: string;
    points_required: number;
    multiplier: number;
    color: string | null;
    is_active: boolean;
}

/**
 * Interfaz para las props de la página
 */
interface EditPageProps {
    customer_type: CustomerType;
}

/**
 * Interfaz para el formulario
 */
interface FormData {
    name: string;
    points_required: string | number;
    multiplier: string | number;
    color: string;
    is_active: boolean;
}

/**
 * Página para editar un tipo de cliente
 */
export default function CustomerTypeEdit({ customer_type }: EditPageProps) {
    const [formData, setFormData] = useState<FormData>({
        name: customer_type.name,
        points_required: customer_type.points_required,
        multiplier: customer_type.multiplier,
        color: customer_type.color || '#6B7280',
        is_active: customer_type.is_active,
    });

    const [errors, setErrors] = useState<Record<string, string>>({});
    const [isSubmitting, setIsSubmitting] = useState(false);

    const handleInputChange = (field: keyof FormData, value: string | number | boolean) => {
        setFormData((prev) => ({
            ...prev,
            [field]: value,
        }));

        // Clear error when user starts typing
        if (errors[field]) {
            setErrors((prev) => {
                const newErrors = { ...prev };
                delete newErrors[field];
                return newErrors;
            });
        }
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        setIsSubmitting(true);

        // Validar que los campos numéricos no estén vacíos
        if (!formData.points_required || formData.points_required === '') {
            setErrors({ points_required: 'El campo Puntos requeridos es obligatorio' });
            setIsSubmitting(false);
            return;
        }

        if (!formData.multiplier || formData.multiplier === '') {
            setErrors({ multiplier: 'El campo Multiplicador es obligatorio' });
            setIsSubmitting(false);
            return;
        }

        // Convertir valores string a números antes de enviar
        const submitData = {
            ...formData,
            points_required: typeof formData.points_required === 'string' ? parseInt(formData.points_required) : formData.points_required,
            multiplier: typeof formData.multiplier === 'string' ? parseFloat(formData.multiplier) : formData.multiplier,
        };

        router.put(`/customer-types/${customer_type.id}`, submitData, {
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
            title="Editar Tipo de Cliente"
            description={`Modifica los datos del tipo "${customer_type.name}"`}
            backHref={route('customer-types.index')}
            backLabel="Volver"
            onSubmit={handleSubmit}
            submitLabel="Guardar"
            processing={isSubmitting}
            pageTitle={`Editar ${customer_type.name}`}
            loading={false}
            loadingSkeleton={EditCustomerTypesSkeleton}
        >
            <FormSection icon={ENTITY_ICONS.customerType.info} title="Información del Tipo">
                {/* Nombre */}
                <FormField label="Nombre" error={errors.name} required>
                    <Input
                        id="name"
                        type="text"
                        value={formData.name}
                        onChange={(e) => handleInputChange('name', e.target.value)}
                        placeholder={PLACEHOLDERS.name}
                    />
                </FormField>

                <div className="grid grid-cols-1 gap-6">
                    {/* Puntos requeridos */}
                    <FormField label="Puntos requeridos" error={errors.points_required} required>
                        <div className="relative">
                            <Star className="absolute top-3 left-3 h-4 w-4 text-muted-foreground" />
                            <Input
                                id="points_required"
                                type="number"
                                min="0"
                                value={formData.points_required}
                                onChange={(e) => handleInputChange('points_required', e.target.value)}
                                className="pl-10"
                            />
                        </div>
                    </FormField>

                    {/* Multiplicador */}
                    <FormField label="Multiplicador" error={errors.multiplier} required>
                        <Input
                            id="multiplier"
                            type="number"
                            min="1"
                            max="10"
                            step="0.01"
                            value={formData.multiplier}
                            onChange={(e) => handleInputChange('multiplier', e.target.value)}
                        />
                    </FormField>
                </div>

                {/* Vista Previa */}
                <div className="rounded-lg border p-4">
                    <Label className="mb-3 block text-sm font-medium">Vista Previa</Label>
                    <div className="flex items-center justify-center rounded-lg bg-muted/50 p-6">
                        <span
                            className="inline-flex items-center rounded-full px-3 py-1 text-sm font-medium text-white shadow-sm"
                            style={{ backgroundColor: formData.color }}
                        >
                            {formData.name || 'Tipo de Cliente'}
                        </span>
                    </div>
                </div>

                {/* Color */}
                <FormField label="Color" error={errors.color}>
                    <div className="space-y-3">
                        <div className="flex flex-wrap gap-2">
                            {PRESET_COLORS.map((preset) => (
                                <button
                                    key={preset.value}
                                    type="button"
                                    onClick={() => handleInputChange('color', preset.value)}
                                    title={preset.name}
                                    className={`h-8 w-8 rounded-full border-2 transition-all ${
                                        formData.color === preset.value ? 'scale-110 border-foreground ring-2 ring-primary ring-offset-2' : 'border-transparent hover:scale-105'
                                    }`}
                                    style={{ backgroundColor: preset.value }}
                                />
                            ))}
                        </div>
                        <div className="flex items-center gap-3">
                            <Input
                                id="color"
                                type="color"
                                value={formData.color}
                                onChange={(e) => handleInputChange('color', e.target.value)}
                                className="h-10 w-14 cursor-pointer p-1"
                            />
                            <Input
                                type="text"
                                value={formData.color}
                                onChange={(e) => handleInputChange('color', e.target.value)}
                                placeholder="#000000"
                                maxLength={30}
                                className="flex-1"
                            />
                        </div>
                    </div>
                </FormField>

                {/* Estado activo */}
                <div className="flex items-center justify-between rounded-lg border p-4">
                    <Label htmlFor="is_active" className="cursor-pointer text-sm font-medium">
                        Tipo Activo
                    </Label>
                    <Switch id="is_active" checked={formData.is_active} onCheckedChange={(checked) => handleInputChange('is_active', checked)} />
                </div>
            </FormSection>
        </EditPageLayout>
    );
}
