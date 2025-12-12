import { showNotification } from '@/hooks/useNotifications';
import { Head, router } from '@inertiajs/react';
import { useState } from 'react';

import { DeleteConfirmationDialog } from '@/components/DeleteConfirmationDialog';
import { SortableTable } from '@/components/SortableTable';
import { StandardMobileCard } from '@/components/StandardMobileCard';
import { TableActions } from '@/components/TableActions';
import { ACTIVE_STATUS_CONFIGS, StatusBadge } from '@/components/status-badge';
import { PLACEHOLDERS } from '@/constants/ui-constants';
import AppLayout from '@/layouts/app-layout';
import { Award, Check, X } from 'lucide-react';

interface BadgeType {
    id: number;
    name: string;
    color: string;
    is_active: boolean;
    sort_order: number;
    product_badges_count: number;
    created_at: string;
}

interface BadgeTypesPageProps {
    badgeTypes: BadgeType[];
    stats: {
        total: number;
        active: number;
    };
}

export default function BadgeTypesIndex({ badgeTypes, stats }: BadgeTypesPageProps) {
    const [deletingId, setDeletingId] = useState<number | null>(null);
    const [selectedBadge, setSelectedBadge] = useState<BadgeType | null>(null);
    const [showDeleteDialog, setShowDeleteDialog] = useState(false);
    const [isSaving, setIsSaving] = useState(false);

    const handleReorder = (reordered: BadgeType[]) => {
        setIsSaving(true);

        const orderData = reordered.map((item, index) => ({
            id: item.id,
            sort_order: index + 1,
        }));

        router.post(
            route('menu.badge-types.reorder'),
            { badge_types: orderData },
            {
                preserveState: true,
                onError: (error) => {
                    if (error.message) {
                        showNotification.error(error.message);
                    }
                },
                onFinish: () => setIsSaving(false),
            },
        );
    };

    const handleRefresh = () => router.reload();

    const openDeleteDialog = (badge: BadgeType) => {
        setSelectedBadge(badge);
        setShowDeleteDialog(true);
    };

    const closeDeleteDialog = () => {
        setSelectedBadge(null);
        setShowDeleteDialog(false);
        setDeletingId(null);
    };

    const handleDelete = () => {
        if (!selectedBadge) return;

        setDeletingId(selectedBadge.id);
        router.delete(`/menu/badge-types/${selectedBadge.id}`, {
            preserveState: false,
            onBefore: () => closeDeleteDialog(),
            onError: (error) => {
                setDeletingId(null);
                if (error.message) {
                    showNotification.error(error.message);
                }
            },
        });
    };

    const badgeStats = [
        {
            title: 'badges',
            value: stats.total,
            icon: <Award className="h-4 w-4 text-primary" />,
        },
        {
            title: 'activos',
            value: stats.active,
            icon: <Check className="h-4 w-4 text-green-600" />,
        },
        {
            title: 'inactivos',
            value: stats.total - stats.active,
            icon: <X className="h-4 w-4 text-destructive" />,
        },
    ];

    const columns = [
        {
            key: 'preview',
            title: 'Vista Previa',
            width: 'w-32',
            render: (badge: BadgeType) => (
                <span
                    className="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium text-white"
                    style={{ backgroundColor: badge.color }}
                >
                    {badge.name}
                </span>
            ),
        },
        {
            key: 'name',
            title: 'Nombre',
            width: 'w-48',
            render: (badge: BadgeType) => <div className="text-sm font-medium text-foreground">{badge.name}</div>,
        },
        {
            key: 'color',
            title: 'Color',
            width: 'w-32',
            render: (badge: BadgeType) => (
                <div className="flex items-center gap-2">
                    <div className="h-5 w-5 rounded border" style={{ backgroundColor: badge.color }} />
                    <span className="text-xs text-muted-foreground">{badge.color}</span>
                </div>
            ),
        },
        {
            key: 'usage',
            title: 'Usos',
            width: 'w-24',
            align: 'center' as const,
            render: (badge: BadgeType) => <span className="text-sm text-muted-foreground">{badge.product_badges_count}</span>,
        },
        {
            key: 'status',
            title: 'Estado',
            width: 'w-32',
            align: 'center' as const,
            render: (badge: BadgeType) => (
                <div className="flex justify-center">
                    <StatusBadge status={badge.is_active ? 'active' : 'inactive'} configs={ACTIVE_STATUS_CONFIGS} showIcon={false} />
                </div>
            ),
        },
        {
            key: 'actions',
            title: 'Acciones',
            width: 'w-24',
            align: 'right' as const,
            render: (badge: BadgeType) => (
                <TableActions
                    editHref={`/menu/badge-types/${badge.id}/edit`}
                    onDelete={() => openDeleteDialog(badge)}
                    isDeleting={deletingId === badge.id}
                    editTooltip="Editar badge"
                    deleteTooltip="Eliminar badge"
                />
            ),
        },
    ];

    const renderMobileCard = (badge: BadgeType) => (
        <StandardMobileCard
            title={badge.name}
            subtitle={`${badge.product_badges_count} productos asignados`}
            icon={Award}
            badge={{
                children: <StatusBadge status={badge.is_active ? 'active' : 'inactive'} configs={ACTIVE_STATUS_CONFIGS} showIcon={false} />,
            }}
            actions={{
                editHref: `/menu/badge-types/${badge.id}/edit`,
                onDelete: () => openDeleteDialog(badge),
                isDeleting: deletingId === badge.id,
                editTooltip: 'Editar badge',
                deleteTooltip: 'Eliminar badge',
            }}
            dataFields={[
                {
                    label: 'Vista previa',
                    value: (
                        <span
                            className="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium text-white"
                            style={{ backgroundColor: badge.color }}
                        >
                            {badge.name}
                        </span>
                    ),
                },
                { label: 'Color', value: badge.color },
            ]}
        />
    );

    return (
        <AppLayout>
            <Head title="Badges" />

            <SortableTable
                title="Badges de Menú"
                data={badgeTypes}
                columns={columns}
                stats={badgeStats}
                createUrl="/menu/badge-types/create"
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
                onConfirm={handleDelete}
                isDeleting={deletingId !== null}
                entityName={selectedBadge?.name || ''}
                entityType="badge"
            />
        </AppLayout>
    );
}
