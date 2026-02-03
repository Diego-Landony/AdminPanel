/**
 * Componente reutilizable para campos de formulario de productos
 * Usado tanto en create como en edit
 */

import { Accordion, AccordionContent, AccordionItem, AccordionTrigger } from '@/components/ui/accordion';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { CategoryCombobox } from '@/components/CategoryCombobox';
import { ImageCropperUpload } from '@/components/ImageCropperUpload';
import { PriceFields } from '@/components/PriceFields';
import { Checkbox } from '@/components/ui/checkbox';
import { FormField } from '@/components/ui/form-field';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { Textarea } from '@/components/ui/textarea';
import { VariantsFromCategory } from '@/components/VariantsFromCategory';
import { AlertCircle, Banknote, Gift, Info, ListChecks, Package } from 'lucide-react';

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
        <div className="space-y-4">
            <Accordion type="multiple" defaultValue={['basica', 'precios']} className="space-y-4">
                {/* Sección: Información Básica */}
                <AccordionItem value="basica" className="rounded-lg border bg-card">
                    <AccordionTrigger className="px-6 hover:no-underline">
                        <div className="flex items-center gap-2">
                            <Package className="h-5 w-5 text-primary" />
                            <span className="text-lg font-semibold">Información Básica</span>
                        </div>
                    </AccordionTrigger>
                    <AccordionContent className="px-6 pb-6">
                        <div className="space-y-6">
                            <div className="flex items-center justify-between rounded-lg border p-4">
                                <Label htmlFor="is_active" className="cursor-pointer text-sm font-medium">
                                    Producto activo
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
                                aspectRatio={4 / 3}
                                aspectLabel="4:3"
                            />
                        </div>
                    </AccordionContent>
                </AccordionItem>

                {/* Sección: Precios */}
                <AccordionItem value="precios" className="rounded-lg border bg-card">
                    <AccordionTrigger className="px-6 hover:no-underline">
                        <div className="flex items-center gap-2">
                            <Banknote className="h-5 w-5 text-primary" />
                            <span className="text-lg font-semibold">Precios</span>
                        </div>
                    </AccordionTrigger>
                    <AccordionContent className="px-6 pb-6">
                        {!selectedCategory ? (
                            <Alert>
                                <AlertCircle className="h-4 w-4" />
                                <AlertDescription>
                                    Selecciona una categoría en la sección anterior para configurar los precios.
                                </AlertDescription>
                            </Alert>
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
                    </AccordionContent>
                </AccordionItem>

                {/* Sección: Recompensas */}
                <AccordionItem value="recompensas" className="rounded-lg border bg-card">
                    <AccordionTrigger className="px-6 hover:no-underline">
                        <div className="flex items-center gap-2">
                            <Gift className="h-5 w-5 text-primary" />
                            <span className="text-lg font-semibold">Recompensas</span>
                        </div>
                    </AccordionTrigger>
                    <AccordionContent className="px-6 pb-6">
                        {selectedCategory?.uses_variants ? (
                            <Alert>
                                <Info className="h-4 w-4" />
                                <AlertDescription>
                                    Las recompensas se configuran individualmente por cada variante en la sección de Precios.
                                </AlertDescription>
                            </Alert>
                        ) : (
                            <div className="space-y-6">
                                <div className="flex items-center justify-between rounded-lg border p-4">
                                    <Label htmlFor="is_redeemable" className="cursor-pointer text-sm font-medium">
                                        Canjeable por puntos
                                    </Label>
                                    <Switch
                                        id="is_redeemable"
                                        checked={formData.is_redeemable}
                                        onCheckedChange={(checked) => onInputChange('is_redeemable', checked as boolean)}
                                    />
                                </div>

                                {formData.is_redeemable && (
                                    <FormField label="Costo en puntos" error={errors.points_cost} required>
                                        <Input
                                            id="points_cost"
                                            type="number"
                                            min="1"
                                            step="1"
                                            value={formData.points_cost}
                                            onChange={(e) => onInputChange('points_cost', e.target.value)}
                                        />
                                    </FormField>
                                )}
                            </div>
                        )}
                    </AccordionContent>
                </AccordionItem>

                {/* Sección: Secciones */}
                <AccordionItem value="secciones" className="rounded-lg border bg-card">
                    <AccordionTrigger className="px-6 hover:no-underline">
                        <div className="flex items-center gap-2">
                            <ListChecks className="h-5 w-5 text-primary" />
                            <span className="text-lg font-semibold">Secciones</span>
                        </div>
                    </AccordionTrigger>
                    <AccordionContent className="px-6 pb-6">
                        <p className="mb-4 text-sm text-muted-foreground">
                            Selecciona las secciones donde aparecerá este producto en el menú.
                        </p>
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
                    </AccordionContent>
                </AccordionItem>
            </Accordion>
        </div>
    );
}
