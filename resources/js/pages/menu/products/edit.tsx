import { EditPageLayout } from '@/components/edit-page-layout';
import { ProductFormFields } from '@/components/forms/ProductFormFields';
import { EditProductsSkeleton } from '@/components/skeletons';
import { useProductForm } from '@/hooks/useProductForm';

import type { Category, Section, Product } from '@/types/menu';

interface EditProductPageProps {
    product: Product;
    categories: Category[];
    sections: Section[];
}

export default function ProductEdit({ product, categories, sections }: EditProductPageProps) {
    const form = useProductForm({
        mode: 'edit',
        product,
        categories,
        sections,
    });

    return (
        <EditPageLayout
            title="Editar Producto"
            description={`Modifica los datos del producto "${product.name}"`}
            backHref={route('menu.products.index')}
            backLabel="Volver"
            onSubmit={form.handleSubmit}
            submitLabel="Guardar"
            processing={form.processing}
            pageTitle={`Editar ${product.name}`}
            loading={false}
            loadingSkeleton={EditProductsSkeleton}
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
                existingVariants={form.existingVariants}
            />
        </EditPageLayout>
    );
}
