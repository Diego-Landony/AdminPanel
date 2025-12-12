import { showNotification } from '@/hooks/useNotifications';
import { useForm } from '@inertiajs/react';
import React from 'react';

import { CreatePageLayout } from '@/components/create-page-layout';
import { FormSection } from '@/components/form-section';
import { Card, CardContent } from '@/components/ui/card';
import { FormField } from '@/components/ui/form-field';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { NOTIFICATIONS } from '@/constants/ui-constants';
import { Award, Palette } from 'lucide-react';

interface BadgeType {
    id: number;
    name: string;
    color: string;
    is_active: boolean;
    sort_order: number;
}

interface EditPageProps {
    badgeType: BadgeType;
}

const PRESET_COLORS = [
    { name: 'Rojo', value: '#ef4444' },
    { name: 'Naranja', value: '#f97316' },
    { name: 'Amarillo', value: '#eab308' },
    { name: 'Verde', value: '#22c55e' },
    { name: 'Azul', value: '#3b82f6' },
    { name: 'Morado', value: '#8b5cf6' },
    { name: 'Rosa', value: '#ec4899' },
    { name: 'Gris', value: '#6b7280' },
];

export default function BadgeTypeEdit({ badgeType }: EditPageProps) {
    const { data, setData, put, processing, errors } = useForm({
        name: badgeType.name,
        color: badgeType.color,
        is_active: badgeType.is_active,
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        put(route('menu.badge-types.update', badgeType.id), {
            onError: (errors) => {
                if (Object.keys(errors).length === 0) {
                    showNotification.error(NOTIFICATIONS.error.server);
                }
            },
        });
    };

    return (
        <CreatePageLayout
            title="Editar Badge"
            backHref={route('menu.badge-types.index')}
            backLabel="Volver"
            onSubmit={handleSubmit}
            submitLabel="Guardar Cambios"
            processing={processing}
            pageTitle={`Editar: ${badgeType.name}`}
        >
            <div className="space-y-8">
                <Card>
                    <CardContent className="pt-6">
                        <FormSection icon={Award} title="Información Básica" description="Configura el nombre y estado del badge">
                            <div className="flex items-center justify-between rounded-lg border p-4">
                                <Label htmlFor="is_active" className="text-base">
                                    Activo
                                </Label>
                                <Switch id="is_active" checked={data.is_active} onCheckedChange={(checked) => setData('is_active', checked as boolean)} />
                            </div>

                            <FormField label="Nombre" error={errors.name} required>
                                <Input
                                    id="name"
                                    type="text"
                                    value={data.name}
                                    onChange={(e) => setData('name', e.target.value)}
                                    placeholder="Ej: Nuevo, Popular, Oferta"
                                    maxLength={50}
                                />
                            </FormField>

                            <div className="rounded-lg border p-4">
                                <Label className="mb-3 block text-base font-medium">Vista Previa</Label>
                                <div className="flex items-center justify-center rounded-lg bg-muted/50 p-6">
                                    <span
                                        className="inline-flex items-center rounded-full px-3 py-1 text-sm font-medium text-white shadow-sm"
                                        style={{ backgroundColor: data.color }}
                                    >
                                        {data.name || 'Badge'}
                                    </span>
                                </div>
                            </div>
                        </FormSection>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent className="pt-6">
                        <FormSection icon={Palette} title="Color" description="Selecciona un color predefinido o personaliza uno">
                            <FormField label="Color" error={errors.color} required>
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
                        </FormSection>
                    </CardContent>
                </Card>
            </div>
        </CreatePageLayout>
    );
}
