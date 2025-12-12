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
import { formatWeekdaysShort } from '@/constants/weekdays';
import AppLayout from '@/layouts/app-layout';
import { formatCurrency, formatDate } from '@/utils/format';
import { Calendar, CheckCircle, Clock, DollarSign, Store, Tag, Truck } from 'lucide-react';

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
}> = ({ promotion, onDelete, isDeleting }) => {
    const getServiceTypeLabel = (serviceType: 'both' | 'delivery_only' | 'pickup_only') => {
        switch (serviceType) {
            case 'both':
                return (
                    <div className="flex items-center gap-1">
                        <Store className="h-3 w-3 text-muted-foreground" />
                        <Truck className="h-3 w-3 text-muted-foreground" />
                        <span>Delivery y Pickup</span>
                    </div>
                );
            case 'delivery_only':
                return (
                    <div className="flex items-center gap-1">
                        <Truck className="h-3 w-3 text-muted-foreground" />
                        <span>Solo Delivery</span>
                    </div>
                );
            case 'pickup_only':
                return (
                    <div className="flex items-center gap-1">
                        <Store className="h-3 w-3 text-muted-foreground" />
                        <span>Solo Pickup</span>
                    </div>
                );
        }
    };

    const dataFields = [];

    if (promotion.special_price_capital !== null || promotion.special_price_interior !== null) {
        dataFields.push({
            label: 'Precios Especiales',
            value: (
                <div className="space-y-1 text-xs">
                    {promotion.special_price_capital !== null && (
                        <div className="flex items-center gap-1">
                            <DollarSign className="h-3 w-3 text-muted-foreground" />
                            <span>Capital: {formatCurrency(promotion.special_price_capital)}</span>
                        </div>
                    )}
                    {promotion.special_price_interior !== null && (
                        <div className="flex items-center gap-1">
                            <DollarSign className="h-3 w-3 text-muted-foreground" />
                            <span>Interior: {formatCurrency(promotion.special_price_interior)}</span>
                        </div>
                    )}
                </div>
            ),
        });
    }

    dataFields.push({
        label: 'Tipo de Servicio',
        value: getServiceTypeLabel(promotion.service_type),
    });

    if (promotion.weekdays && promotion.weekdays.length > 0) {
        dataFields.push({
            label: 'Días Activos',
            value: (
                <div className="flex items-center gap-1">
                    <Calendar className="h-3 w-3 text-muted-foreground" />
                    <span>{formatWeekdaysShort(promotion.weekdays)}</span>
                </div>
            ),
        });
    }

    if (promotion.has_time_restriction && promotion.time_from && promotion.time_until) {
        dataFields.push({
            label: 'Horario',
            value: (
                <div className="flex items-center gap-1">
                    <Clock className="h-3 w-3 text-muted-foreground" />
                    <span>
                        {promotion.time_from} - {promotion.time_until}
                    </span>
                </div>
            ),
        });
    }

    if (!promotion.is_permanent && promotion.valid_from && promotion.valid_until) {
        dataFields.push({
            label: 'Vigencia',
            value: (
                <div className="text-xs">
                    <div>{formatDate(promotion.valid_from)}</div>
                    <div className="text-muted-foreground">hasta {formatDate(promotion.valid_until)}</div>
                </div>
            ),
        });
    }

    dataFields.push({
        label: 'Productos',
        value: (
            <div className="flex items-center gap-1">
                <Tag className="h-3 w-3 text-muted-foreground" />
                <span>
                    {promotion.items_count} producto{promotion.items_count !== 1 ? 's' : ''}
                </span>
            </div>
        ),
    });

    return (
        <StandardMobileCard
            title={promotion.name}
            badge={{
                children: <StatusBadge status={promotion.is_active ? 'active' : 'inactive'} configs={PROMOTION_STATUS_CONFIGS} showIcon={false} />,
            }}
            dataFields={dataFields}
            actions={{
                editHref: `/menu/promotions/${promotion.id}/edit`,
                onDelete: () => onDelete(promotion),
                isDeleting,
                editTooltip: 'Editar promoción',
                deleteTooltip: 'Eliminar promoción',
            }}
        />
    );
};

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
            render: (promotion: Promotion) => <div className="text-sm font-medium text-foreground">{promotion.name}</div>,
        },
        {
            key: 'created_at',
            title: 'Creado',
            width: 'lg' as const,
            sortable: true,
            render: (promotion: Promotion) => <div className="text-sm text-muted-foreground">{formatDate(promotion.created_at)}</div>,
        },
        {
            key: 'status',
            title: 'Estado',
            width: 'md' as const,
            align: 'center' as const,
            render: (promotion: Promotion) => (
                <div className="flex justify-center">
                    <StatusBadge status={promotion.is_active ? 'active' : 'inactive'} configs={PROMOTION_STATUS_CONFIGS} showIcon={false} />
                </div>
            ),
        },
        {
            key: 'actions',
            title: 'Acciones',
            width: 'sm' as const,
            align: 'right' as const,
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
