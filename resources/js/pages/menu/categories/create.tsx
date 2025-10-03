import { showNotification } from '@/hooks/useNotifications';
import { useForm } from '@inertiajs/react';
import React from 'react';

import { CreatePageLayout } from '@/components/create-page-layout';
import { FormSection } from '@/components/form-section';
import { ImageUpload } from '@/components/ImageUpload';
import { CreateCategoriesSkeleton } from '@/components/skeletons';
import { Checkbox } from '@/components/ui/checkbox';
import { FormField } from '@/components/ui/form-field';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { SECTION_ICONS } from '@/constants/section-icons';
import { NOTIFICATIONS } from '@/constants/ui-constants';
import { Layers } from 'lucide-react';

/**
 * Página para crear una categoría de menú
 */
export default function CategoryCreate() {
    const { data, setData, post, processing, errors, reset } = useForm({
        name: '',
        image: '',
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
            backLabel="Volver a Categorías"
            onSubmit={handleSubmit}
            submitLabel="Crear Categoría"
            processing={processing}
            pageTitle="Crear Categoría"
            loading={processing}
            loadingSkeleton={CreateCategoriesSkeleton}
        >
            <FormSection icon={Layers} title="Información de la Categoría">
                {/* Nombre */}
                <FormField label="Nombre" error={errors.name} required>
                    <Input
                        id="name"
                        type="text"
                        value={data.name}
                        onChange={(e) => setData('name', e.target.value)}
                        placeholder="ej: Sándwiches, Bebidas, Postres"
                    />
                </FormField>

                {/* Imagen */}
                <ImageUpload
                    label="Imagen de la Categoría"
                    currentImage={data.image}
                    onImageChange={(url) => setData('image', url || '')}
                    error={errors.image}
                />

                {/* Estado activo */}
                <div className="flex items-center space-x-2">
                    <Checkbox
                        id="is_active"
                        checked={data.is_active}
                        onCheckedChange={(checked) => setData('is_active', checked as boolean)}
                    />
                    <Label htmlFor="is_active" className="text-sm leading-none font-medium peer-disabled:cursor-not-allowed peer-disabled:opacity-70">
                        Categoría activa
                    </Label>
                </div>
            </FormSection>
        </CreatePageLayout>
    );
}
