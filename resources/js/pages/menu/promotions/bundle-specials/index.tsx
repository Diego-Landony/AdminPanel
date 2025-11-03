import { showNotification } from '@/hooks/useNotifications';
import { Head, router } from '@inertiajs/react';
import { useState } from 'react';

import { DeleteConfirmationDialog } from '@/components/DeleteConfirmationDialog';
import { StandardMobileCard } from '@/components/StandardMobileCard';
import { SortableTable } from '@/components/SortableTable';
import { TableActions } from '@/components/TableActions';
import { COMBINADO_STATUS_CONFIGS, StatusBadge } from '@/components/status-badge';
import { PLACEHOLDERS } from '@/constants/ui-constants';
import AppLayout from '@/layouts/app-layout';
import { Clock, Gift, Star } from 'lucide-react';

interface BundlePromotionItem {
    id: number;
    product?: {
        id: number;
        name: string;
    } | null;
}

interface Combinado {
    id: number;
    name: string;
    description: string | null;
    type: 'bundle_special';
    is_active: boolean;
    special_bundle_price_capital: number | null;
    special_bundle_price_interior: number | null;
    valid_from: string | null;
    valid_until: string | null;
    time_from: string | null;
    time_until: string | null;
    weekdays: number[] | null;
    sort_order: number;
    bundle_items_count: number;
    bundle_items?: BundlePromotionItem[];
    created_at: string;
    updated_at: string;
}

interface CombinadosPageProps {
    combinados: Combinado[];
    stats: {
        total_combinados: number;
        active_combinados: number;
        valid_now_combinados: number;
    };
    filters: {
        search: string | null;
        per_page: number;
    };
}

/**
 * Helper: Determina el estado de un combinado basándose en las fechas de vigencia
 * Lógica sincronizada con backend Promotion::scopeValidNowCombinados()
 */
function getCombinadoStatus(combinado: Combinado): 'active' | 'inactive' | 'expired' | 'upcoming' {
    if (!combinado.is_active) {
        return 'inactive';
    }

    const now = new Date();
    const currentDate = now.toISOString().split('T')[0];
    const currentTime = now.toTimeString().split(' ')[0].substring(0, 5); // HH:MM
    const currentWeekday = now.getDay() === 0 ? 7 : now.getDay(); // Convert JS (0-6) to ISO (1-7)

    // Check if upcoming (before valid_from date)
    if (combinado.valid_from && currentDate < combinado.valid_from) {
        return 'upcoming';
    }

    // Check if expired (past valid_until date)
    if (combinado.valid_until && currentDate > combinado.valid_until) {
        return 'expired';
    }

    // Within date range (or no date restrictions), check time
    if (combinado.time_from && currentTime < combinado.time_from) {
        return 'upcoming';
    }

    if (combinado.time_until && currentTime > combinado.time_until) {
        return 'expired';
    }

    // Check weekday restrictions
    if (combinado.weekdays && combinado.weekdays.length > 0) {
        if (!combinado.weekdays.includes(currentWeekday)) {
            return 'inactive';
        }
    }

    return 'active';
}

/**
 * Helper: Formatea la vigencia temporal de un combinado en formato legible
 */
function formatVigencia(combinado: Combinado): string {
    const parts: string[] = [];

    // Fechas
    if (combinado.valid_from || combinado.valid_until) {
        const dateOptions: Intl.DateTimeFormatOptions = { day: 'numeric', month: 'numeric', year: 'numeric' };
        const fromStr = combinado.valid_from
            ? new Date(combinado.valid_from + 'T00:00:00').toLocaleDateString('es-GT', dateOptions)
            : '...';
        const untilStr = combinado.valid_until
            ? new Date(combinado.valid_until + 'T00:00:00').toLocaleDateString('es-GT', dateOptions)
            : '...';
        parts.push(`${fromStr} - ${untilStr}`);
    }

    // Horarios
    if (combinado.time_from || combinado.time_until) {
        const fromTime = combinado.time_from || '00:00';
        const untilTime = combinado.time_until || '23:59';
        parts.push(`${fromTime} - ${untilTime}`);
    }

    // Días de la semana
    if (combinado.weekdays && combinado.weekdays.length > 0) {
        const weekdayNames = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'];
        const dayNames = combinado.weekdays
            .sort((a, b) => a - b)
            .map((day) => {
                // Convert from ISO (1=Mon, 7=Sun) to our array index (0=Sun, 6=Sat)
                const index = day === 7 ? 0 : day;
                return weekdayNames[index];
            });
        parts.push(dayNames.join(', '));
    }

    return parts.length > 0 ? parts.join(' | ') : 'Siempre válido';
}

