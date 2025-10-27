import { showNotification } from '@/hooks/useNotifications';
import { useForm } from '@inertiajs/react';
import React, { useEffect } from 'react';

import { CreatePageLayout } from '@/components/create-page-layout';
import { FormSection } from '@/components/form-section';
import { CreateCategoriesSkeleton } from '@/components/skeletons';
import { FormField } from '@/components/ui/form-field';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { VariantDefinitionsInput } from '@/components/VariantDefinitionsInput';
import { NOTIFICATIONS } from '@/constants/ui-constants';
import { Layers, ListOrdered } from 'lucide-react';

/**
 * Página para crear una categoría de menú
 */
export default function CategoryCreate() {
    const { data, setData, post, processing, errors, reset } = useForm({
        name: '',
        is_active: true,
        is_combo_category: false,
        uses_variants: false,
        variant_definitions: [],
    });

    // Limpiar variant_definitions si uses_variants se desactiva
    useEffect(() => {
        if (!data.uses_variants && data.variant_definitions.length > 0) {
            setData('variant_definitions', []);
        }
    }, [data.uses_variants]);

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
            <FormSection icon={Layers} title="Información Básica">
                <div className="flex items-center justify-between rounded-lg border p-4">
                    <Label htmlFor="is_active" className="text-base">
                        Activa
                    </Label>
                    <Switch id="is_active" checked={data.is_active} onCheckedChange={(checked) => setData('is_active', checked as boolean)} />
                </div>

                <div className="flex items-center justify-between rounded-lg border p-4">
                    <Label htmlFor="is_combo_category" className="text-base">
                        Categoría de combos
                    </Label>
                    <Switch
                        id="is_combo_category"
                        checked={data.is_combo_category}
                        onCheckedChange={(checked) => setData('is_combo_category', checked as boolean)}
                    />
                </div>

                <FormField label="Nombre" error={errors.name} required>
                    <Input id="name" type="text" value={data.name} onChange={(e) => setData('name', e.target.value)} />
                </FormField>
            </FormSection>

            <FormSection icon={ListOrdered} title="Variantes">
                <div className="flex items-center justify-between rounded-lg border p-4">
                    <Label htmlFor="uses_variants" className="text-base">
                        Usa variantes
                    </Label>
                    <Switch
                        id="uses_variants"
                        checked={data.uses_variants}
                        onCheckedChange={(checked) => setData('uses_variants', checked as boolean)}
                    />
                </div>

                {data.uses_variants && (
                    <div className="space-y-2">
                        <Label className="text-sm font-medium">
                            Variantes <span className="text-destructive">*</span>
                        </Label>
                        <VariantDefinitionsInput
                            variants={data.variant_definitions}
                            onChange={(variants) => setData('variant_definitions', variants)}
                            error={errors.variant_definitions}
                        />
                    </div>
                )}
            </FormSection>
        </CreatePageLayout>
    );
}
