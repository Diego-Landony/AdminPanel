import { showNotification } from '@/hooks/useNotifications';
import { useForm } from '@inertiajs/react';
import React from 'react';
import { PLACEHOLDERS } from '@/constants/ui-constants';

import { CreatePageLayout } from '@/components/create-page-layout';
import { FormSection } from '@/components/form-section';
import { CreateCategoriesSkeleton } from '@/components/skeletons';
import { Checkbox } from '@/components/ui/checkbox';
import { FormField } from '@/components/ui/form-field';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { NOTIFICATIONS } from '@/constants/ui-constants';
import { Layers } from 'lucide-react';

/**
 * Página para crear una categoría de menú
 */
export default function CategoryCreate() {
    const { data, setData, post, processing, errors, reset } = useForm({
        name: '',
        is_active: true,
    });

    /**
     * Maneja el envío del formulario
     */
    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        post(route('menu.categories.store'), {
            onSuccess: () => {
                reset();
            },
            onError: (errors) => {
                if (Object.keys(errors).length === 0) {
                    showNotification.error(NOTIFICATIONS.error.server);
                }
            },
        });
    };

    return (
        <CreatePageLayout
            title="Nueva Categoría"
            backHref={route('menu.categories.index')}
            backLabel="Volver"
            onSubmit={handleSubmit}
            submitLabel="Guardar"
            processing={processing}
            pageTitle="Crear Categoría"
            loading={processing}
            loadingSkeleton={CreateCategoriesSkeleton}
        >
            <FormSection icon={Layers} title="Información Básica" description="Datos principales de la categoría">
                {/* Estado activo */}
                <div className="flex items-center justify-between rounded-lg border p-4">
                    <Label htmlFor="is_active" className="text-base">
                        Categoría activa
                    </Label>
                    <Switch
                        id="is_active"
                        checked={data.is_active}
                        onCheckedChange={(checked) => setData('is_active', checked as boolean)}
                    />
                </div>

                {/* Nombre */}
                <FormField label="Nombre" error={errors.name} required>
                    <Input
                        id="name"
                        type="text"
                        value={data.name}
                        onChange={(e) => setData('name', e.target.value)}

                    />
                </FormField>
            </FormSection>
        </CreatePageLayout>
    );
}