export default function BundleSpecialsIndex({ combinados, stats }: CombinadosPageProps) {
    const [deletingCombinado, setDeletingCombinado] = useState<number | null>(null);
    const [selectedCombinado, setSelectedCombinado] = useState<Combinado | null>(null);
    const [showDeleteDialog, setShowDeleteDialog] = useState(false);
    const [isSaving, setIsSaving] = useState(false);

    const handleReorder = (reorderedCombinados: Combinado[]) => {
        setIsSaving(true);

        const orderData = reorderedCombinados.map((combinado, index) => ({
            id: combinado.id,
            sort_order: index + 1,
        }));

        router.post(
            route('menu.promotions.bundle-specials.reorder'),
            { combinados: orderData },
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

    const openDeleteDialog = (combinado: Combinado) => {
        setSelectedCombinado(combinado);
        setShowDeleteDialog(true);
    };

    const closeDeleteDialog = () => {
        setSelectedCombinado(null);
        setShowDeleteDialog(false);
        setDeletingCombinado(null);
    };

    const handleDeleteCombinado = () => {
        if (!selectedCombinado) return;

        setDeletingCombinado(selectedCombinado.id);
        router.delete(route('menu.promotions.bundle-specials.destroy', selectedCombinado.id), {
            preserveState: false,
            onBefore: () => {
                closeDeleteDialog();
            },
            onError: (error) => {
                setDeletingCombinado(null);
                if (error.message) {
                    showNotification.error(error.message);
                }
            },
        });
    };

    const columns = [
        {
            key: 'name',
            title: 'Combinado',
            width: 'flex-1',
            sortable: true,
            render: (combinado: Combinado) => (
                <div className="space-y-0.5">
                    <div className="text-sm font-medium">{combinado.name}</div>
                    {combinado.description && <div className="text-xs text-muted-foreground line-clamp-1">{combinado.description}</div>}
                </div>
            ),
        },
        {
            key: 'prices',
            title: 'Precio',
            width: 'w-28',
            textAlign: 'center' as const,
            render: (combinado: Combinado) => (
                <div className="space-y-0.5 text-xs">
                    <div className="flex items-center justify-center gap-1">
                        <span className="text-muted-foreground">C:</span>
                        <span className="font-medium tabular-nums">
                            Q{combinado.special_bundle_price_capital ? Number(combinado.special_bundle_price_capital).toFixed(2) : '0.00'}
                        </span>
                    </div>
                    <div className="flex items-center justify-center gap-1">
                        <span className="text-muted-foreground">I:</span>
                        <span className="font-medium tabular-nums">
                            Q{combinado.special_bundle_price_interior ? Number(combinado.special_bundle_price_interior).toFixed(2) : '0.00'}
                        </span>
                    </div>
                </div>
            ),
        },
        {
            key: 'vigencia',
            title: 'Vigencia',
            width: 'w-64',
            render: (combinado: Combinado) => (
                <div className="text-xs text-muted-foreground whitespace-normal break-words">
                    {formatVigencia(combinado)}
                </div>
            ),
        },
        {
            key: 'status',
            title: 'Estado',
            width: 'w-32',
            textAlign: 'center' as const,
            render: (combinado: Combinado) => (
                <div className="flex justify-center">
                    <StatusBadge
                        status={getCombinadoStatus(combinado)}
                        configs={COMBINADO_STATUS_CONFIGS}
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
            render: (combinado: Combinado) => (
                <TableActions
                    editHref={route('menu.promotions.bundle-specials.edit', combinado.id)}
                    onDelete={() => openDeleteDialog(combinado)}
                    isDeleting={deletingCombinado === combinado.id}
                    editTooltip="Editar combinado"
                    deleteTooltip="Eliminar combinado"
                />
            ),
        },
    ];

    const renderMobileCard = (combinado: Combinado) => (
        <StandardMobileCard
            icon={Gift}
            title={combinado.name}
            subtitle={combinado.description || `${combinado.bundle_items_count} items`}
            badge={{
                children: (
                    <StatusBadge
                        status={getCombinadoStatus(combinado)}
                        configs={COMBINADO_STATUS_CONFIGS}
                        showIcon={false}
                    />
                ),
            }}
            dataFields={[
                {
                    label: 'Precio Capital',
                    value: `Q${combinado.special_bundle_price_capital ? Number(combinado.special_bundle_price_capital).toFixed(2) : '0.00'}`,
                },
                {
                    label: 'Precio Interior',
                    value: `Q${combinado.special_bundle_price_interior ? Number(combinado.special_bundle_price_interior).toFixed(2) : '0.00'}`,
                },
                {
                    label: 'Vigencia',
                    value: formatVigencia(combinado),
                },
                {
                    label: 'Items',
                    value: combinado.bundle_items_count.toString(),
                },
            ]}
            actions={{
                editHref: route('menu.promotions.bundle-specials.edit', combinado.id),
                onDelete: () => openDeleteDialog(combinado),
                isDeleting: deletingCombinado === combinado.id,
                editTooltip: 'Editar combinado',
                deleteTooltip: 'Eliminar combinado',
            }}
        />
    );

    const combinadoStats = [
        {
            title: 'combinados',
            value: stats.total_combinados,
            icon: <Gift className="h-3 w-3 text-primary" />,
        },
        {
            title: 'activos',
            value: stats.active_combinados,
            icon: <Star className="h-3 w-3 text-green-600" />,
        },
        {
            title: 'válidos ahora',
            value: stats.valid_now_combinados,
            icon: <Clock className="h-3 w-3 text-blue-600" />,
        },
    ];

    return (
        <AppLayout>
            <Head title="Combinados" />

            <SortableTable
                title="Combinados"
                description="Ofertas temporales con vigencia limitada"
                data={combinados}
                columns={columns}
                stats={combinadoStats}
                createUrl={route('menu.promotions.bundle-specials.create')}
                createLabel="Crear"
                searchable={true}
                searchPlaceholder={PLACEHOLDERS.search}
                onReorder={handleReorder}
                onRefresh={handleRefresh}
                isSaving={isSaving}
                renderMobileCard={renderMobileCard}
                breakpoint="lg"
            />

            <DeleteConfirmationDialog
                isOpen={showDeleteDialog}
                onClose={closeDeleteDialog}
                onConfirm={handleDeleteCombinado}
                isDeleting={deletingCombinado !== null}
                entityName={selectedCombinado?.name || ''}
                entityType="combinado"
            />
        </AppLayout>
    );
}
