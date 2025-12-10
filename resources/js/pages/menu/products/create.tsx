import { CreatePageLayout } from '@/components/create-page-layout';
import { ProductFormFields } from '@/components/forms/ProductFormFields';
import { CreateProductsSkeleton } from '@/components/skeletons';
import { useProductForm } from '@/hooks/useProductForm';

import type { Category, Section } from '@/types/menu';

interface CreateProductPageProps {
    categories: Category[];
    sections: Section[];
}

export default function ProductCreate({ categories, sections }: CreateProductPageProps) {
    const form = useProductForm({
        mode: 'create',
        categories,
        sections,
    });

    return (
        <CreatePageLayout
            title="Nuevo Producto"
            backHref={route('menu.products.index')}
            backLabel="Volver"
            onSubmit={form.handleSubmit}
            submitLabel="Guardar"
            processing={form.processing}
            pageTitle="Crear Producto"
            loading={form.processing}
            loadingSkeleton={CreateProductsSkeleton}
        >
            <ProductFormFields
                formData={form.formData}
                onInputChange={form.handleInputChange}
                categories={categories}
                sections={sections}
                selectedSections={form.selectedSections}
                onToggleSection={form.toggleSection}
                selectedCategory={form.selectedCategory}
                imagePreview={form.imagePreview}
                onImageChange={form.handleImageChange}
                onVariantsChange={form.handleVariantsChange}
                onCategoryChange={form.handleCategoryChange}
                errors={form.errors}
            />
        </CreatePageLayout>
    );
}
