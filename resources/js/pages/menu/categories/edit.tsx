import { router } from '@inertiajs/react';
import { useEffect, useState } from 'react';

import { EditPageLayout } from '@/components/edit-page-layout';
import { FormSection } from '@/components/form-section';
import { EditCategoriesSkeleton } from '@/components/skeletons';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { FormField } from '@/components/ui/form-field';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { VariantDefinitionsInput } from '@/components/VariantDefinitionsInput';
import { AlertCircle, Layers, ListOrdered } from 'lucide-react';

interface Category {
    id: number;
    name: string;
    is_active: boolean;
    is_combo_category: boolean;
    uses_variants: boolean;
    variant_definitions: string[];
}

interface EditPageProps {
    category: Category;
}

interface FormData {
    name: string;
    is_active: boolean;
    is_combo_category: boolean;
    uses_variants: boolean;
    variant_definitions: string[];
}

export default function CategoryEdit({ category }: EditPageProps) {
    const [formData, setFormData] = useState<FormData>({
        name: category.name,
        is_active: category.is_active,
        is_combo_category: category.is_combo_category,
        uses_variants: category.uses_variants,
        variant_definitions: category.variant_definitions || [],
    });

    const [errors, setErrors] = useState<Record<string, string>>({});
    const [isSubmitting, setIsSubmitting] = useState(false);

    const initialVariants = category.variant_definitions || [];
    const variantsChanged = JSON.stringify(formData.variant_definitions) !== JSON.stringify(initialVariants);

    // Limpiar variant_definitions si uses_variants se desactiva
    useEffect(() => {
        if (!formData.uses_variants && formData.variant_definitions.length > 0) {
            setFormData((prev) => ({
                ...prev,
                variant_definitions: [],
            }));
        }
    }, [formData.uses_variants, formData.variant_definitions.length]);

    const handleInputChange = (field: keyof FormData, value: string | boolean) => {
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

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        setIsSubmitting(true);

        router.put(`/menu/categories/${category.id}`, formData as unknown as Record<string, string | number | boolean>, {
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
            title="Editar Categoría"
            description={`Modifica los datos de la categoría "${category.name}"`}
            backHref={route('menu.categories.index')}
            backLabel="Volver"
            onSubmit={handleSubmit}
            submitLabel="Guardar"
            processing={isSubmitting}
            pageTitle={`Editar ${category.name}`}
            loading={false}
            loadingSkeleton={EditCategoriesSkeleton}
        >
            <FormSection icon={Layers} title="Información Básica">
                <div className="flex items-center justify-between rounded-lg border p-4">
                    <Label htmlFor="is_active" className="text-base">
                        Activa
                    </Label>
                    <Switch
                        id="is_active"
                        checked={formData.is_active}
                        onCheckedChange={(checked) => handleInputChange('is_active', checked as boolean)}
                    />
                </div>

                <div className="flex items-center justify-between rounded-lg border p-4">
                    <Label htmlFor="is_combo_category" className="text-base">
                        Categoría de combos
                    </Label>
                    <Switch
                        id="is_combo_category"
                        checked={formData.is_combo_category}
                        onCheckedChange={(checked) => handleInputChange('is_combo_category', checked as boolean)}
                    />
                </div>

                <FormField label="Nombre" error={errors.name} required>
                    <Input id="name" type="text" value={formData.name} onChange={(e) => handleInputChange('name', e.target.value)} />
                </FormField>
            </FormSection>

            <FormSection icon={ListOrdered} title="Variantes">
                {formData.uses_variants && variantsChanged && (
                    <Alert variant="default" className="border-yellow-200 bg-yellow-50 dark:bg-yellow-950">
                        <AlertCircle className="h-4 w-4 text-yellow-600" />
                        <AlertTitle className="text-yellow-800 dark:text-yellow-200">Atención</AlertTitle>
                        <AlertDescription className="text-yellow-700 dark:text-yellow-300">
                            Los cambios se aplicarán a todos los productos de esta categoría.
                        </AlertDescription>
                    </Alert>
                )}

                <div className="flex items-center justify-between rounded-lg border p-4">
                    <Label htmlFor="uses_variants" className="text-base">
                        Usa variantes
                    </Label>
                    <Switch
                        id="uses_variants"
                        checked={formData.uses_variants}
                        onCheckedChange={(checked) => handleInputChange('uses_variants', checked as boolean)}
                    />
                </div>

                {formData.uses_variants && (
                    <div className="space-y-2">
                        <Label className="text-sm font-medium">
                            Variantes <span className="text-destructive">*</span>
                        </Label>
                        <VariantDefinitionsInput
                            variants={formData.variant_definitions}
                            onChange={(variants) => handleInputChange('variant_definitions', variants)}
                            error={errors.variant_definitions}
                        />
                    </div>
                )}
            </FormSection>
        </EditPageLayout>
    );
}
