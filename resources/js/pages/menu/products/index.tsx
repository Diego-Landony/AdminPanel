import { showNotification } from '@/hooks/useNotifications';
import { Head, router } from '@inertiajs/react';
import { useCallback, useState } from 'react';

import { DataTable } from '@/components/DataTable';
import { DeleteConfirmationDialog } from '@/components/DeleteConfirmationDialog';
import { EntityInfoCell } from '@/components/EntityInfoCell';
import { StandardMobileCard } from '@/components/StandardMobileCard';
import { TableActions } from '@/components/TableActions';
import { ProductsSkeleton } from '@/components/skeletons';
import { ACTIVE_STATUS_CONFIGS, StatusBadge } from '@/components/status-badge';
import AppLayout from '@/layouts/app-layout';
import { Package, Star } from 'lucide-react';

interface Product {
    id: number;
    name: string;
    description: string | null;
    image: string | null;
    is_customizable: boolean;
    is_active: boolean;
    sections_count: number;
    created_at: string;
    updated_at: string;
}

interface ProductsPageProps {
    products: {
        data: Product[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        from: number;
        to: number;
    };
    stats: {
        total_products: number;
        active_products: number;
    };
    filters: {
        search: string | null;
        per_page: number;
        sort_field?: string;
        sort_direction?: 'asc' | 'desc';
    };
}

const ProductInfoCell: React.FC<{ product: Product }> = ({ product }) => (
    <EntityInfoCell
        icon={Package}
        primaryText={product.name}
        secondaryText={product.description}
        imageUrl={product.image}
    />
);

const ProductMobileCard: React.FC<{ product: Product; onDelete: (product: Product) => void; isDeleting: boolean }> = ({
    product,
    onDelete,
    isDeleting,
}) => (
    <StandardMobileCard
        icon={Package}
        title={product.name}
        subtitle={product.description}
        imageUrl={product.image || undefined}
        badge={{
            children: <StatusBadge status={product.is_active ? 'active' : 'inactive'} configs={ACTIVE_STATUS_CONFIGS} showIcon={false} />,
        }}
        dataFields={[
            {
                label: 'Secciones',
                value: `${product.sections_count} secciones`,
            },
            {
                label: 'Personalizable',
                value: product.is_customizable ? 'Sí' : 'No',
            },
        ]}
        actions={{
            editHref: `/menu/products/${product.id}/edit`,
            onDelete: () => onDelete(product),
            isDeleting,
            editTooltip: 'Editar producto',
            deleteTooltip: 'Eliminar producto',
        }}
    />
);

export default function ProductsIndex({ products, stats, filters }: ProductsPageProps) {
    const [deletingProduct, setDeletingProduct] = useState<number | null>(null);
    const [selectedProduct, setSelectedProduct] = useState<Product | null>(null);
    const [showDeleteDialog, setShowDeleteDialog] = useState(false);

    const openDeleteDialog = useCallback((product: Product) => {
        setSelectedProduct(product);
        setShowDeleteDialog(true);
    }, []);

    const closeDeleteDialog = useCallback(() => {
        setSelectedProduct(null);
        setShowDeleteDialog(false);
        setDeletingProduct(null);
    }, []);

    const handleDeleteProduct = async () => {
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
            key: 'product',
            title: 'Producto',
            width: 'lg' as const,
            sortable: true,
            render: (product: Product) => <ProductInfoCell product={product} />,
        },
        {
            key: 'sections_count',
            title: 'Secciones',
            width: 'sm' as const,
            textAlign: 'center' as const,
            render: (product: Product) => (
                <span className="text-sm text-muted-foreground">{product.sections_count}</span>
            ),
        },
        {
            key: 'is_customizable',
            title: 'Personalizable',
            width: 'sm' as const,
            textAlign: 'center' as const,
            sortable: true,
            render: (product: Product) => (
                <span className="text-sm text-muted-foreground">{product.is_customizable ? 'Sí' : 'No'}</span>
            ),
        },
        {
            key: 'is_active',
            title: 'Estado',
            width: 'sm' as const,
            textAlign: 'center' as const,
            sortable: true,
            render: (product: Product) => (
                <StatusBadge status={product.is_active ? 'active' : 'inactive'} configs={ACTIVE_STATUS_CONFIGS} showIcon={false} />
            ),
        },
        {
            key: 'actions',
            title: 'Acciones',
            width: 'sm' as const,
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

    const productStats = [
        {
            title: 'productos',
            value: stats.total_products,
            icon: <Package className="h-3 w-3 text-primary" />,
        },
        {
            title: 'activos',
            value: stats.active_products,
            icon: <Star className="h-3 w-3 text-green-600" />,
        },
        {
            title: 'inactivos',
            value: stats.total_products - stats.active_products,
            icon: <Package className="h-3 w-3 text-red-600" />,
        },
    ];

    return (
        <AppLayout>
            <Head title="Productos" />

            <DataTable
                title="Productos de Menú"
                description="Gestiona los productos de tu menú, define variantes con precios y personaliza con secciones adicionales."
                data={products}
                columns={columns}
                stats={productStats}
                filters={filters}
                createUrl="/menu/products/create"
                createLabel="Crear Producto"
                searchPlaceholder="Buscar productos..."
                loadingSkeleton={ProductsSkeleton}
                renderMobileCard={(product) => <ProductMobileCard product={product} onDelete={openDeleteDialog} isDeleting={deletingProduct === product.id} />}
                routeName="/menu/products"
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
