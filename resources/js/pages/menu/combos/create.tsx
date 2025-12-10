import { CreatePageLayout } from '@/components/create-page-layout';
import { ComboFormFields } from '@/components/forms/ComboFormFields';
import { CreateProductsSkeleton } from '@/components/skeletons';
import { useComboForm } from '@/hooks/useComboForm';

import type { Category, Product } from '@/types/menu';

interface CreateComboPageProps {
    products: Product[];
    categories: Category[];
}

export default function ComboCreate({ products, categories }: CreateComboPageProps) {
    const form = useComboForm({
        mode: 'create',
        products,
        categories,
    });

    return (
        <CreatePageLayout
            title="Nuevo Combo"
            description="Crea un nuevo combo seleccionando productos y definiendo precios"
            backHref={route('menu.combos.index')}
            backLabel="Volver"
            onSubmit={form.handleSubmit}
            submitLabel="Guardar"
            processing={form.processing}
            pageTitle="Crear Combo"
            loading={form.processing}
            loadingSkeleton={CreateProductsSkeleton}
        >
            <ComboFormFields
                formData={form.formData}
                onInputChange={form.handleInputChange}
                categories={categories}
                products={products}
                imagePreview={form.imagePreview}
                onImageChange={form.handleImageChange}
                localItems={form.localItems}
                onAddItem={form.addItem}
                onRemoveItem={form.removeItem}
                onUpdateItem={form.updateItem}
                onBatchUpdateItem={form.batchUpdateItem}
                onDragEnd={form.handleDragEnd}
                sensors={form.sensors}
                errors={form.errors}
                inactiveItems={form.inactiveItems}
                canDeleteItem={form.canDeleteItem}
                mode="create"
            />
        </CreatePageLayout>
    );
}
