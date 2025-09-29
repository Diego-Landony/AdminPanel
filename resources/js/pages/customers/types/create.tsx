import { showNotification } from '@/hooks/useNotifications';
import { useForm } from '@inertiajs/react';
import { Hash, Star } from 'lucide-react';
import React from 'react';

import { CreatePageLayout } from '@/components/create-page-layout';
import { FormSection } from '@/components/form-section';
import { CreateCustomerTypesSkeleton } from '@/components/skeletons';
import { Checkbox } from '@/components/ui/checkbox';
import { FormField } from '@/components/ui/form-field';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { ENTITY_ICONS } from '@/constants/section-icons';
import { NOTIFICATIONS } from '@/constants/ui-constants';

/**
 * Página para crear un tipo de cliente
 */
export default function CustomerTypeCreate() {
    const { data, setData, post, processing, errors, reset } = useForm({
        name: '',
        points_required: '',
        multiplier: '',
        color: 'blue',
        is_active: true,
    });


    /**
     * Maneja el envío del formulario
     */
    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        // Validar que los campos numéricos no estén vacíos
        if (!data.points_required || data.points_required === '') {
            showNotification.error('El campo Puntos requeridos es obligatorio');
            return;
        }

        if (!data.multiplier || data.multiplier === '') {
            showNotification.error('El campo Multiplicador es obligatorio');
            return;
        }

        // Convertir valores string a números antes de enviar
        const submitData = {
            ...data,
            points_required: typeof data.points_required === 'string'
                ? parseInt(data.points_required)
                : data.points_required,
            multiplier: typeof data.multiplier === 'string'
                ? parseFloat(data.multiplier)
                : data.multiplier,
        };

        post(route('customer-types.store'), {
            data: submitData,
            onSuccess: () => {
                reset();
            },
            onError: (errors) => {
                // Los errores de validación se muestran automáticamente
                // Los errores del servidor se manejan por el layout
                if (Object.keys(errors).length === 0) {
                    showNotification.error(NOTIFICATIONS.error.serverCustomerType);
                }
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
        <CreatePageLayout
            title="Crear Tipo de Cliente"
            description="Crea un nuevo tipo de cliente con sus multiplicadores y requisitos"
            backHref={route('customer-types.index')}
            backLabel="Volver a Tipos de Cliente"
            onSubmit={handleSubmit}
            submitLabel="Crear Tipo"
            processing={processing}
            pageTitle="Crear Tipo de Cliente"
            loading={processing}
            loadingSkeleton={CreateCustomerTypesSkeleton}
        >
            <FormSection icon={ENTITY_ICONS.customerType.info} title="Información del Tipo" description="Complete los datos del nuevo tipo de cliente">
                {/* Nombre */}
                <FormField label="Nombre" error={errors.name} required>
                    <Input
                        id="name"
                        type="text"
                        value={data.name}
                        onChange={(e) => setData('name', e.target.value)}
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
                                value={data.points_required}
                                onChange={(e) => setData('points_required', e.target.value)}
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
                            value={data.multiplier}
                            onChange={(e) => setData('multiplier', e.target.value)}
                        />
                    </FormField>
                </div>

                <div className="grid grid-cols-1 gap-6">
                    {/* Color */}
                    <FormField label="Color" error={errors.color}>
                        <Select value={data.color} onValueChange={(value) => setData('color', value)}>
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
                    <Checkbox id="is_active" checked={data.is_active} onCheckedChange={(checked) => setData('is_active', checked as boolean)} />
                    <Label htmlFor="is_active" className="text-sm leading-none font-medium peer-disabled:cursor-not-allowed peer-disabled:opacity-70">
                        Tipo activo
                    </Label>
                </div>
            </FormSection>
        </CreatePageLayout>
    );
}
