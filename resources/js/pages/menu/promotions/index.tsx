import { showNotification } from '@/hooks/useNotifications';
import { Head, router } from '@inertiajs/react';
import { useCallback, useState } from 'react';

import { DataTable } from '@/components/DataTable';
import { DeleteConfirmationDialog } from '@/components/DeleteConfirmationDialog';
import { EntityInfoCell } from '@/components/EntityInfoCell';
import { StandardMobileCard } from '@/components/StandardMobileCard';
import { TableActions } from '@/components/TableActions';
import { ProductsSkeleton } from '@/components/skeletons';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import AppLayout from '@/layouts/app-layout';
import { Calendar, CheckCircle, Clock, Percent, Power, Star, Tag, XCircle } from 'lucide-react';

interface Promotion {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    type: 'two_for_one' | 'percentage_discount';
    discount_value: number | null;
    applies_to: 'product' | 'category';
    is_permanent: boolean;
    valid_from: string | null;
    valid_until: string | null;
    has_time_restriction: boolean;
    time_from: string | null;
    time_until: string | null;
    active_days: number[] | null;
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
        type: string | null;
        is_active: boolean | null;
        applies_to: string | null;
        only_valid: boolean | null;
        per_page: number;
        sort_field?: string;
        sort_direction?: 'asc' | 'desc';
    };
}

// Mapeo de tipos de promoción
const PROMOTION_TYPES = {
    percentage_discount: { label: 'Descuento %', icon: Percent, color: 'bg-green-500' },
    two_for_one: { label: '2x1', icon: Star, color: 'bg-purple-500' },
};

// Mapeo de aplicabilidad
const APPLIES_TO = {
    product: 'Producto',
    category: 'Categoría',
};

const PromotionInfoCell: React.FC<{ promotion: Promotion }> = ({ promotion }) => {
    const typeInfo = PROMOTION_TYPES[promotion.type];
    const Icon = typeInfo.icon;

    return (
        <div className="flex items-start gap-3">
            <div className={`${typeInfo.color} text-white p-2 rounded-lg shrink-0`}>
                <Icon className="h-4 w-4" />
            </div>
            <div className="min-w-0">
                <div className="font-medium text-sm">{promotion.name}</div>
                {promotion.description && (
                    <div className="text-xs text-muted-foreground truncate">{promotion.description}</div>
                )}
                <div className="flex items-center gap-2 mt-1">
                    <Badge variant="outline" className="text-xs">
                        {typeInfo.label}
                    </Badge>
                    <Badge variant="secondary" className="text-xs">
                        {APPLIES_TO[promotion.applies_to]}
                    </Badge>
                </div>
            </div>
        </div>
    );
};

