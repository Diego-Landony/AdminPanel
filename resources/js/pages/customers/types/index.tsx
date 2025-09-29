import { showNotification } from '@/hooks/useNotifications';
import { Head, router } from '@inertiajs/react';
import { useCallback, useState } from 'react';

import { DataTable } from '@/components/DataTable';
import { DeleteConfirmationDialog } from '@/components/DeleteConfirmationDialog';
import { EntityInfoCell } from '@/components/EntityInfoCell';
import { StandardMobileCard } from '@/components/StandardMobileCard';
import { TableActions } from '@/components/TableActions';
import { CustomerTypesSkeleton } from '@/components/skeletons';
import { ACTIVE_STATUS_CONFIGS, ColorBadge, StatusBadge } from '@/components/status-badge';
import AppLayout from '@/layouts/app-layout';
import { Shield, Star, Users } from 'lucide-react';

interface CustomerType {
    id: number;
    name: string;
    points_required: number;
    multiplier: number;
    color: string | null;
    is_active: boolean;
    customers_count: number;
    created_at: string;
    updated_at: string;
}

interface CustomerTypesPageProps {
    customer_types: {
        data: CustomerType[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        from: number;
        to: number;
    };
    stats: {
        total_types: number;
        active_types: number;
    };
    filters: {
        search: string | null;
        per_page: number;
        sort_field?: string;
        sort_direction?: 'asc' | 'desc';
    };
}

const CustomerTypeInfoCell: React.FC<{ type: CustomerType }> = ({ type }) => (
    <EntityInfoCell icon={Shield} primaryText={type.name} />
);

const CustomerTypeMobileCard: React.FC<{ type: CustomerType; onDelete: (type: CustomerType) => void; isDeleting: boolean }> = ({
    type,
    onDelete,
    isDeleting,
}) => (
    <StandardMobileCard
        icon={Shield}
        title={type.name}
        badge={{
            children: <StatusBadge status={type.is_active ? 'active' : 'inactive'} configs={ACTIVE_STATUS_CONFIGS} showIcon={false} />,
        }}
        dataFields={[
            {
                label: 'Puntos Requeridos',
                value: `${type.points_required.toLocaleString()} pts`,
            },
            {
                label: 'Multiplicador',
                value: <ColorBadge color={type.color}>{type.multiplier}x</ColorBadge>,
            },
            {
                label: 'Clientes',
                value: (
                    <div className="flex items-center gap-2">
                        <Users className="h-4 w-4 text-muted-foreground" />
                        <span>{type.customers_count}</span>
                    </div>
                ),
            },
        ]}
        actions={{
            editHref: `/customer-types/${type.id}/edit`,
            onDelete: () => onDelete(type),
            isDeleting,
            editTooltip: 'Editar tipo',
            deleteTooltip: 'Eliminar tipo',
            canDelete: type.customers_count === 0,
        }}
    />
);

export default function CustomerTypesIndex({ customer_types, stats, filters }: CustomerTypesPageProps) {
    const [deletingType, setDeletingType] = useState<number | null>(null);
    const [selectedType, setSelectedType] = useState<CustomerType | null>(null);
    const [showDeleteDialog, setShowDeleteDialog] = useState(false);

    const openDeleteDialog = useCallback((type: CustomerType) => {
        setSelectedType(type);
        setShowDeleteDialog(true);
    }, []);

    const closeDeleteDialog = useCallback(() => {
        setSelectedType(null);
        setShowDeleteDialog(false);
        setDeletingType(null);
    }, []);

    const handleDeleteType = async () => {
        if (!selectedType) return;

        setDeletingType(selectedType.id);
        router.delete(`/customer-types/${selectedType.id}`, {
            onSuccess: () => {
                closeDeleteDialog();
            },
            onError: (error) => {
                setDeletingType(null);
                if (error.message) {
                    showNotification.error(error.message);
                }
            },
        });
    };

    const columns = [
        {
            key: 'type',
            title: 'Tipo',
            width: 'lg' as const,
            sortable: true,
            render: (type: CustomerType) => <CustomerTypeInfoCell type={type} />,
        },
        {
            key: 'points_required',
            title: 'Puntos Requeridos',
            width: 'md' as const,
            sortable: true,
            render: (type: CustomerType) => <span className="text-sm font-medium text-foreground">{type.points_required.toLocaleString()} pts</span>,
        },
        {
            key: 'multiplier',
            title: 'Multiplicador',
            width: 'sm' as const,
            render: (type: CustomerType) => <ColorBadge color={type.color}>{type.multiplier}x</ColorBadge>,
        },
        {
            key: 'customers_count',
            title: 'Clientes',
            width: 'sm' as const,
            textAlign: 'center' as const,
            render: (type: CustomerType) => (
                <div className="flex items-center justify-center gap-2 text-sm text-muted-foreground">
                    <Users className="h-4 w-4" />
                    <span>{type.customers_count}</span>
                </div>
            ),
        },
        {
            key: 'is_active',
            title: 'Estado',
            width: 'sm' as const,
            textAlign: 'center' as const,
            sortable: true,
            render: (type: CustomerType) => (
                <StatusBadge status={type.is_active ? 'active' : 'inactive'} configs={ACTIVE_STATUS_CONFIGS} showIcon={false} />
            ),
        },
        {
            key: 'created_at',
            title: 'Creado',
            width: 'sm' as const,
            sortable: true,
            render: (type: CustomerType) => {
                const formatDate = (dateString: string): string => {
                    return new Date(dateString).toLocaleDateString('es-ES', {
                        year: 'numeric',
                        month: 'short',
                        day: 'numeric',
                    });
                };
                return <div className="text-sm text-muted-foreground">{formatDate(type.created_at)}</div>;
            },
        },
        {
            key: 'actions',
            title: 'Acciones',
            width: 'xs' as const,
            textAlign: 'right' as const,
            render: (type: CustomerType) => (
                <TableActions
                    editHref={`/customer-types/${type.id}/edit`}
                    onDelete={() => openDeleteDialog(type)}
                    isDeleting={deletingType === type.id}
                    editTooltip="Editar tipo"
                    deleteTooltip="Eliminar tipo"
                    canDelete={type.customers_count === 0}
                />
            ),
        },
    ];

    const customerTypeStats = [
        {
            title: 'tipos',
            value: stats.total_types,
            icon: <Shield className="h-3 w-3 text-primary" />,
        },
        {
            title: 'activos',
            value: stats.active_types,
            icon: <Star className="h-3 w-3 text-green-600" />,
        },
        {
            title: 'inactivos',
            value: stats.total_types - stats.active_types,
            icon: <Users className="h-3 w-3 text-red-600" />,
        },
    ];

    return (
        <AppLayout>
            <Head title="Tipos de Cliente" />

            <DataTable
                title="Tipos de Cliente"
                description="Gestiona los diferentes tipos de clientes y sus multiplicadores."
                data={customer_types}
                columns={columns}
                stats={customerTypeStats}
                filters={filters}
                createUrl="/customer-types/create"
                createLabel="Crear Tipo"
                searchPlaceholder="Buscar tipos de cliente..."
                loadingSkeleton={CustomerTypesSkeleton}
                renderMobileCard={(type) => <CustomerTypeMobileCard type={type} onDelete={openDeleteDialog} isDeleting={deletingType === type.id} />}
                routeName="/customer-types"
                breakpoint="lg"
            />

            <DeleteConfirmationDialog
                isOpen={showDeleteDialog}
                onClose={closeDeleteDialog}
                onConfirm={handleDeleteType}
                isDeleting={deletingType !== null}
                entityName={selectedType?.display_name || ''}
                entityType="tipo de cliente"
                canDelete={(selectedType?.customers_count ?? 0) === 0}
                deleteBlockedReason={
                    selectedType?.customers_count && selectedType.customers_count > 0
                        ? `Este tipo tiene ${selectedType.customers_count} clientes asignados y no se puede eliminar.`
                        : undefined
                }
            />
        </AppLayout>
    );
}
