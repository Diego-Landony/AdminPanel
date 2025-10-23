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
import { CheckCircle, Clock, Tag } from 'lucide-react';

interface Promotion {
    id: number;
    name: string;
    description: string | null;
    type: 'daily_special';
    scope_type: 'product';
    special_price_capital: number | null;
    special_price_interior: number | null;
    applies_to: 'product';
    service_type: 'both' | 'delivery_only' | 'pickup_only';
    validity_type: 'permanent' | 'date_range' | 'time_range' | 'date_time_range' | 'weekdays';
    is_permanent: boolean;
    valid_from: string | null;
    valid_until: string | null;
    has_time_restriction: boolean;
    time_from: string | null;
    time_until: string | null;
    active_days: number[] | null;
    weekdays: number[] | null;
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

export default function DailySpecialIndex({ promotions, stats, filters }: PromotionsPageProps) {
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
            preserveState: false,
            onBefore: () => {
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
            width: 'full' as const,
            sortable: true,
            render: (promotion: Promotion) => (
                <div className="text-sm font-medium text-foreground">{promotion.name}</div>
            ),
        },
        {
            key: 'created_at',
            title: 'Creado',
            width: 'lg' as const,
            sortable: true,
            render: (promotion: Promotion) => (
                <div className="text-sm text-muted-foreground">{formatDate(promotion.created_at)}</div>
            ),
        },
        {
            key: 'status',
            title: 'Estado',
            width: 'md' as const,
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
            width: 'sm' as const,
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
            title: 'subs del día',
            value: stats.total_promotions,
            icon: <Tag className="h-3 w-3 text-orange-600" />,
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
            <Head title="Sub del Día" />

            <DataTable
                title="Sub del Día"
                data={promotions}
                columns={columns}
                stats={promotionStats}
                filters={filters}
                createUrl="/menu/promotions/daily-special/create"
                createLabel="Crear"
                searchPlaceholder={PLACEHOLDERS.search}
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
                routeName="/menu/promotions/daily-special"
                breakpoint="lg"
            />

            <DeleteConfirmationDialog
                isOpen={showDeleteDialog}
                onClose={closeDeleteDialog}
                onConfirm={handleDeletePromotion}
                isDeleting={deletingPromotion !== null}
                entityName={selectedPromotion?.name || ''}
                entityType="Sub del Día"
            />
        </AppLayout>
    );
}
