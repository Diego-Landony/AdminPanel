import { showNotification } from '@/hooks/useNotifications';
import { useForm } from '@inertiajs/react';
import { Palette, Star } from 'lucide-react';
import React from 'react';

import { CreatePageLayout } from '@/components/create-page-layout';
import { FormSection } from '@/components/form-section';
import { CreateCustomerTypesSkeleton } from '@/components/skeletons';
import { Card, CardContent } from '@/components/ui/card';
import { FormField } from '@/components/ui/form-field';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { ENTITY_ICONS } from '@/constants/section-icons';
import { NOTIFICATIONS, PLACEHOLDERS } from '@/constants/ui-constants';

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
 * Página para crear un tipo de cliente
 */
export default function CustomerTypeCreate() {
    const { data, setData, post, processing, errors, reset } = useForm({
        name: '',
        points_required: '',
        multiplier: '',
        color: '#6B7280',
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

        // Enviar directamente - Laravel convertirá los strings a números
        post(route('customer-types.store'), {
            onSuccess: () => {
                reset();
            },
            onError: (errors: Record<string, string>) => {
                // Los errores de validación se muestran automáticamente
                // Los errores del servidor se manejan por el layout
                if (Object.keys(errors).length === 0) {
                    showNotification.error(NOTIFICATIONS.error.serverCustomerType);
                }
            },
        });
    };

    return (
        <CreatePageLayout
            title="Nuevo Tipo de Cliente"
            backHref={route('customer-types.index')}
            backLabel="Volver"
            onSubmit={handleSubmit}
            submitLabel="Guardar"
            processing={processing}
            pageTitle="Crear Tipo de Cliente"
            loading={processing}
            loadingSkeleton={CreateCustomerTypesSkeleton}
        >
            <FormSection icon={ENTITY_ICONS.customerType.info} title="Información del Tipo">
                {/* Nombre */}
                <FormField label="Nombre" error={errors.name} required>
                    <Input
                        id="name"
                        type="text"
                        value={data.name}
                        onChange={(e) => setData('name', e.target.value)}
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

                {/* Vista Previa */}
                <div className="rounded-lg border p-4">
                    <Label className="mb-3 block text-sm font-medium">Vista Previa</Label>
                    <div className="flex items-center justify-center rounded-lg bg-muted/50 p-6">
                        <span
                            className="inline-flex items-center rounded-full px-3 py-1 text-sm font-medium text-white shadow-sm"
                            style={{ backgroundColor: data.color }}
                        >
                            {data.name || 'Tipo de Cliente'}
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
                                    onClick={() => setData('color', preset.value)}
                                    title={preset.name}
                                    className={`h-8 w-8 rounded-full border-2 transition-all ${
                                        data.color === preset.value ? 'scale-110 border-foreground ring-2 ring-primary ring-offset-2' : 'border-transparent hover:scale-105'
                                    }`}
                                    style={{ backgroundColor: preset.value }}
                                />
                            ))}
                        </div>
                        <div className="flex items-center gap-3">
                            <Input
                                id="color"
                                type="color"
                                value={data.color}
                                onChange={(e) => setData('color', e.target.value)}
                                className="h-10 w-14 cursor-pointer p-1"
                            />
                            <Input
                                type="text"
                                value={data.color}
                                onChange={(e) => setData('color', e.target.value)}
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
                    <Switch id="is_active" checked={data.is_active} onCheckedChange={(checked) => setData('is_active', checked)} />
                </div>
            </FormSection>
        </CreatePageLayout>
    );
}
