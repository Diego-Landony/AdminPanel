import { EditPageLayout } from '@/components/edit-page-layout';
import { ComboFormFields } from '@/components/forms/ComboFormFields';
import { EditProductsSkeleton } from '@/components/skeletons';
import { useComboForm } from '@/hooks/useComboForm';

import type { Category, Product, Combo } from '@/types/menu';

interface EditComboPageProps {
    combo: Combo;
    products: Product[];
    categories: Category[];
}

export default function ComboEdit({ combo, products, categories }: EditComboPageProps) {
    const form = useComboForm({
        mode: 'edit',
        combo,
        products,
        categories,
    });

    return (
        <EditPageLayout
            title="Editar Combo"
            description={`Modifica los datos del combo "${combo.name}"`}
            backHref={route('menu.combos.index')}
            backLabel="Volver"
            onSubmit={form.handleSubmit}
            submitLabel="Guardar"
            processing={form.processing}
            pageTitle={`Editar ${combo.name}`}
            loading={false}
            loadingSkeleton={EditProductsSkeleton}
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
                mode="edit"
            />
        </EditPageLayout>
    );
}
