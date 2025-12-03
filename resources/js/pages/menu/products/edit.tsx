import { router } from '@inertiajs/react';
import { useCallback, useEffect, useState } from 'react';

import { CategoryCombobox } from '@/components/CategoryCombobox';
import { EditPageLayout } from '@/components/edit-page-layout';
import { FormSection } from '@/components/form-section';
import { ImageUpload } from '@/components/ImageUpload';
import { PriceFields } from '@/components/PriceFields';
import { EditProductsSkeleton } from '@/components/skeletons';
import { Checkbox } from '@/components/ui/checkbox';
import { FormField } from '@/components/ui/form-field';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { Textarea } from '@/components/ui/textarea';
import { VariantsFromCategory } from '@/components/VariantsFromCategory';
import { Banknote, ListChecks, Package } from 'lucide-react';

interface Category {
    id: number;
    name: string;
    uses_variants: boolean;
    variant_definitions: string[];
}

interface Section {
    id: number;
    title: string;
}

interface ProductVariant {
    id: number | string;
    name: string;
    is_active?: boolean;
    precio_pickup_capital: string | number;
    precio_domicilio_capital: string | number;
    precio_pickup_interior: string | number;
    precio_domicilio_interior: string | number;
}

interface VariantData {
    name: string;
    is_active: boolean;
    precio_pickup_capital: string;
    precio_domicilio_capital: string;
    precio_pickup_interior: string;
    precio_domicilio_interior: string;
}

interface Product {
    id: number;
    category_id: number | null;
    name: string;
    description: string | null;
    image: string | null;
    is_customizable: boolean;
    is_active: boolean;
    has_variants: boolean;
    precio_pickup_capital: string | null;
    precio_domicilio_capital: string | null;
    precio_pickup_interior: string | null;
    precio_domicilio_interior: string | null;
    category: Category | null;
    sections: Section[];
    variants: ProductVariant[];
}

interface EditProductPageProps {
    product: Product;
    categories: Category[];
    sections: Section[];
}

interface FormData {
    category_id: string;
    name: string;
    description: string;
    is_active: boolean;
    has_variants: boolean;
    precio_pickup_capital: string;
    precio_domicilio_capital: string;
    precio_pickup_interior: string;
    precio_domicilio_interior: string;
    variants: VariantData[];
}

