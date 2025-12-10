import { EditPageLayout } from '@/components/edit-page-layout';
import { CategoryFormFields } from '@/components/forms/CategoryFormFields';
import { EditCategoriesSkeleton } from '@/components/skeletons';
import { useCategoryForm } from '@/hooks/useCategoryForm';

import type { Category } from '@/types/menu';

interface EditPageProps {
    category: Category;
}

export default function CategoryEdit({ category }: EditPageProps) {
    const form = useCategoryForm({
        mode: 'edit',
        category,
    });

    return (
        <EditPageLayout
            title="Editar Categoría"
            description={`Modifica los datos de la categoría "${category.name}"`}
            backHref={route('menu.categories.index')}
            backLabel="Volver"
            onSubmit={form.handleSubmit}
            submitLabel="Guardar"
            processing={form.processing}
            pageTitle={`Editar ${category.name}`}
            loading={false}
            loadingSkeleton={EditCategoriesSkeleton}
        >
            <CategoryFormFields
                formData={form.formData}
                onInputChange={form.handleInputChange}
                errors={form.errors}
                variantsChanged={form.variantsChanged}
                mode="edit"
            />
        </EditPageLayout>
    );
}
