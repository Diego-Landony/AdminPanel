/**
 * Componente reutilizable para campos de formulario de productos
 * Usado tanto en create como en edit
 */

import { CategoryCombobox } from '@/components/CategoryCombobox';
import { FormSection } from '@/components/form-section';
import { ImageCropperUpload } from '@/components/ImageCropperUpload';
import { PriceFields } from '@/components/PriceFields';
import { Card, CardContent } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { FormField } from '@/components/ui/form-field';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { Textarea } from '@/components/ui/textarea';
import { VariantsFromCategory } from '@/components/VariantsFromCategory';
import { Banknote, ListChecks, Package } from 'lucide-react';

import type { Category, Section, ProductVariant, VariantFormData, FormErrors } from '@/types/menu';
import type { ProductFormData } from '@/hooks/useProductForm';

export interface ProductFormFieldsProps {
    formData: ProductFormData;
    onInputChange: (field: keyof ProductFormData, value: string | boolean | VariantFormData[]) => void;
    categories: Category[];
    sections: Section[];
    selectedSections: number[];
    onToggleSection: (id: number) => void;
    selectedCategory: Category | null;
    imagePreview: string | null;
    onImageChange: (file: File | null, preview: string | null) => void;
    onVariantsChange: (variants: VariantFormData[]) => void;
    onCategoryChange: (id: number | null) => void;
    errors: FormErrors;
    existingVariants?: ProductVariant[];
}

export function ProductFormFields({
    formData,
    onInputChange,
    categories,
    sections,
    selectedSections,
    onToggleSection,
    selectedCategory,
    imagePreview,
    onImageChange,
    onVariantsChange,
    onCategoryChange,
    errors,
    existingVariants,
}: ProductFormFieldsProps) {
    return (
        <div className="space-y-8">
            {/* Informacion Basica */}
            <Card>
                <CardContent className="pt-6">
                    <FormSection icon={Package} title="Información Básica">
                        <div className="space-y-6">
                            <div className="flex items-center justify-between rounded-lg border p-4">
                                <Label htmlFor="is_active" className="cursor-pointer text-sm font-medium">
                                    Producto Activo
                                </Label>
                                <Switch
                                    id="is_active"
                                    checked={formData.is_active}
                                    onCheckedChange={(checked) => onInputChange('is_active', checked as boolean)}
                                />
                            </div>

                            <CategoryCombobox
                                value={formData.category_id ? Number(formData.category_id) : null}
                                onChange={onCategoryChange}
                                categories={categories}
                                label="Categoría"
                                error={errors.category_id}
                                required
                            />

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
                                />
                            </FormField>

                            <ImageCropperUpload
                                label="Imagen"
                                currentImage={imagePreview}
                                onImageChange={(file) => onImageChange(file, null)}
                                error={errors.image}
                                aspectRatio={1}
                                aspectLabel="1:1"
                            />
                        </div>
                    </FormSection>
                </CardContent>
            </Card>

            {/* Precios */}
            <Card>
                <CardContent className="pt-6">
                    <FormSection icon={Banknote} title="Precios">
                        {!selectedCategory ? (
                            <p className="text-sm text-muted-foreground">Selecciona una categoría para continuar</p>
                        ) : selectedCategory.uses_variants ? (
                            <VariantsFromCategory
                                categoryVariants={selectedCategory.variant_definitions || []}
                                existingVariants={existingVariants}
                                onChange={onVariantsChange}
                                errors={errors}
                            />
                        ) : (
                            <PriceFields
                                capitalPickup={formData.precio_pickup_capital}
                                capitalDomicilio={formData.precio_domicilio_capital}
                                interiorPickup={formData.precio_pickup_interior}
                                interiorDomicilio={formData.precio_domicilio_interior}
                                onChangeCapitalPickup={(value) => onInputChange('precio_pickup_capital', value)}
                                onChangeCapitalDomicilio={(value) => onInputChange('precio_domicilio_capital', value)}
                                onChangeInteriorPickup={(value) => onInputChange('precio_pickup_interior', value)}
                                onChangeInteriorDomicilio={(value) => onInputChange('precio_domicilio_interior', value)}
                                errors={{
                                    capitalPickup: errors.precio_pickup_capital,
                                    capitalDomicilio: errors.precio_domicilio_capital,
                                    interiorPickup: errors.precio_pickup_interior,
                                    interiorDomicilio: errors.precio_domicilio_interior,
                                }}
                            />
                        )}
                    </FormSection>
                </CardContent>
            </Card>

            {/* Secciones */}
            <Card>
                <CardContent className="pt-6">
                    <FormSection icon={ListChecks} title="Secciones">
                        <div className="space-y-2">
                            {sections.length === 0 ? (
                                <p className="text-sm text-muted-foreground">No hay secciones disponibles</p>
                            ) : (
                                sections.map((section) => (
                                    <div key={section.id} className="flex items-center space-x-2">
                                        <Checkbox
                                            id={`section-${section.id}`}
                                            checked={selectedSections.includes(section.id)}
                                            onCheckedChange={() => onToggleSection(section.id)}
                                        />
                                        <Label
                                            htmlFor={`section-${section.id}`}
                                            className="cursor-pointer text-sm leading-none font-medium"
                                        >
                                            {section.title}
                                        </Label>
                                    </div>
                                ))
                            )}
                        </div>
                    </FormSection>
                </CardContent>
            </Card>
        </div>
    );
}