export default function ProductEdit({ product, categories, sections }: EditProductPageProps) {
    // Initialize variants from existing product data
    const initialVariants: VariantData[] = product.variants.map((v) => ({
        name: v.name,
        is_active: v.is_active ?? true,
        precio_pickup_capital: String(v.precio_pickup_capital || ''),
        precio_domicilio_capital: String(v.precio_domicilio_capital || ''),
        precio_pickup_interior: String(v.precio_pickup_interior || ''),
        precio_domicilio_interior: String(v.precio_domicilio_interior || ''),
    }));

    const [formData, setFormData] = useState<FormData>({
        category_id: product.category_id ? String(product.category_id) : '',
        name: product.name,
        description: product.description || '',
        is_active: product.is_active,
        has_variants: product.has_variants,
        precio_pickup_capital: product.precio_pickup_capital || '',
        precio_domicilio_capital: product.precio_domicilio_capital || '',
        precio_pickup_interior: product.precio_pickup_interior || '',
        precio_domicilio_interior: product.precio_domicilio_interior || '',
        variants: initialVariants,
    });

    const [imageFile, setImageFile] = useState<File | null>(null);
    const [imagePreview, setImagePreview] = useState<string | null>(product.image || null);
    const [removeImage, setRemoveImage] = useState(false);
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [selectedSections, setSelectedSections] = useState<number[]>(product.sections.map((s) => s.id));
    const [selectedCategory, setSelectedCategory] = useState<Category | null>(
        product.category ? categories.find((c) => c.id === product.category_id) || null : null,
    );

    useEffect(() => {
        if (formData.category_id) {
            const category = categories.find((c) => c.id === Number(formData.category_id));
            setSelectedCategory(category || null);
            setFormData((prev) => ({ ...prev, has_variants: category?.uses_variants || false }));
        } else {
            setSelectedCategory(null);
            setFormData((prev) => ({ ...prev, has_variants: false }));
        }
    }, [formData.category_id, categories]);

    const handleInputChange = (field: keyof FormData, value: string | boolean | VariantData[]) => {
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

    const handleCategoryChange = (value: number | null) => {
        handleInputChange('category_id', value ? String(value) : '');
    };

    const handleVariantsChange = useCallback((variants: VariantData[]) => {
        setFormData((prev) => ({
            ...prev,
            variants: variants,
        }));

        if (errors['variants']) {
            setErrors((prev) => {
                const newErrors = { ...prev };
                delete newErrors['variants'];
                return newErrors;
            });
        }
    }, [errors]);

    const handleImageChange = (file: File | null, previewUrl: string | null) => {
        setImageFile(file);
        setImagePreview(previewUrl);
        setRemoveImage(file === null && previewUrl === null);
        if (errors.image) {
            setErrors((prev) => {
                const newErrors = { ...prev };
                delete newErrors.image;
                return newErrors;
            });
        }
    };

    const toggleSection = (sectionId: number) => {
        setSelectedSections((prev) => (prev.includes(sectionId) ? prev.filter((id) => id !== sectionId) : [...prev, sectionId]));
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        setIsSubmitting(true);

        const data = new FormData();
        data.append('_method', 'PUT');
        data.append('category_id', formData.category_id);
        data.append('name', formData.name);
        data.append('description', formData.description);
        data.append('is_active', formData.is_active ? '1' : '0');
        data.append('has_variants', formData.has_variants ? '1' : '0');
        data.append('precio_pickup_capital', formData.precio_pickup_capital);
        data.append('precio_domicilio_capital', formData.precio_domicilio_capital);
        data.append('precio_pickup_interior', formData.precio_pickup_interior);
        data.append('precio_domicilio_interior', formData.precio_domicilio_interior);

        if (imageFile) {
            data.append('image', imageFile);
        } else if (removeImage) {
            data.append('remove_image', '1');
        }

        selectedSections.forEach((sectionId, index) => {
            data.append(`sections[${index}]`, String(sectionId));
        });

        const activeVariants = formData.has_variants
            ? formData.variants.filter((v) => v.is_active)
            : [];

        activeVariants.forEach((variant, index) => {
            data.append(`variants[${index}][name]`, variant.name);
            data.append(`variants[${index}][precio_pickup_capital]`, variant.precio_pickup_capital);
            data.append(`variants[${index}][precio_domicilio_capital]`, variant.precio_domicilio_capital);
            data.append(`variants[${index}][precio_pickup_interior]`, variant.precio_pickup_interior);
            data.append(`variants[${index}][precio_domicilio_interior]`, variant.precio_domicilio_interior);
        });

        router.post(`/menu/products/${product.id}`, data, {
            forceFormData: true,
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
            title="Editar Producto"
            description={`Modifica los datos del producto "${product.name}"`}
            backHref={route('menu.products.index')}
            backLabel="Volver"
            onSubmit={handleSubmit}
            submitLabel="Guardar"
            processing={isSubmitting}
            pageTitle={`Editar ${product.name}`}
            loading={false}
            loadingSkeleton={EditProductsSkeleton}
        >
            <FormSection icon={Package} title="Información Básica">
                <div className="flex items-center justify-between rounded-lg border p-4">
                    <Label htmlFor="is_active" className="text-base">
                        Activo
                    </Label>
                    <Switch
                        id="is_active"
                        checked={formData.is_active}
                        onCheckedChange={(checked) => handleInputChange('is_active', checked as boolean)}
                    />
                </div>

                <CategoryCombobox
                    value={formData.category_id ? Number(formData.category_id) : null}
                    onChange={handleCategoryChange}
                    categories={categories}
                    label="Categoría"
                    error={errors.category_id}
                    required
                />

                <FormField label="Nombre" error={errors.name} required>
                    <Input id="name" type="text" value={formData.name} onChange={(e) => handleInputChange('name', e.target.value)} />
                </FormField>

                <FormField label="Descripción" error={errors.description}>
                    <Textarea
                        id="description"
                        value={formData.description}
                        onChange={(e) => handleInputChange('description', e.target.value)}
                        rows={2}
                    />
                </FormField>

                <ImageUpload
                    label="Imagen"
                    currentImage={imagePreview}
                    onImageChange={handleImageChange}
                    error={errors.image}
                />
            </FormSection>

            <FormSection icon={Banknote} title="Precios" className="mt-8">
                {!selectedCategory ? (
                    <p className="text-sm text-muted-foreground">Selecciona una categoría</p>
                ) : selectedCategory.uses_variants ? (
                    <VariantsFromCategory
                        categoryVariants={selectedCategory.variant_definitions || []}
                        existingVariants={product.variants}
                        onChange={handleVariantsChange}
                        errors={errors}
                    />
                ) : (
                    <PriceFields
                        capitalPickup={formData.precio_pickup_capital}
                        capitalDomicilio={formData.precio_domicilio_capital}
                        interiorPickup={formData.precio_pickup_interior}
                        interiorDomicilio={formData.precio_domicilio_interior}
                        onChangeCapitalPickup={(value) => handleInputChange('precio_pickup_capital', value)}
                        onChangeCapitalDomicilio={(value) => handleInputChange('precio_domicilio_capital', value)}
                        onChangeInteriorPickup={(value) => handleInputChange('precio_pickup_interior', value)}
                        onChangeInteriorDomicilio={(value) => handleInputChange('precio_domicilio_interior', value)}
                        errors={{
                            capitalPickup: errors.precio_pickup_capital,
                            capitalDomicilio: errors.precio_domicilio_capital,
                            interiorPickup: errors.precio_pickup_interior,
                            interiorDomicilio: errors.precio_domicilio_interior,
                        }}
                    />
                )}
            </FormSection>

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
                                    onCheckedChange={() => toggleSection(section.id)}
                                />
                                <Label htmlFor={`section-${section.id}`} className="cursor-pointer text-sm leading-none font-medium">
                                    {section.title}
                                </Label>
                            </div>
                        ))
                    )}
                </div>
            </FormSection>
        </EditPageLayout>
    );
}
