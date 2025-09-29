import { router } from '@inertiajs/react';
import { useState } from 'react';
import { Hash, Star } from 'lucide-react';

import { EditPageLayout } from '@/components/edit-page-layout';
import { FormSection } from '@/components/form-section';
import { EditCustomerTypesSkeleton } from '@/components/skeletons';
import { Checkbox } from '@/components/ui/checkbox';
import { FormField } from '@/components/ui/form-field';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { ENTITY_ICONS } from '@/constants/section-icons';

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
        color: customer_type.color || 'blue',
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
            points_required: typeof formData.points_required === 'string'
                ? parseInt(formData.points_required)
                : formData.points_required,
            multiplier: typeof formData.multiplier === 'string'
                ? parseFloat(formData.multiplier)
                : formData.multiplier,
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


    const colorOptions = [
        { value: 'gray', label: 'Gris', class: 'bg-gray-100 text-gray-800 border border-gray-200' },
        { value: 'blue', label: 'Azul', class: 'bg-blue-100 text-blue-800 border border-blue-200' },
        { value: 'green', label: 'Verde', class: 'bg-green-100 text-green-800 border border-green-200' },
        { value: 'yellow', label: 'Amarillo', class: 'bg-yellow-100 text-yellow-800 border border-yellow-200' },
        { value: 'orange', label: 'Naranja', class: 'bg-orange-100 text-orange-800 border border-orange-200' },
        { value: 'red', label: 'Rojo', class: 'bg-red-100 text-red-800 border border-red-200' },
        { value: 'purple', label: 'Púrpura', class: 'bg-purple-100 text-purple-800 border border-purple-200' },
        { value: 'slate', label: 'Pizarra', class: 'bg-slate-100 text-slate-800 border border-slate-200' },
    ];

    return (
        <EditPageLayout
            title="Editar Tipo de Cliente"
            description={`Modifica los datos del tipo "${customer_type.name}"`}
            backHref={route('customer-types.index')}
            backLabel="Volver a Tipos de Cliente"
            onSubmit={handleSubmit}
            submitLabel="Guardar Cambios"
            processing={isSubmitting}
            pageTitle={`Editar ${customer_type.name}`}
            loading={false}
            loadingSkeleton={EditCustomerTypesSkeleton}
        >
            <FormSection icon={ENTITY_ICONS.customerType.info} title="Información del Tipo" description="Modifica los datos del tipo de cliente">
                {/* Nombre */}
                <FormField label="Nombre" error={errors.name} required>
                    <Input
                        id="name"
                        type="text"
                        value={formData.name}
                        onChange={(e) => handleInputChange('name', e.target.value)}
                        placeholder="ej: Bronce, Plata, Oro"
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

                <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
                    {/* Color */}
                    <FormField label="Color" error={errors.color}>
                        <Select value={formData.color} onValueChange={(value) => handleInputChange('color', value)}>
                            <SelectTrigger>
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                {colorOptions.map((color) => (
                                    <SelectItem key={color.value} value={color.value}>
                                        <div className="flex items-center gap-2">
                                            <div className={`h-4 w-4 rounded ${color.class}`}></div>
                                            {color.label}
                                        </div>
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </FormField>

                </div>

                {/* Estado activo */}
                <div className="flex items-center space-x-2">
                    <Checkbox
                        id="is_active"
                        checked={formData.is_active}
                        onCheckedChange={(checked) => handleInputChange('is_active', checked as boolean)}
                    />
                    <Label htmlFor="is_active" className="text-sm leading-none font-medium peer-disabled:cursor-not-allowed peer-disabled:opacity-70">
                        Tipo activo
                    </Label>
                </div>

            </FormSection>
        </EditPageLayout>
    );
}
