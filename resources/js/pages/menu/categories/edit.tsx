import { router } from '@inertiajs/react';
import { useState } from 'react';
import { PLACEHOLDERS } from '@/constants/ui-constants';

import { EditPageLayout } from '@/components/edit-page-layout';
import { FormSection } from '@/components/form-section';
import { EditCategoriesSkeleton } from '@/components/skeletons';
import { Checkbox } from '@/components/ui/checkbox';
import { FormField } from '@/components/ui/form-field';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { Layers } from 'lucide-react';

interface Category {
    id: number;
    name: string;
    is_active: boolean;
}

interface EditPageProps {
    category: Category;
}

interface FormData {
    name: string;
    is_active: boolean;
}

export default function CategoryEdit({ category }: EditPageProps) {
    const [formData, setFormData] = useState<FormData>({
        name: category.name,
        is_active: category.is_active,
    });

    const [errors, setErrors] = useState<Record<string, string>>({});
    const [isSubmitting, setIsSubmitting] = useState(false);

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

        router.put(`/menu/categories/${category.id}`, formData, {
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
            <FormSection icon={Layers} title="Información Básica" description="Datos principales de la categoría">
                <div className="flex items-center justify-between rounded-lg border p-4">
                    <Label htmlFor="is_active" className="text-base">
                        Categoría activa
                    </Label>
                    <Switch
                        id="is_active"
                        checked={formData.is_active}
                        onCheckedChange={(checked) => handleInputChange('is_active', checked as boolean)}
                    />
                </div>

                <FormField label="Nombre" error={errors.name} required>
                    <Input
                        id="name"
                        type="text"
                        value={formData.name}
                        onChange={(e) => handleInputChange('name', e.target.value)}

                    />
                </FormField>
            </FormSection>
        </EditPageLayout>
    );
}
