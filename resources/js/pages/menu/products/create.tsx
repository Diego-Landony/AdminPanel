import { showNotification } from '@/hooks/useNotifications';
import { useForm } from '@inertiajs/react';
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
    const { data, setData, post, processing, errors, reset } = useForm({
        category_id: '',
        name: '',
        description: '',
        image: '',
        is_active: true,
        has_variants: false,
        precio_pickup_capital: '',
        precio_domicilio_capital: '',
        precio_pickup_interior: '',
        precio_domicilio_interior: '',
        variants: [] as VariantData[],
        sections: [] as number[],
    });

    const [selectedCategory, setSelectedCategory] = useState<Category | null>(null);
    const [selectedSections, setSelectedSections] = useState<number[]>([]);

    useEffect(() => {
        if (data.category_id) {
            const category = categories.find((c) => c.id === Number(data.category_id));
            setSelectedCategory(category || null);
            setData('has_variants', category?.uses_variants || false);
        } else {
            setSelectedCategory(null);
            setData('has_variants', false);
        }
    }, [data.category_id, categories, setData]);

    const handleCategoryChange = (value: number | null) => {
        setData('category_id', value ? String(value) : '');
    };

    const handleVariantsChange = (variants: VariantData[]) => {
        setData('variants', variants);
    };

    const toggleSection = (sectionId: number) => {
        const newSelected = selectedSections.includes(sectionId)
            ? selectedSections.filter((id) => id !== sectionId)
            : [...selectedSections, sectionId];

        setSelectedSections(newSelected);
        setData('sections', newSelected);
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        const submitData = {
            ...data,
            sections: selectedSections,
            variants: data.has_variants ? data.variants.filter((v) => v.is_active).map(({ is_active: _is_active, ...rest }) => rest) : [],
        };

        post(route('menu.products.store'), {
            ...submitData,
            onSuccess: () => {
                reset();
                setSelectedSections([]);
            },
            onError: (errors: Record<string, string>) => {
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
                    <Switch id="is_active" checked={data.is_active} onCheckedChange={(checked) => setData('is_active', checked as boolean)} />
                </div>

                <CategoryCombobox
                    value={data.category_id ? Number(data.category_id) : null}
                    onChange={handleCategoryChange}
                    categories={categories}
                    label="Categoría"
                    error={errors.category_id}
                    required
                />

                <FormField label="Nombre" error={errors.name} required>
                    <Input id="name" type="text" value={data.name} onChange={(e) => setData('name', e.target.value)} />
                </FormField>

                <FormField label="Descripción" error={errors.description}>
                    <Textarea id="description" value={data.description} onChange={(e) => setData('description', e.target.value)} rows={2} />
                </FormField>

                <ImageUpload label="Imagen" currentImage={data.image} onImageChange={(url) => setData('image', url || '')} error={errors.image} />
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
                        capitalPickup={data.precio_pickup_capital}
                        capitalDomicilio={data.precio_domicilio_capital}
                        interiorPickup={data.precio_pickup_interior}
                        interiorDomicilio={data.precio_domicilio_interior}
                        onChangeCapitalPickup={(value) => setData('precio_pickup_capital', value)}
                        onChangeCapitalDomicilio={(value) => setData('precio_domicilio_capital', value)}
                        onChangeInteriorPickup={(value) => setData('precio_pickup_interior', value)}
                        onChangeInteriorDomicilio={(value) => setData('precio_domicilio_interior', value)}
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
