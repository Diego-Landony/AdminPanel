import { showNotification } from '@/hooks/useNotifications';
import { Head, router } from '@inertiajs/react';
import { CheckCircle, Clock, Users, XCircle } from 'lucide-react';
import { useCallback, useState } from 'react';

import { DataTable } from '@/components/DataTable';
import { DeleteConfirmationDialog } from '@/components/DeleteConfirmationDialog';
import { EntityInfoCell } from '@/components/EntityInfoCell';
import { StandardMobileCard } from '@/components/StandardMobileCard';
import { ACTIVE_STATUS_CONFIGS, StatusBadge } from '@/components/status-badge';
import { TableActions } from '@/components/TableActions';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip';
import { NOTIFICATIONS } from '@/constants/ui-constants';
import AppLayout from '@/layouts/app-layout';
import { Driver, Filters, PaginatedData, Restaurant } from '@/types';
import { formatDate } from '@/utils/format';

interface DriversPageProps {
    drivers: PaginatedData<Driver>;
    restaurants: Restaurant[];
    total_drivers: number;
    active_drivers: number;
    available_drivers: number;
    filters: Filters & {
        restaurant_id?: string | null;
        status?: string | null;
        availability?: string | null;
    };
}

/**
 * Configuraciones de estado de disponibilidad
 */
const AVAILABILITY_STATUS_CONFIGS: Record<string, { color: string; text: string; icon: React.ReactNode }> = {
    available: {
        color: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300 border border-green-200 dark:border-green-700',
        text: 'Disponible',
        icon: <CheckCircle className="h-3 w-3" />,
    },
    unavailable: {
        color: 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300 border border-gray-200 dark:border-gray-600',
        text: 'No Disponible',
        icon: <XCircle className="h-3 w-3" />,
    },
    default: {
        color: 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300 border border-gray-200 dark:border-gray-600',
        text: 'Desconocido',
        icon: <Clock className="h-3 w-3" />,
    },
};

/**
 * Pagina principal de gestion de motoristas
 */
export default function DriversIndex({
    drivers,
    restaurants,
    total_drivers,
    active_drivers,
    available_drivers,
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

    const handleToggleAvailability = (driver: Driver) => {
        router.patch(
            `/drivers/${driver.id}/toggle-availability`,
            {},
            {
                preserveState: true,
                preserveScroll: true,
                onError: (error) => {
                    if (error.message) {
                        showNotification.error(error.message);
                    } else {
                        showNotification.error(NOTIFICATIONS.error.server);
                    }
                },
            },
        );
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
        if (key === 'availability' && value) params.availability = value;
        else if (filters.availability) params.availability = filters.availability;

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
        {
            title: 'disponibles',
            value: available_drivers,
            icon: <CheckCircle className="h-4 w-4 text-blue-600" />,
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
            key: 'availability',
            title: 'Disponibilidad',
            width: 'sm' as const,
            render: (driver: Driver) => (
                <div className="flex items-center gap-2">
                    <StatusBadge
                        status={driver.is_available ? 'available' : 'unavailable'}
                        configs={AVAILABILITY_STATUS_CONFIGS}
                        className="text-xs"
                    />
                    {driver.active_orders_count !== undefined && driver.active_orders_count > 0 && (
                        <Badge variant="secondary" className="text-xs">
                            {driver.active_orders_count} orden(es)
                        </Badge>
                    )}
                </div>
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
                <div className="flex items-center justify-end gap-1">
                    <Tooltip>
                        <TooltipTrigger asChild>
                            <Button
                                variant="ghost"
                                size="sm"
                                className="h-8 w-8 p-0"
                                onClick={() => handleToggleAvailability(driver)}
                                disabled={!driver.is_active}
                            >
                                {driver.is_available ? (
                                    <XCircle className="h-4 w-4 text-orange-500" />
                                ) : (
                                    <CheckCircle className="h-4 w-4 text-green-500" />
                                )}
                            </Button>
                        </TooltipTrigger>
                        <TooltipContent>
                            <p>{driver.is_available ? 'Marcar como no disponible' : 'Marcar como disponible'}</p>
                        </TooltipContent>
                    </Tooltip>
                    <TableActions
                        editHref={`/drivers/${driver.id}/edit`}
                        onDelete={() => openDeleteDialog(driver)}
                        isDeleting={deletingDriver === driver.id}
                        editTooltip="Editar motorista"
                        deleteTooltip="Eliminar motorista"
                    />
                </div>
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
                    label: 'Disponibilidad',
                    value: (
                        <StatusBadge
                            status={driver.is_available ? 'available' : 'unavailable'}
                            configs={AVAILABILITY_STATUS_CONFIGS}
                            className="text-xs"
                        />
                    ),
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

                    <Select
                        value={filters.availability || 'all'}
                        onValueChange={(value) => handleFilterChange('availability', value === 'all' ? null : value)}
                    >
                        <SelectTrigger className="w-[150px]">
                            <SelectValue placeholder="Disponibilidad" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">Todos</SelectItem>
                            <SelectItem value="available">Disponibles</SelectItem>
                            <SelectItem value="unavailable">No disponibles</SelectItem>
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
