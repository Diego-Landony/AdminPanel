import { showNotification } from '@/hooks/useNotifications';
import { router } from '@inertiajs/react';
import React, { useEffect, useState } from 'react';

import { CategoryCombobox } from '@/components/CategoryCombobox';
import { CreatePageLayout } from '@/components/create-page-layout';
import { FormSection } from '@/components/form-section';
import { ImageUpload } from '@/components/ImageUpload';
import { PriceFields } from '@/components/PriceFields';
import { CreateProductsSkeleton } from '@/components/skeletons';
import { Checkbox } from '@/components/ui/checkbox';
import { FormField } from '@/components/ui/form-field';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { Textarea } from '@/components/ui/textarea';
import { VariantsFromCategory } from '@/components/VariantsFromCategory';
import { NOTIFICATIONS } from '@/constants/ui-constants';
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

interface VariantData {
    name: string;
    is_active: boolean;
    precio_pickup_capital: string;
    precio_domicilio_capital: string;
    precio_pickup_interior: string;
    precio_domicilio_interior: string;
}

interface CreateProductPageProps {
    categories: Category[];
    sections: Section[];
}

export default function ProductCreate({ categories, sections }: CreateProductPageProps) {
    const [formData, setFormData] = useState({
        category_id: '',
        name: '',
        description: '',
        is_active: true,
        has_variants: false,
        precio_pickup_capital: '',
        precio_domicilio_capital: '',
        precio_pickup_interior: '',
        precio_domicilio_interior: '',
        variants: [] as VariantData[],
    });

    const [imageFile, setImageFile] = useState<File | null>(null);
    const [imagePreview, setImagePreview] = useState<string | null>(null);
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [processing, setProcessing] = useState(false);
    const [selectedCategory, setSelectedCategory] = useState<Category | null>(null);
    const [selectedSections, setSelectedSections] = useState<number[]>([]);

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

    const handleInputChange = (field: string, value: string | boolean | VariantData[]) => {
        setFormData((prev) => ({ ...prev, [field]: value }));
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

    const handleVariantsChange = (variants: VariantData[]) => {
        handleInputChange('variants', variants);
    };

    const handleImageChange = (file: File | null, previewUrl: string | null) => {
        setImageFile(file);
        setImagePreview(previewUrl);
        if (errors.image) {
            setErrors((prev) => {
                const newErrors = { ...prev };
                delete newErrors.image;
                return newErrors;
            });
        }
    };

    const toggleSection = (sectionId: number) => {
        const newSelected = selectedSections.includes(sectionId)
            ? selectedSections.filter((id) => id !== sectionId)
            : [...selectedSections, sectionId];

        setSelectedSections(newSelected);
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        setProcessing(true);

        const data = new FormData();
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

        router.post(route('menu.products.store'), data, {
            forceFormData: true,
            onSuccess: () => {
                setFormData({
                    category_id: '',
                    name: '',
                    description: '',
                    is_active: true,
                    has_variants: false,
                    precio_pickup_capital: '',
                    precio_domicilio_capital: '',
                    precio_pickup_interior: '',
                    precio_domicilio_interior: '',
                    variants: [],
                });
                setImageFile(null);
                setImagePreview(null);
                setSelectedSections([]);
                setProcessing(false);
            },
            onError: (errors: Record<string, string>) => {
                setErrors(errors);
                setProcessing(false);
                if (Object.keys(errors).length === 0) {
                    showNotification.error(NOTIFICATIONS.error.server);
                }
            },
        });
    };

    return (
        <CreatePageLayout
            title="Nuevo Producto"
            backHref={route('menu.products.index')}
            backLabel="Volver"
            onSubmit={handleSubmit}
            submitLabel="Guardar"
            processing={processing}
            pageTitle="Crear Producto"
            loading={processing}
            loadingSkeleton={CreateProductsSkeleton}
        >
            <FormSection icon={Package} title="Información Básica">
                <div className="flex items-center justify-between rounded-lg border p-4">
                    <Label htmlFor="is_active" className="text-base">
                        Activo
                    </Label>
                    <Switch id="is_active" checked={formData.is_active} onCheckedChange={(checked) => handleInputChange('is_active', checked as boolean)} />
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
                    <Textarea id="description" value={formData.description} onChange={(e) => handleInputChange('description', e.target.value)} rows={2} />
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
        </CreatePageLayout>
    );
}
