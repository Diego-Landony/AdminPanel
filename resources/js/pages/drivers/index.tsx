import { showNotification } from '@/hooks/useNotifications';
import { Head, router } from '@inertiajs/react';
import { CheckCircle, Users } from 'lucide-react';
import { useCallback, useState } from 'react';

import { DataTable } from '@/components/DataTable';
import { DeleteConfirmationDialog } from '@/components/DeleteConfirmationDialog';
import { EntityInfoCell } from '@/components/EntityInfoCell';
import { StandardMobileCard } from '@/components/StandardMobileCard';
import { ACTIVE_STATUS_CONFIGS, StatusBadge } from '@/components/status-badge';
import { TableActions } from '@/components/TableActions';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { NOTIFICATIONS } from '@/constants/ui-constants';
import AppLayout from '@/layouts/app-layout';
import { Driver, Filters, PaginatedData, Restaurant } from '@/types';
import { formatDate } from '@/utils/format';

interface DriversPageProps {
    drivers: PaginatedData<Driver>;
    restaurants: Restaurant[];
    total_drivers: number;
    active_drivers: number;
    filters: Filters & {
        restaurant_id?: string | null;
        status?: string | null;
    };
}

/**
 * Pagina principal de gestion de motoristas
 */
export default function DriversIndex({
    drivers,
    restaurants,
    total_drivers,
    active_drivers,
    filters,
}: DriversPageProps) {
    const [deletingDriver, setDeletingDriver] = useState<number | null>(null);
    const [driverToDelete, setDriverToDelete] = useState<Driver | null>(null);
    const [showDeleteDialog, setShowDeleteDialog] = useState(false);

    const openDeleteDialog = useCallback((driver: Driver) => {
        setDriverToDelete(driver);
        setShowDeleteDialog(true);
    }, []);

    const closeDeleteDialog = useCallback(() => {
        setDriverToDelete(null);
        setShowDeleteDialog(false);
        setDeletingDriver(null);
    }, []);

    const handleDeleteDriver = async () => {
        if (!driverToDelete) return;

        setDeletingDriver(driverToDelete.id);
        router.delete(`/drivers/${driverToDelete.id}`, {
            preserveState: false,
            onBefore: () => {
                closeDeleteDialog();
            },
            onError: (error) => {
                setDeletingDriver(null);
                if (error.message) {
                    showNotification.error(error.message);
                } else {
                    showNotification.error(NOTIFICATIONS.error.delete);
                }
            },
        });
    };

    const handleFilterChange = (key: string, value: string | null) => {
        const params: Record<string, string | number | undefined> = {
            per_page: filters.per_page,
        };
        if (filters.search) params.search = filters.search;
        if (key === 'restaurant_id' && value) params.restaurant_id = value;
        else if (filters.restaurant_id) params.restaurant_id = filters.restaurant_id;
        if (key === 'status' && value) params.status = value;
        else if (filters.status) params.status = filters.status;

        router.get('/drivers', params, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    };

    const stats = [
        {
            title: 'total',
            value: total_drivers,
            icon: <Users className="h-4 w-4" />,
        },
        {
            title: 'activos',
            value: active_drivers,
            icon: <CheckCircle className="h-4 w-4 text-green-600" />,
        },
    ];

    const columns = [
        {
            key: 'driver',
            title: 'Motorista',
            width: 'lg' as const,
            sortable: true,
            render: (driver: Driver) => {
                return <EntityInfoCell icon={Users} primaryText={driver.name} secondaryText={driver.email} />;
            },
        },
        {
            key: 'phone',
            title: 'Telefono',
            width: 'md' as const,
            render: (driver: Driver) => <div className="text-sm">{driver.phone || 'N/A'}</div>,
        },
        {
            key: 'restaurant',
            title: 'Restaurante',
            width: 'md' as const,
            render: (driver: Driver) => (
                <div className="text-sm">{driver.restaurant?.name || 'Sin asignar'}</div>
            ),
        },
        {
            key: 'status',
            title: 'Estado',
            width: 'sm' as const,
            sortable: true,
            render: (driver: Driver) => (
                <StatusBadge status={driver.is_active ? 'active' : 'inactive'} configs={ACTIVE_STATUS_CONFIGS} className="text-xs" />
            ),
        },
        {
            key: 'created_at',
            title: 'Creado',
            width: 'sm' as const,
            sortable: true,
            render: (driver: Driver) => <div className="text-sm text-muted-foreground">{formatDate(driver.created_at)}</div>,
        },
        {
            key: 'actions',
            title: 'Acciones',
            width: 'sm' as const,
            align: 'right' as const,
            render: (driver: Driver) => (
                <TableActions
                    editHref={`/drivers/${driver.id}/edit`}
                    onDelete={() => openDeleteDialog(driver)}
                    isDeleting={deletingDriver === driver.id}
                    editTooltip="Editar motorista"
                    deleteTooltip="Eliminar motorista"
                />
            ),
        },
    ];

    const DriverMobileCard = ({ driver }: { driver: Driver }) => (
        <StandardMobileCard
            icon={Users}
            title={driver.name}
            subtitle={driver.email}
            badge={{
                children: (
                    <StatusBadge status={driver.is_active ? 'active' : 'inactive'} configs={ACTIVE_STATUS_CONFIGS} showIcon={false} className="text-xs" />
                ),
            }}
            actions={{
                editHref: `/drivers/${driver.id}/edit`,
                onDelete: () => openDeleteDialog(driver),
                isDeleting: deletingDriver === driver.id,
                editTooltip: 'Editar motorista',
                deleteTooltip: 'Eliminar motorista',
            }}
            dataFields={[
                {
                    label: 'Telefono',
                    value: driver.phone || 'N/A',
                },
                {
                    label: 'Restaurante',
                    value: driver.restaurant?.name || 'Sin asignar',
                },
                {
                    label: 'Creado',
                    value: formatDate(driver.created_at),
                },
            ]}
        />
    );

    return (
        <AppLayout>
            <Head title="Gestion de Motoristas" />

            <div className="flex h-full flex-1 flex-col gap-6 p-6">
                {/* Filtros adicionales */}
                <div className="flex flex-wrap gap-4">
                    <Select
                        value={filters.restaurant_id || 'all'}
                        onValueChange={(value) => handleFilterChange('restaurant_id', value === 'all' ? null : value)}
                    >
                        <SelectTrigger className="w-[200px]">
                            <SelectValue placeholder="Todos los restaurantes" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">Todos los restaurantes</SelectItem>
                            {restaurants.map((restaurant) => (
                                <SelectItem key={restaurant.id} value={restaurant.id.toString()}>
                                    {restaurant.name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>

                    <Select
                        value={filters.status || 'all'}
                        onValueChange={(value) => handleFilterChange('status', value === 'all' ? null : value)}
                    >
                        <SelectTrigger className="w-[150px]">
                            <SelectValue placeholder="Estado" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">Todos</SelectItem>
                            <SelectItem value="active">Activos</SelectItem>
                            <SelectItem value="inactive">Inactivos</SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <DataTable
                    title="Motoristas"
                    data={drivers}
                    columns={columns}
                    stats={stats}
                    filters={filters}
                    createUrl="/drivers/create"
                    createLabel="Crear"
                    searchPlaceholder="Buscar por nombre, email o telefono..."
                    renderMobileCard={(driver) => <DriverMobileCard driver={driver} />}
                    routeName="/drivers"
                    breakpoint="lg"
                />

                <DeleteConfirmationDialog
                    isOpen={showDeleteDialog}
                    onClose={closeDeleteDialog}
                    onConfirm={handleDeleteDriver}
                    isDeleting={deletingDriver !== null}
                    entityName={driverToDelete?.name || ''}
                    entityType="motorista"
                />
            </div>
        </AppLayout>
    );
}
