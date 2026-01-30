import { CreatePageLayout } from '@/components/create-page-layout';
import { CategoryFormFields } from '@/components/forms/CategoryFormFields';
import { CreateCategoriesSkeleton } from '@/components/skeletons';
import { useCategoryForm } from '@/hooks/useCategoryForm';

/**
 * Página para crear una categoría de menú
 */
export default function CategoryCreate() {
    const form = useCategoryForm({
        mode: 'create',
    });

    return (
        <CreatePageLayout
            title="Nueva Categoría"
            backHref={route('menu.categories.index')}
            backLabel="Volver"
            onSubmit={form.handleSubmit}
            submitLabel="Guardar"
            processing={form.processing}
            pageTitle="Crear Categoría"
            loading={form.processing}
            loadingSkeleton={CreateCategoriesSkeleton}
        >
            <CategoryFormFields
                formData={form.formData}
                onInputChange={form.handleInputChange}
                errors={form.errors}
                mode="create"
            />
        </CreatePageLayout>
    );
}
