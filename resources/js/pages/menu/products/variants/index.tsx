import { showNotification } from '@/hooks/useNotifications';
import { Head, Link, router } from '@inertiajs/react';
import { useCallback, useState } from 'react';

import { DataTable } from '@/components/DataTable';
import { DeleteConfirmationDialog } from '@/components/DeleteConfirmationDialog';
import { EntityInfoCell } from '@/components/EntityInfoCell';
import { StandardMobileCard } from '@/components/StandardMobileCard';
import { TableActions } from '@/components/TableActions';
import { ProductsSkeleton } from '@/components/skeletons';
import { ACTIVE_STATUS_CONFIGS, StatusBadge } from '@/components/status-badge';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { ArrowLeft, Banknote, Package, Star } from 'lucide-react';

interface Product {
    id: number;
    name: string;
}

interface ProductVariant {
    id: number;
    product_id: number;
    sku: string;
    name: string;
    size: string;
    precio_pickup_capital: number;
    precio_domicilio_capital: number;
    precio_pickup_interior: number;
    precio_domicilio_interior: number;
    is_daily_special: boolean;
    daily_special_days: number[] | null;
    is_active: boolean;
    sort_order: number;
    created_at: string;
    updated_at: string;
}

interface VariantsPageProps {
    product: Product;
    variants: {
        data: ProductVariant[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        from: number;
        to: number;
    };
    stats: {
        total_variants: number;
        active_variants: number;
        daily_specials: number;
    };
    filters: {
        search: string | null;
        per_page: number;
        sort_field?: string;
        sort_direction?: 'asc' | 'desc';
    };
}

const VariantInfoCell: React.FC<{ variant: ProductVariant }> = ({ variant }) => (
    <EntityInfoCell icon={Package} primaryText={variant.size} secondaryText={`SKU: ${variant.sku}`} />
);

const VariantMobileCard: React.FC<{ variant: ProductVariant; onDelete: (variant: ProductVariant) => void; isDeleting: boolean; productId: number }> = ({
    variant,
    onDelete,
    isDeleting,
    productId,
}) => (
    <StandardMobileCard
        icon={Package}
        title={variant.size}
        subtitle={`SKU: ${variant.sku}`}
        badge={{
            children: (
                <div className="flex items-center gap-2">
                    <StatusBadge status={variant.is_active ? 'active' : 'inactive'} configs={ACTIVE_STATUS_CONFIGS} showIcon={false} />
                    {variant.is_daily_special && (
                        <Badge variant="default" className="bg-amber-500">
                            <Star className="h-3 w-3 mr-1" />
                            Sub del Día
                        </Badge>
                    )}
                </div>
            ),
        }}
        dataFields={[
            {
                label: 'Precio Pickup Capital',
                value: (
                    <div className="flex items-center gap-2">
                        <Banknote className="h-4 w-4 text-muted-foreground" />
                        <span>Q{Number(variant.precio_pickup_capital).toFixed(2)}</span>
                    </div>
                ),
            },
            {
                label: 'Precio Domicilio Capital',
                value: (
                    <div className="flex items-center gap-2">
                        <Banknote className="h-4 w-4 text-muted-foreground" />
                        <span>Q{Number(variant.precio_domicilio_capital).toFixed(2)}</span>
                    </div>
                ),
            },
        ]}
        actions={{
            editHref: `/menu/products/${productId}/variants/${variant.id}/edit`,
            onDelete: () => onDelete(variant),
            isDeleting,
            editTooltip: 'Editar variante',
            deleteTooltip: 'Eliminar variante',
        }}
    />
);

export default function VariantsIndex({ product, variants, stats, filters }: VariantsPageProps) {
    const [deletingVariant, setDeletingVariant] = useState<number | null>(null);
    const [selectedVariant, setSelectedVariant] = useState<ProductVariant | null>(null);
    const [showDeleteDialog, setShowDeleteDialog] = useState(false);

    const openDeleteDialog = useCallback((variant: ProductVariant) => {
        setSelectedVariant(variant);
        setShowDeleteDialog(true);
    }, []);

    const closeDeleteDialog = useCallback(() => {
        setSelectedVariant(null);
        setShowDeleteDialog(false);
        setDeletingVariant(null);
    }, []);

    const handleDeleteVariant = async () => {
        if (!selectedVariant) return;

        setDeletingVariant(selectedVariant.id);
        router.delete(`/menu/products/${product.id}/variants/${selectedVariant.id}`, {
            preserveState: false,
            onBefore: () => {
                closeDeleteDialog();
            },
            onError: (error) => {
                setDeletingVariant(null);
                if (error.message) {
                    showNotification.error(error.message);
                }
            },
        });
    };

    const columns = [
        {
            key: 'size',
            title: 'Variante / SKU',
            width: 'lg' as const,
            sortable: true,
            render: (variant: ProductVariant) => <VariantInfoCell variant={variant} />,
        },
        {
            key: 'precio_pickup_capital',
            title: 'Precio Pickup Capital',
            width: 'sm' as const,
            textAlign: 'right' as const,
            sortable: true,
            render: (variant: ProductVariant) => (
                <div className="flex items-center justify-end gap-1 text-sm font-medium text-foreground">
                    <Banknote className="h-3 w-3" />
                    <span>Q{Number(variant.precio_pickup_capital).toFixed(2)}</span>
                </div>
            ),
        },
        {
            key: 'precio_domicilio_capital',
            title: 'Precio Domicilio Capital',
            width: 'sm' as const,
            textAlign: 'right' as const,
            sortable: true,
            render: (variant: ProductVariant) => (
                <div className="flex items-center justify-end gap-1 text-sm font-medium text-foreground">
                    <Banknote className="h-3 w-3" />
                    <span>Q{Number(variant.precio_domicilio_capital).toFixed(2)}</span>
                </div>
            ),
        },
        {
            key: 'precio_pickup_interior',
            title: 'Precio Pickup Interior',
            width: 'sm' as const,
            textAlign: 'right' as const,
            sortable: true,
            render: (variant: ProductVariant) => (
                <div className="flex items-center justify-end gap-1 text-sm font-medium text-muted-foreground">
                    <Banknote className="h-3 w-3" />
                    <span>Q{Number(variant.precio_pickup_interior).toFixed(2)}</span>
                </div>
            ),
        },
        {
            key: 'precio_domicilio_interior',
            title: 'Precio Domicilio Interior',
            width: 'sm' as const,
            textAlign: 'right' as const,
            sortable: true,
            render: (variant: ProductVariant) => (
                <div className="flex items-center justify-end gap-1 text-sm font-medium text-muted-foreground">
                    <Banknote className="h-3 w-3" />
                    <span>Q{Number(variant.precio_domicilio_interior).toFixed(2)}</span>
                </div>
            ),
        },
        {
            key: 'is_daily_special',
            title: 'Sub del Día',
            width: 'sm' as const,
            textAlign: 'center' as const,
            render: (variant: ProductVariant) =>
                variant.is_daily_special ? (
                    <Badge variant="default" className="bg-amber-500">
                        <Star className="h-3 w-3 mr-1" />
                        Sí
                    </Badge>
                ) : (
                    <span className="text-sm text-muted-foreground">No</span>
                ),
        },
        {
            key: 'is_active',
            title: 'Estado',
            width: 'sm' as const,
            textAlign: 'center' as const,
            sortable: true,
            render: (variant: ProductVariant) => (
                <StatusBadge status={variant.is_active ? 'active' : 'inactive'} configs={ACTIVE_STATUS_CONFIGS} showIcon={false} />
            ),
        },
        {
            key: 'actions',
            title: 'Acciones',
            width: 'xs' as const,
            textAlign: 'right' as const,
            render: (variant: ProductVariant) => (
                <TableActions
                    editHref={`/menu/products/${product.id}/variants/${variant.id}/edit`}
                    onDelete={() => openDeleteDialog(variant)}
                    isDeleting={deletingVariant === variant.id}
                    editTooltip="Editar variante"
                    deleteTooltip="Eliminar variante"
                />
            ),
        },
    ];

    const variantStats = [
        {
            title: 'variantes',
            value: stats.total_variants,
            icon: <Package className="h-3 w-3 text-primary" />,
        },
        {
            title: 'activas',
            value: stats.active_variants,
            icon: <Package className="h-3 w-3 text-green-600" />,
        },
        {
            title: 'subs del día',
            value: stats.daily_specials,
            icon: <Star className="h-3 w-3 text-amber-500" />,
        },
    ];

    return (
        <AppLayout>
            <Head title={`Variantes - ${product.name}`} />

            {/* Breadcrumb / Back button */}
            <div className="mb-4">
                <Button variant="ghost" size="sm" asChild>
                    <Link href="/menu/products" className="flex items-center gap-2">
                        <ArrowLeft className="h-4 w-4" />
                        Volver a Productos
                    </Link>
                </Button>
            </div>

            <DataTable
                title={`Variantes de ${product.name}`}
                description="Gestiona los precios de cada variante. Las variantes se crean automáticamente cuando agregas el producto a una categoría con variantes."
                data={variants}
                columns={columns}
                stats={variantStats}
                filters={filters}
                createUrl={undefined}
                searchPlaceholder="Buscar por tamaño o SKU..."
                loadingSkeleton={ProductsSkeleton}
                renderMobileCard={(variant) => <VariantMobileCard variant={variant} onDelete={openDeleteDialog} isDeleting={deletingVariant === variant.id} productId={product.id} />}
                routeName={`/menu/products/${product.id}/variants`}
                breakpoint="lg"
            />

            <DeleteConfirmationDialog
                isOpen={showDeleteDialog}
                onClose={closeDeleteDialog}
                onConfirm={handleDeleteVariant}
                isDeleting={deletingVariant !== null}
                entityName={selectedVariant?.size || ''}
                entityType="variante"
            />
        </AppLayout>
    );
}
