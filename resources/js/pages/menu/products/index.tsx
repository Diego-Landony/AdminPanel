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
    precio_pickup_capital: number;
    precio_domicilio_capital: number;
    precio_pickup_interior: number;
    precio_domicilio_interior: number;
}

interface Product {
    id: number;
    name: string;
    description: string | null;
    image: string | null;
    is_active: boolean;
    sort_order: number;
    has_variants: boolean;
    precio_pickup_capital: number;
    precio_domicilio_capital: number;
    precio_pickup_interior: number;
    precio_domicilio_interior: number;
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
    const [comboUsageInfo, setComboUsageInfo] = useState<{ used_in_combos: boolean; combos: string[]; count: number } | null>(null);

    const getPriceRange = (product: Product) => {
        if (product.has_variants && product.variants && product.variants.length > 0) {
            const pickupCapital = product.variants.map((v) => Number(v.precio_pickup_capital));
            const domicilioCapital = product.variants.map((v) => Number(v.precio_domicilio_capital));
            const pickupInterior = product.variants.map((v) => Number(v.precio_pickup_interior));
            const domicilioInterior = product.variants.map((v) => Number(v.precio_domicilio_interior));

            const minPickupCapital = Math.min(...pickupCapital);
            const maxPickupCapital = Math.max(...pickupCapital);
            const minDomicilioCapital = Math.min(...domicilioCapital);
            const maxDomicilioCapital = Math.max(...domicilioCapital);
            const minPickupInterior = Math.min(...pickupInterior);
            const maxPickupInterior = Math.max(...pickupInterior);
            const minDomicilioInterior = Math.min(...domicilioInterior);
            const maxDomicilioInterior = Math.max(...domicilioInterior);

            return {
                capital: {
                    pickup:
                        minPickupCapital === maxPickupCapital
                            ? `Q${minPickupCapital.toFixed(2)}`
                            : `Q${minPickupCapital.toFixed(2)} - Q${maxPickupCapital.toFixed(2)}`,
                    domicilio:
                        minDomicilioCapital === maxDomicilioCapital
                            ? `Q${minDomicilioCapital.toFixed(2)}`
                            : `Q${minDomicilioCapital.toFixed(2)} - Q${maxDomicilioCapital.toFixed(2)}`,
                },
                interior: {
                    pickup:
                        minPickupInterior === maxPickupInterior
                            ? `Q${minPickupInterior.toFixed(2)}`
                            : `Q${minPickupInterior.toFixed(2)} - Q${maxPickupInterior.toFixed(2)}`,
                    domicilio:
                        minDomicilioInterior === maxDomicilioInterior
                            ? `Q${minDomicilioInterior.toFixed(2)}`
                            : `Q${minDomicilioInterior.toFixed(2)} - Q${maxDomicilioInterior.toFixed(2)}`,
                },
            };
        }

        return {
            capital: {
                pickup: `Q${Number(product.precio_pickup_capital).toFixed(2)}`,
                domicilio: `Q${Number(product.precio_domicilio_capital).toFixed(2)}`,
            },
            interior: {
                pickup: `Q${Number(product.precio_pickup_interior).toFixed(2)}`,
                domicilio: `Q${Number(product.precio_domicilio_interior).toFixed(2)}`,
            },
        };
    };

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
                onError: (error) => {
                    if (error.message) {
                        showNotification.error(error.message);
                    }
                },
                onFinish: () => {
                    setIsSaving(false);
                },
            },
        );
    };

    const handleRefresh = () => {
        router.reload();
    };

    const openDeleteDialog = async (product: Product) => {
        setSelectedProduct(product);

        // Consultar información de uso del producto
        try {
            const response = await fetch(route('menu.products.usage-info', product.id));
            const data = await response.json();
            setComboUsageInfo(data);
        } catch (error) {
            console.error('Error fetching usage info:', error);
            setComboUsageInfo(null);
        }

        setShowDeleteDialog(true);
    };

    const closeDeleteDialog = () => {
        setSelectedProduct(null);
        setShowDeleteDialog(false);
        setDeletingProduct(null);
        setComboUsageInfo(null);
    };

    const handleDeleteProduct = () => {
        if (!selectedProduct) return;

        setDeletingProduct(selectedProduct.id);
        closeDeleteDialog();

        router.delete(route('menu.products.destroy', selectedProduct.id), {
            preserveState: false,
            onFinish: () => {
                setDeletingProduct(null);
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
                    {product.image && <img src={product.image} alt={product.name} className="h-10 w-10 rounded-md object-cover" />}
                    <div className="truncate text-sm font-medium text-foreground">{product.name}</div>
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
                        <ul className="list-inside list-disc space-y-1">
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
            key: 'prices',
            title: 'Precios',
            width: 'w-80',
            render: (product: Product) => {
                const prices = getPriceRange(product);
                return (
                    <div className="space-y-0.5 text-xs">
                        <div className="flex items-center gap-2">
                            <span className="min-w-[3.5rem] text-muted-foreground">Capital:</span>
                            <span className="font-medium tabular-nums">
                                {prices.capital.pickup} / {prices.capital.domicilio}
                            </span>
                        </div>
                        <div className="flex items-center gap-2">
                            <span className="min-w-[3.5rem] text-muted-foreground">Interior:</span>
                            <span className="font-medium tabular-nums">
                                {prices.interior.pickup} / {prices.interior.domicilio}
                            </span>
                        </div>
                    </div>
                );
            },
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

    const renderMobileCard = (product: Product) => {
        const dataFields = [];
        const prices = getPriceRange(product);

        if (product.has_variants && product.variants && product.variants.length > 0) {
            dataFields.push({
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
            });
        }

        dataFields.push({
            label: 'Precios',
            value: (
                <div className="space-y-1 text-xs">
                    <div className="flex justify-between gap-2">
                        <span className="text-muted-foreground">Capital:</span>
                        <span className="font-medium tabular-nums">
                            {prices.capital.pickup} / {prices.capital.domicilio}
                        </span>
                    </div>
                    <div className="flex justify-between gap-2">
                        <span className="text-muted-foreground">Interior:</span>
                        <span className="font-medium tabular-nums">
                            {prices.interior.pickup} / {prices.interior.domicilio}
                        </span>
                    </div>
                </div>
            ),
        });

        return (
            <StandardMobileCard
                title={product.name}
                subtitle={product.description || 'Sin descripción'}
                image={product.image}
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
                dataFields={dataFields}
            />
        );
    };

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
                customMessage={
                    comboUsageInfo?.used_in_combos
                        ? `¿Estás seguro de que quieres eliminar el producto "${selectedProduct?.name}"?\n\n⚠️ ADVERTENCIA: Este producto está siendo usado en ${comboUsageInfo.count} combo(s):\n${comboUsageInfo.combos.join(', ')}\n\nAl eliminarlo, será removido de estos combos. Esta acción no se puede deshacer.`
                        : undefined
                }
            />
        </AppLayout>
    );
}
