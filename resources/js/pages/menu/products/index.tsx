import { showNotification } from '@/hooks/useNotifications';
import { Head, router } from '@inertiajs/react';
import { useState } from 'react';

import { DeleteConfirmationDialog } from '@/components/DeleteConfirmationDialog';
import { GroupedSortableTable } from '@/components/GroupedSortableTable';
import { StandardMobileCard } from '@/components/StandardMobileCard';
import { TableActions } from '@/components/TableActions';
import { ACTIVE_STATUS_CONFIGS, StatusBadge } from '@/components/status-badge';
import AppLayout from '@/layouts/app-layout';
import { Package } from 'lucide-react';

interface ProductVariant {
    id: number;
    name: string;
    size: string;
}

interface Product {
    id: number;
    name: string;
    description: string | null;
    image: string | null;
    is_active: boolean;
    sort_order: number;
    has_variants: boolean;
    variants?: ProductVariant[];
}

interface CategoryGroup {
    category: {
        id: number | null;
        name: string;
    };
    products: Product[];
}

interface ProductsPageProps {
    groupedProducts: CategoryGroup[];
    stats: {
        total_products: number;
        active_products: number;
    };
}

export default function ProductsIndex({ groupedProducts, stats }: ProductsPageProps) {
    const [deletingProduct, setDeletingProduct] = useState<number | null>(null);
    const [selectedProduct, setSelectedProduct] = useState<Product | null>(null);
    const [showDeleteDialog, setShowDeleteDialog] = useState(false);
    const [isSaving, setIsSaving] = useState(false);

    const handleReorder = (reorderedProducts: Product[]) => {
        setIsSaving(true);

        const orderData = reorderedProducts.map((product) => ({
            id: product.id,
            sort_order: product.sort_order,
        }));

        router.post(
            route('menu.products.reorder'),
            { products: orderData },
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

    const openDeleteDialog = (product: Product) => {
        setSelectedProduct(product);
        setShowDeleteDialog(true);
    };

    const closeDeleteDialog = () => {
        setSelectedProduct(null);
        setShowDeleteDialog(false);
        setDeletingProduct(null);
    };

    const handleDeleteProduct = () => {
        if (!selectedProduct) return;

        setDeletingProduct(selectedProduct.id);
        router.delete(`/menu/products/${selectedProduct.id}`, {
            onSuccess: () => {
                closeDeleteDialog();
            },
            onError: (error) => {
                setDeletingProduct(null);
                if (error.message) {
                    showNotification.error(error.message);
                }
            },
        });
    };

    const columns = [
        {
            key: 'name',
            title: 'Producto',
            width: 'w-64',
            render: (product: Product) => (
                <div className="flex items-center gap-3">
                    {product.image && (
                        <img
                            src={product.image}
                            alt={product.name}
                            className="h-10 w-10 rounded-md object-cover"
                        />
                    )}
                    <div className="text-sm font-medium text-foreground truncate">{product.name}</div>
                </div>
            ),
        },
        {
            key: 'variants',
            title: 'Variantes',
            width: 'w-64',
            render: (product: Product) => (
                <div className="text-sm text-muted-foreground">
                    {product.has_variants && product.variants && product.variants.length > 0 ? (
                        <ul className="list-disc list-inside space-y-1">
                            {product.variants.map((variant) => (
                                <li key={variant.id} className="text-xs">
                                    {variant.name}
                                </li>
                            ))}
                        </ul>
                    ) : (
                        <span className="text-muted-foreground/50">—</span>
                    )}
                </div>
            ),
        },
        {
            key: 'status',
            title: 'Estado',
            width: 'w-32',
            textAlign: 'center' as const,
            render: (product: Product) => (
                <div className="flex justify-center">
                    <StatusBadge status={product.is_active ? 'active' : 'inactive'} configs={ACTIVE_STATUS_CONFIGS} showIcon={false} />
                </div>
            ),
        },
        {
            key: 'actions',
            title: 'Acciones',
            width: 'w-24',
            textAlign: 'right' as const,
            render: (product: Product) => (
                <TableActions
                    editHref={`/menu/products/${product.id}/edit`}
                    onDelete={() => openDeleteDialog(product)}
                    isDeleting={deletingProduct === product.id}
                    editTooltip="Editar producto"
                    deleteTooltip="Eliminar producto"
                />
            ),
        },
    ];

    const renderMobileCard = (product: Product) => (
        <StandardMobileCard
            title={product.name}
            subtitle={product.description || 'Sin descripción'}
            imageUrl={product.image || undefined}
            badge={{
                children: <StatusBadge status={product.is_active ? 'active' : 'inactive'} configs={ACTIVE_STATUS_CONFIGS} showIcon={false} />,
            }}
            actions={{
                editHref: `/menu/products/${product.id}/edit`,
                onDelete: () => openDeleteDialog(product),
                isDeleting: deletingProduct === product.id,
                editTooltip: 'Editar producto',
                deleteTooltip: 'Eliminar producto',
            }}
            dataFields={
                product.has_variants && product.variants && product.variants.length > 0
                    ? [
                          {
                              label: 'Variantes',
                              value: (
                                  <ul className="space-y-1">
                                      {product.variants.map((variant) => (
                                          <li key={variant.id} className="text-xs">
                                              {variant.name}
                                          </li>
                                      ))}
                                  </ul>
                              ),
                          },
                      ]
                    : undefined
            }
        />
    );

    const productStats = [
        {
            title: 'productos',
            value: stats.total_products,
            icon: <Package className="h-4 w-4 text-primary" />,
        },
        {
            title: 'activos',
            value: stats.active_products,
            icon: <Package className="h-4 w-4 text-green-600" />,
        },
        {
            title: 'inactivos',
            value: stats.total_products - stats.active_products,
            icon: <Package className="h-4 w-4 text-red-600" />,
        },
    ];

    return (
        <AppLayout>
            <Head title="Productos" />

            <GroupedSortableTable
                title="Productos de Menú"
                groupedData={groupedProducts}
                columns={columns}
                stats={productStats}
                createUrl="/menu/products/create"
                createLabel="Crear"
                searchable={true}
                searchPlaceholder="Buscar productos..."
                onReorder={handleReorder}
                onRefresh={handleRefresh}
                isSaving={isSaving}
                renderMobileCard={renderMobileCard}
                breakpoint="lg"
            />

            <DeleteConfirmationDialog
                isOpen={showDeleteDialog}
                onClose={closeDeleteDialog}
                onConfirm={handleDeleteProduct}
                isDeleting={deletingProduct !== null}
                entityName={selectedProduct?.name || ''}
                entityType="producto"
            />
        </AppLayout>
    );
}
