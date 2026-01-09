/**
 * Componente reutilizable para campos de formulario de categorías
 * Usado tanto en create como en edit
 */

import { FormSection } from '@/components/form-section';
import { ImageCropperUpload } from '@/components/ImageCropperUpload';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Card, CardContent } from '@/components/ui/card';
import { FormField } from '@/components/ui/form-field';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { Textarea } from '@/components/ui/textarea';
import { VariantDefinitionsInput } from '@/components/VariantDefinitionsInput';
import { AlertCircle, Layers, ListOrdered } from 'lucide-react';

import type { CategoryFormData, FormErrors } from '@/types/menu';

export interface CategoryFormFieldsProps {
    formData: CategoryFormData;
    onInputChange: (field: keyof CategoryFormData, value: string | boolean | string[]) => void;
    imagePreview: string | null;
    onImageChange: (file: File | null, preview: string | null) => void;
    errors: FormErrors;
    variantsChanged?: boolean;
    mode: 'create' | 'edit';
}

export function CategoryFormFields({
    formData,
    onInputChange,
    imagePreview,
    onImageChange,
    errors,
    variantsChanged = false,
    mode,
}: CategoryFormFieldsProps) {
    const isEdit = mode === 'edit';

    return (
        <div className="space-y-8">
            {/* Informacion Basica */}
            <Card>
                <CardContent className="pt-6">
                    <FormSection icon={Layers} title="Información Básica">
                        <div className="space-y-6">
                            <div className="grid grid-cols-1 gap-4 rounded-lg border p-4 md:grid-cols-2">
                                <div className="flex items-center justify-between md:flex-col md:items-start md:gap-2">
                                    <Label htmlFor="is_active" className="cursor-pointer text-sm font-medium">
                                        Activa
                                    </Label>
                                    <Switch
                                        id="is_active"
                                        checked={formData.is_active}
                                        onCheckedChange={(checked) => onInputChange('is_active', checked as boolean)}
                                    />
                                </div>

                                <div className="flex items-center justify-between md:flex-col md:items-start md:gap-2">
                                    <Label htmlFor="is_combo_category" className="cursor-pointer text-sm font-medium">
                                        Categoría de combos
                                    </Label>
                                    <Switch
                                        id="is_combo_category"
                                        checked={formData.is_combo_category}
                                        onCheckedChange={(checked) => onInputChange('is_combo_category', checked as boolean)}
                                    />
                                </div>
                            </div>

                            <FormField label="Nombre" error={errors.name} required>
                                <Input
                                    id="name"
                                    type="text"
                                    value={formData.name}
                                    onChange={(e) => onInputChange('name', e.target.value)}
                                />
                            </FormField>

                            <FormField label="Descripción" error={errors.description}>
                                <Textarea
                                    id="description"
                                    value={formData.description}
                                    onChange={(e) => onInputChange('description', e.target.value)}
                                    rows={2}
                                    placeholder="Descripción opcional de la categoría"
                                />
                            </FormField>

                            <ImageCropperUpload
                                label="Imagen"
                                currentImage={imagePreview}
                                onImageChange={(file) => onImageChange(file, null)}
                                error={errors.image}
                                aspectRatio={5 / 3}
                                aspectLabel="5:3"
                            />
                        </div>
                    </FormSection>
                </CardContent>
            </Card>

            {/* Variantes */}
            <Card>
                <CardContent className="pt-6">
                    <FormSection icon={ListOrdered} title="Variantes">
                        <div className="space-y-6">
                            {isEdit && formData.uses_variants && variantsChanged && (
                                <Alert variant="default" className="border-yellow-200 bg-yellow-50 dark:bg-yellow-950">
                                    <AlertCircle className="h-4 w-4 text-yellow-600" />
                                    <AlertTitle className="text-yellow-800 dark:text-yellow-200">Atención</AlertTitle>
                                    <AlertDescription className="text-yellow-700 dark:text-yellow-300">
                                        Los cambios se aplicarán a todos los productos de esta categoría.
                                    </AlertDescription>
                                </Alert>
                            )}

                            <div className="flex items-center justify-between rounded-lg border p-4">
                                <Label htmlFor="uses_variants" className="cursor-pointer text-sm font-medium">
                                    Usa variantes
                                </Label>
                                <Switch
                                    id="uses_variants"
                                    checked={formData.uses_variants}
                                    onCheckedChange={(checked) => onInputChange('uses_variants', checked as boolean)}
                                />
                            </div>

                            {formData.uses_variants && (
                                <div className="space-y-2">
                                    <Label className="text-sm font-medium">
                                        Variantes <span className="text-destructive">*</span>
                                    </Label>
                                    <VariantDefinitionsInput
                                        variants={formData.variant_definitions}
                                        onChange={(variants) => onInputChange('variant_definitions', variants)}
                                        error={errors.variant_definitions}
                                    />
                                </div>
                            )}
                        </div>
                    </FormSection>
                </CardContent>
            </Card>
        </div>
    );
}