const PromotionMobileCard: React.FC<{
    promotion: Promotion;
    onDelete: (promotion: Promotion) => void;
    onToggle: (promotion: Promotion) => void;
    isDeleting: boolean;
    isToggling: boolean;
}> = ({ promotion, onDelete, onToggle, isDeleting, isToggling }) => {
    const typeInfo = PROMOTION_TYPES[promotion.type];
    const Icon = typeInfo.icon;

    return (
        <StandardMobileCard
            icon={Icon}
            title={promotion.name}
            subtitle={promotion.description}
            badge={{
                children: (
                    <Badge variant={promotion.is_active ? 'default' : 'secondary'}>
                        {promotion.is_active ? 'Activa' : 'Inactiva'}
                    </Badge>
                ),
            }}
            dataFields={[
                {
                    label: 'Tipo',
                    value: typeInfo.label,
                },
                {
                    label: 'Aplica a',
                    value: APPLIES_TO[promotion.applies_to],
                },
                {
                    label: 'Items',
                    value: `${promotion.items_count} items`,
                },
            ]}
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

export default function PromotionsIndex({ promotions, stats, filters }: PromotionsPageProps) {
    const [deletingPromotion, setDeletingPromotion] = useState<number | null>(null);
    const [togglingPromotion, setTogglingPromotion] = useState<number | null>(null);
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

    const handleToggleStatus = (promotion: Promotion) => {
        setTogglingPromotion(promotion.id);
        router.post(`/menu/promotions/${promotion.id}/toggle`, {}, {
            onSuccess: () => {
                setTogglingPromotion(null);
            },
            onError: (error) => {
                setTogglingPromotion(null);
                if (error.message) {
                    showNotification.error(error.message);
                }
            },
        });
    };

    const columns = [
        {
            key: 'promotion',
            title: 'Promoción',
            width: 'lg' as const,
            sortable: true,
            render: (promotion: Promotion) => <PromotionInfoCell promotion={promotion} />,
        },
        {
            key: 'discount_value',
            title: 'Descuento',
            width: 'sm' as const,
            textAlign: 'center' as const,
            render: (promotion: Promotion) => {
                if (promotion.type === 'percentage_discount' && promotion.discount_value) {
                    return (
                        <div className="flex items-center justify-center gap-1 text-sm font-medium text-green-600">
                            <Percent className="h-3 w-3" />
                            <span>{promotion.discount_value}%</span>
                        </div>
                    );
                }
                if (promotion.type === 'two_for_one') {
                    return <Badge variant="secondary">2x1</Badge>;
                }
                return <span className="text-xs text-muted-foreground">-</span>;
            },
        },
        {
            key: 'validity',
            title: 'Vigencia',
            width: 'md' as const,
            render: (promotion: Promotion) => {
                const now = new Date();
                const validFrom = promotion.valid_from ? new Date(promotion.valid_from) : null;
                const validUntil = promotion.valid_until ? new Date(promotion.valid_until) : null;

                let status: 'active' | 'future' | 'expired' | 'always' = 'always';
                if (promotion.is_permanent) {
                    status = 'always';
                } else if (validFrom && validFrom > now) {
                    status = 'future';
                } else if (validUntil && validUntil < now) {
                    status = 'expired';
                } else if (validFrom || validUntil) {
                    status = 'active';
                }

                const badges = {
                    active: <Badge variant="default" className="text-xs"><CheckCircle className="h-3 w-3 mr-1" />Vigente</Badge>,
                    future: <Badge variant="secondary" className="text-xs"><Clock className="h-3 w-3 mr-1" />Próxima</Badge>,
                    expired: <Badge variant="destructive" className="text-xs"><XCircle className="h-3 w-3 mr-1" />Expirada</Badge>,
                    always: <Badge variant="outline" className="text-xs"><Calendar className="h-3 w-3 mr-1" />Permanente</Badge>,
                };

                return (
                    <div className="flex items-center gap-2">
                        {badges[status]}
                        {promotion.active_days && promotion.active_days.length > 0 && (
                            <TooltipProvider>
                                <Tooltip>
                                    <TooltipTrigger>
                                        <Badge variant="outline" className="text-xs">
                                            {promotion.active_days.length} días
                                        </Badge>
                                    </TooltipTrigger>
                                    <TooltipContent>
                                        <p>Días específicos de la semana</p>
                                    </TooltipContent>
                                </Tooltip>
                            </TooltipProvider>
                        )}
                        {promotion.has_time_restriction && (
                            <TooltipProvider>
                                <Tooltip>
                                    <TooltipTrigger>
                                        <Badge variant="outline" className="text-xs">
                                            <Clock className="h-3 w-3" />
                                        </Badge>
                                    </TooltipTrigger>
                                    <TooltipContent>
                                        <p>Restricción horaria</p>
                                    </TooltipContent>
                                </Tooltip>
                            </TooltipProvider>
                        )}
                    </div>
                );
            },
        },
        {
            key: 'items_count',
            title: 'Items',
            width: 'sm' as const,
            textAlign: 'center' as const,
            render: (promotion: Promotion) => (
                <span className="text-sm text-muted-foreground">{promotion.items_count}</span>
            ),
        },
        {
            key: 'is_active',
            title: 'Estado',
            width: 'sm' as const,
            textAlign: 'center' as const,
            sortable: true,
            render: (promotion: Promotion) => (
                <Badge variant={promotion.is_active ? 'default' : 'secondary'}>
                    {promotion.is_active ? 'Activa' : 'Inactiva'}
                </Badge>
            ),
        },
        {
            key: 'actions',
            title: 'Acciones',
            width: 'sm' as const,
            textAlign: 'right' as const,
            render: (promotion: Promotion) => (
                <TooltipProvider>
                    <div className="flex items-center justify-end gap-1">
                        <Tooltip>
                            <TooltipTrigger asChild>
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    className="h-11 w-11 md:h-8 md:w-8 p-0 text-muted-foreground hover:text-foreground"
                                    onClick={() => handleToggleStatus(promotion)}
                                    disabled={togglingPromotion === promotion.id}
                                >
                                    {togglingPromotion === promotion.id ? (
                                        <div className="h-4 w-4 animate-spin rounded-full border-2 border-muted-foreground border-t-transparent" />
                                    ) : (
                                        <Power className="h-4 w-4" />
                                    )}
                                </Button>
                            </TooltipTrigger>
                            <TooltipContent>
                                <p>{promotion.is_active ? 'Desactivar' : 'Activar'}</p>
                            </TooltipContent>
                        </Tooltip>
                        <TableActions
                            editHref={`/menu/promotions/${promotion.id}/edit`}
                            onDelete={() => openDeleteDialog(promotion)}
                            isDeleting={deletingPromotion === promotion.id}
                            editTooltip="Editar promoción"
                            deleteTooltip="Eliminar promoción"
                        />
                    </div>
                </TooltipProvider>
            ),
        },
    ];

    const promotionStats = [
        {
            title: 'promociones',
            value: stats.total_promotions,
            icon: <Percent className="h-3 w-3 text-primary" />,
        },
        {
            title: 'activas',
            value: stats.active_promotions,
            icon: <Star className="h-3 w-3 text-green-600" />,
        },
        {
            title: 'vigentes ahora',
            value: stats.valid_now_promotions,
            icon: <CheckCircle className="h-3 w-3 text-blue-600" />,
        },
    ];

    return (
        <AppLayout>
            <Head title="Promociones" />

            <DataTable
                title="Promociones de Menú"
                description="Gestiona las promociones del menú: descuentos porcentuales y 2x1."
                data={promotions}
                columns={columns}
                stats={promotionStats}
                filters={filters}
                createUrl="/menu/promotions/create"
                createLabel="Crear Promoción"
                searchPlaceholder="Buscar promociones..."
                loadingSkeleton={ProductsSkeleton}
                renderMobileCard={(promotion) => (
                    <PromotionMobileCard
                        promotion={promotion}
                        onDelete={openDeleteDialog}
                        onToggle={handleToggleStatus}
                        isDeleting={deletingPromotion === promotion.id}
                        isToggling={togglingPromotion === promotion.id}
                    />
                )}
                routeName="/menu/promotions"
                breakpoint="lg"
            />

            <DeleteConfirmationDialog
                isOpen={showDeleteDialog}
                onClose={closeDeleteDialog}
                onConfirm={handleDeletePromotion}
                isDeleting={deletingPromotion !== null}
                entityName={selectedPromotion?.name || ''}
                entityType="promoción"
            />
        </AppLayout>
    );
}
