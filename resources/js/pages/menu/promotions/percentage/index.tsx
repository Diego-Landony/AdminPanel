import { showNotification } from '@/hooks/useNotifications';
import { Head, router } from '@inertiajs/react';
import { useCallback, useState } from 'react';

import { DataTable } from '@/components/DataTable';
import { DeleteConfirmationDialog } from '@/components/DeleteConfirmationDialog';
import { StandardMobileCard } from '@/components/StandardMobileCard';
import { TableActions } from '@/components/TableActions';
import { DataTableSkeleton } from '@/components/skeletons';
import { PROMOTION_STATUS_CONFIGS, StatusBadge } from '@/components/status-badge';
import { PLACEHOLDERS } from '@/constants/ui-constants';
import AppLayout from '@/layouts/app-layout';
import { formatDate } from '@/utils/format';
import { CheckCircle, Clock, Percent } from 'lucide-react';

interface Promotion {
    id: number;
    name: string;
    description: string | null;
    type: 'percentage_discount';
    is_active: boolean;
    items_count: number;
    created_at: string;
    updated_at: string;
}

interface PromotionsPageProps {
    promotions: {
        data: Promotion[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        from: number;
        to: number;
    };
    stats: {
        total_promotions: number;
        active_promotions: number;
        valid_now_promotions: number;
    };
    filters: {
        search: string | null;
        per_page: number;
        sort_field?: string;
        sort_direction?: 'asc' | 'desc';
    };
}

const PromotionMobileCard: React.FC<{
    promotion: Promotion;
    onDelete: (promotion: Promotion) => void;
    onToggle: (promotion: Promotion) => void;
    isDeleting: boolean;
    isToggling: boolean;
}> = ({ promotion, onDelete, isDeleting }) => (
    <StandardMobileCard
        title={promotion.name}
        badge={{
            children: (
                <StatusBadge
                    status={promotion.is_active ? 'active' : 'inactive'}
                    configs={PROMOTION_STATUS_CONFIGS}
                    showIcon={false}
                />
            ),
        }}
        dataFields={[]}
        actions={{
            editHref: `/menu/promotions/${promotion.id}/edit`,
            onDelete: () => onDelete(promotion),
            isDeleting,
            editTooltip: 'Editar promoción',
            deleteTooltip: 'Eliminar promoción',
        }}
    />
);

export default function PercentageIndex({ promotions, stats, filters }: PromotionsPageProps) {
    const [deletingPromotion, setDeletingPromotion] = useState<number | null>(null);
    const [selectedPromotion, setSelectedPromotion] = useState<Promotion | null>(null);
    const [showDeleteDialog, setShowDeleteDialog] = useState(false);

    const openDeleteDialog = useCallback((promotion: Promotion) => {
        setSelectedPromotion(promotion);
        setShowDeleteDialog(true);
    }, []);

    const closeDeleteDialog = useCallback(() => {
        setSelectedPromotion(null);
        setShowDeleteDialog(false);
        setDeletingPromotion(null);
    }, []);

    const handleDeletePromotion = async () => {
        if (!selectedPromotion) return;

        setDeletingPromotion(selectedPromotion.id);
        router.delete(`/menu/promotions/${selectedPromotion.id}`, {
            onSuccess: () => {
                closeDeleteDialog();
            },
            onError: (error) => {
                setDeletingPromotion(null);
                if (error.message) {
                    showNotification.error(error.message);
                }
            },
        });
    };

    const columns = [
        {
            key: 'name',
            title: 'Promoción',
            width: 'flex-1',
            sortable: true,
            render: (promotion: Promotion) => (
                <div className="text-sm font-medium text-foreground">{promotion.name}</div>
            ),
        },
        {
            key: 'created_at',
            title: 'Creado',
            width: 'w-40',
            sortable: true,
            render: (promotion: Promotion) => (
                <div className="text-sm text-muted-foreground">{formatDate(promotion.created_at)}</div>
            ),
        },
        {
            key: 'status',
            title: 'Estado',
            width: 'w-32',
            textAlign: 'center' as const,
            render: (promotion: Promotion) => (
                <div className="flex justify-center">
                    <StatusBadge
                        status={promotion.is_active ? 'active' : 'inactive'}
                        configs={PROMOTION_STATUS_CONFIGS}
                        showIcon={false}
                    />
                </div>
            ),
        },
        {
            key: 'actions',
            title: 'Acciones',
            width: 'w-24',
            textAlign: 'right' as const,
            render: (promotion: Promotion) => (
                <TableActions
                    editHref={`/menu/promotions/${promotion.id}/edit`}
                    onDelete={() => openDeleteDialog(promotion)}
                    isDeleting={deletingPromotion === promotion.id}
                    editTooltip="Editar promoción"
                    deleteTooltip="Eliminar promoción"
                />
            ),
        },
    ];

    const promotionStats = [
        {
            title: 'promociones porcentaje',
            value: stats.total_promotions,
            icon: <Percent className="h-3 w-3 text-green-600" />,
        },
        {
            title: 'activas',
            value: stats.active_promotions,
            icon: <CheckCircle className="h-3 w-3 text-green-600" />,
        },
        {
            title: 'vigentes ahora',
            value: stats.valid_now_promotions,
            icon: <Clock className="h-3 w-3 text-blue-600" />,
        },
    ];

    return (
        <AppLayout>
            <Head title="Promociones de Porcentaje" />

            <DataTable
                title="Promociones de Porcentaje"
                data={promotions}
                columns={columns}
                stats={promotionStats}
                filters={filters}
                createUrl="/menu/promotions/percentage/create"
                createLabel="Crear"
                searchPlaceholder={PLACEHOLDERS.promotionSearchPercentage}
                loadingSkeleton={DataTableSkeleton}
                renderMobileCard={(promotion) => (
                    <PromotionMobileCard
                        promotion={promotion}
                        onDelete={openDeleteDialog}
                        onToggle={() => {}}
                        isDeleting={deletingPromotion === promotion.id}
                        isToggling={false}
                    />
                )}
                routeName="/menu/promotions/percentage"
                breakpoint="lg"
            />

            <DeleteConfirmationDialog
                isOpen={showDeleteDialog}
                onClose={closeDeleteDialog}
                onConfirm={handleDeletePromotion}
                isDeleting={deletingPromotion !== null}
                entityName={selectedPromotion?.name || ''}
                entityType="Promoción de Porcentaje"
            />
        </AppLayout>
    );
}
