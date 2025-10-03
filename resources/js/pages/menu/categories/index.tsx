import { showNotification } from '@/hooks/useNotifications';
import { Head, router } from '@inertiajs/react';
import { useState } from 'react';

import { DeleteConfirmationDialog } from '@/components/DeleteConfirmationDialog';
import { EntityInfoCell } from '@/components/EntityInfoCell';
import { SortableTable } from '@/components/SortableTable';
import { StandardMobileCard } from '@/components/StandardMobileCard';
import { TableActions } from '@/components/TableActions';
import { ACTIVE_STATUS_CONFIGS, StatusBadge } from '@/components/status-badge';
import AppLayout from '@/layouts/app-layout';
import { Layers, Package, Star } from 'lucide-react';

interface Category {
    id: number;
    name: string;
    image: string | null;
    is_active: boolean;
    sort_order: number;
    created_at: string;
    updated_at: string;
}

interface CategoriesPageProps {
    categories: Category[];
    stats: {
        total_categories: number;
        active_categories: number;
    };
}

export default function CategoriesIndex({ categories, stats }: CategoriesPageProps) {
    const [deletingCategory, setDeletingCategory] = useState<number | null>(null);
    const [selectedCategory, setSelectedCategory] = useState<Category | null>(null);
    const [showDeleteDialog, setShowDeleteDialog] = useState(false);
    const [isSaving, setIsSaving] = useState(false);

    const handleReorder = (reorderedCategories: Category[]) => {
        setIsSaving(true);

        const orderData = reorderedCategories.map((category, index) => ({
            id: category.id,
            sort_order: index + 1,
        }));

        router.post(
            route('menu.categories.reorder'),
            { categories: orderData },
            {
                preserveState: true,
                onSuccess: () => {
                    showNotification.success('Orden guardado correctamente');
                },
                onError: (error) => {
                    if (error.message) {
                        showNotification.error(error.message);
                    }
                },
                onFinish: () => {
                    setIsSaving(false);
                },
            }
        );
    };

    const handleRefresh = () => {
        router.reload();
    };

    const openDeleteDialog = (category: Category) => {
        setSelectedCategory(category);
        setShowDeleteDialog(true);
    };

    const closeDeleteDialog = () => {
        setSelectedCategory(null);
        setShowDeleteDialog(false);
        setDeletingCategory(null);
    };

    const handleDeleteCategory = () => {
        if (!selectedCategory) return;

        setDeletingCategory(selectedCategory.id);
        router.delete(`/menu/categories/${selectedCategory.id}`, {
            onSuccess: () => {
                closeDeleteDialog();
            },
            onError: (error) => {
                setDeletingCategory(null);
                if (error.message) {
                    showNotification.error(error.message);
                }
            },
        });
    };

    const formatDate = (dateString: string): string => {
        return new Date(dateString).toLocaleDateString('es-ES', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
        });
    };

    const categoryStats = [
        {
            title: 'categorías',
            value: stats.total_categories,
            icon: <Layers className="h-3 w-3 text-primary" />,
        },
        {
            title: 'activas',
            value: stats.active_categories,
            icon: <Star className="h-3 w-3 text-green-600" />,
        },
        {
            title: 'inactivas',
            value: stats.total_categories - stats.active_categories,
            icon: <Package className="h-3 w-3 text-red-600" />,
        },
    ];

    const columns = [
        {
            key: 'name',
            title: 'Categoría',
            width: 'w-48 min-w-48 max-w-64',
            render: (category: Category) => (
                <EntityInfoCell
                    icon={Layers}
                    primaryText={category.name}
                    secondaryText={`#${category.sort_order}`}
                    badges={<StatusBadge status={category.is_active ? 'active' : 'inactive'} configs={ACTIVE_STATUS_CONFIGS} showIcon={false} />}
                />
            ),
        },
        {
            key: 'created_at',
            title: 'Creado',
            width: 'w-24',
            textAlign: 'center' as const,
            render: (category: Category) => (
                <div className="text-sm text-muted-foreground">{formatDate(category.created_at)}</div>
            ),
        },
        {
            key: 'actions',
            title: 'Acciones',
            width: 'w-16',
            textAlign: 'right' as const,
            render: (category: Category) => (
                <TableActions
                    editHref={`/menu/categories/${category.id}/edit`}
                    onDelete={() => openDeleteDialog(category)}
                    isDeleting={deletingCategory === category.id}
                    editTooltip="Editar categoría"
                    deleteTooltip="Eliminar categoría"
                />
            ),
        },
    ];

    const renderMobileCard = (category: Category) => (
        <StandardMobileCard
            icon={Layers}
            title={category.name}
            subtitle={`Orden #${category.sort_order}`}
            badge={{
                children: <StatusBadge status={category.is_active ? 'active' : 'inactive'} configs={ACTIVE_STATUS_CONFIGS} showIcon={false} />,
            }}
            dataFields={[
                {
                    label: 'Creado',
                    value: formatDate(category.created_at),
                },
            ]}
            actions={{
                editHref: `/menu/categories/${category.id}/edit`,
                onDelete: () => openDeleteDialog(category),
                isDeleting: deletingCategory === category.id,
                editTooltip: 'Editar categoría',
                deleteTooltip: 'Eliminar categoría',
            }}
        />
    );

    return (
        <AppLayout>
            <Head title="Categorías" />

            <SortableTable
                title="Categorías de Menú"
                description="Administra las categorías del menú"
                data={categories}
                columns={columns}
                stats={categoryStats}
                createUrl="/menu/categories/create"
                createLabel="Crear Categoría"
                searchable={true}
                searchPlaceholder="Buscar categorías..."
                onReorder={handleReorder}
                onRefresh={handleRefresh}
                isSaving={isSaving}
                renderMobileCard={renderMobileCard}
                breakpoint="lg"
            />

            <DeleteConfirmationDialog
                isOpen={showDeleteDialog}
                onClose={closeDeleteDialog}
                onConfirm={handleDeleteCategory}
                isDeleting={deletingCategory !== null}
                entityName={selectedCategory?.name || ''}
                entityType="categoría"
            />
        </AppLayout>
    );
}
