import { NOTIFICATIONS } from '@/constants/ui-constants';
import { showNotification } from '@/hooks/useNotifications';
import { Head, router } from '@inertiajs/react';
import { Building2, CheckCircle, Clock, FileText, MapPin, Phone, ShoppingBag, Truck } from 'lucide-react';
import { useCallback, useState } from 'react';

import { DataTable } from '@/components/DataTable';
import { DeleteConfirmationDialog } from '@/components/DeleteConfirmationDialog';
import { EntityInfoCell } from '@/components/EntityInfoCell';
import { RestaurantsSkeleton } from '@/components/skeletons';
import { StandardMobileCard } from '@/components/StandardMobileCard';
import { ACTIVE_STATUS_CONFIGS, SERVICE_STATUS_CONFIGS, StatusBadge } from '@/components/status-badge';
import { TableActions } from '@/components/TableActions';
import { Badge } from '@/components/ui/badge';
import AppLayout from '@/layouts/app-layout';
import { formatDate, formatNumber } from '@/utils/format';

interface Restaurant {
    id: number;
    name: string;
    address: string;
    latitude: number | null;
    longitude: number | null;
    is_active: boolean;
    delivery_active: boolean;
    pickup_active: boolean;
    phone: string | null;
    email: string | null;
    schedule: Record<string, unknown>; // JSON
    minimum_order_amount: number;
    estimated_delivery_time: number;
    geofence_kml: string | null;
    status_text: string;
    today_schedule: string | null;
    is_open_now: boolean;
    has_geofence: boolean;
    coordinates: { lat: number; lng: number } | null;
    created_at: string;
    updated_at: string;
}

interface Filters {
    search: string | null;
    per_page: number;
    sort_field?: string;
    sort_direction?: 'asc' | 'desc';
}

interface RestaurantsPageProps {
    restaurants: {
        data: Restaurant[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        from: number;
        to: number;
    };
    total_restaurants: number;
    active_restaurants: number;
    delivery_restaurants: number;
    pickup_restaurants: number;
    filters: Filters;
}

/**
 * Obtiene el tipo de servicio del restaurante
 */
const getServiceType = (delivery_active: boolean, pickup_active: boolean): string => {
    if (delivery_active && pickup_active) return 'both';
    if (delivery_active) return 'delivery';
    if (pickup_active) return 'pickup';
    return 'none';
};

/**
 * Página principal de gestión de restaurantes
 * Refactorizada para usar DataTable unificado directamente
 */
export default function RestaurantsIndex({
    restaurants,
    total_restaurants,
    active_restaurants,
    delivery_restaurants,
    pickup_restaurants,
    filters,
}: RestaurantsPageProps) {
    const [deletingRestaurant, setDeletingRestaurant] = useState<number | null>(null);
    const [restaurantToDelete, setRestaurantToDelete] = useState<Restaurant | null>(null);
    const [showDeleteDialog, setShowDeleteDialog] = useState(false);

    const openDeleteDialog = useCallback((restaurant: Restaurant) => {
        setRestaurantToDelete(restaurant);
        setShowDeleteDialog(true);
    }, []);

    const closeDeleteDialog = useCallback(() => {
        setRestaurantToDelete(null);
        setShowDeleteDialog(false);
        setDeletingRestaurant(null);
    }, []);

    const handleDeleteRestaurant = async () => {
        if (!restaurantToDelete) return;

        setDeletingRestaurant(restaurantToDelete.id);
        router.delete(`/restaurants/${restaurantToDelete.id}`, {
            preserveState: false,
            onBefore: () => {
                closeDeleteDialog();
            },
            onError: (error) => {
                setDeletingRestaurant(null);
                if (error.message) {
                    showNotification.error(error.message);
                } else {
                    showNotification.error(NOTIFICATIONS.error.deleteRestaurant);
                }
            },
        });
    };

    const stats = [
        {
            title: 'Total',
            value: total_restaurants,
            icon: <Building2 className="h-4 w-4" />,
        },
        {
            title: 'Activos',
            value: active_restaurants,
            icon: <CheckCircle className="h-4 w-4" />,
        },
        {
            title: 'Con Delivery',
            value: delivery_restaurants,
            icon: <Truck className="h-4 w-4" />,
        },
        {
            title: 'Con Pickup',
            value: pickup_restaurants,
            icon: <ShoppingBag className="h-4 w-4" />,
        },
    ];

    const columns = [
        {
            key: 'restaurant',
            title: 'Restaurante',
            width: 'lg' as const,
            sortable: true,
            render: (restaurant: Restaurant) => {
                const badges = [];

                // Geofence badge si existe
                if (restaurant.has_geofence) {
                    badges.push(
                        <Badge key="geofence" variant="outline" className="border-green-200 bg-green-50 px-2 py-0.5 text-xs text-green-700">
                            <FileText className="mr-1 h-3 w-3" />
                            KML
                        </Badge>,
                    );
                }

                return (
                    <EntityInfoCell
                        icon={Building2}
                        primaryText={restaurant.name}
                        secondaryText={restaurant.email || 'Sin email'}
                        badges={<>{badges}</>}
                    />
                );
            },
        },
        {
            key: 'location',
            title: 'Ubicación',
            width: 'xl' as const,
            render: (restaurant: Restaurant) => (
                <div>
                    <div className="mb-1 flex items-center gap-2">
                        <MapPin className="h-4 w-4 text-muted-foreground" />
                        <span className="text-sm break-words">{restaurant.address}</span>
                    </div>
                    {restaurant.phone && (
                        <div className="flex items-center gap-2">
                            <Phone className="h-3 w-3 text-muted-foreground" />
                            <span className="text-xs text-muted-foreground">{restaurant.phone}</span>
                        </div>
                    )}
                </div>
            ),
        },
        {
            key: 'services',
            title: 'Servicios',
            width: 'md' as const,
            render: (restaurant: Restaurant) => {
                const serviceType = getServiceType(restaurant.delivery_active, restaurant.pickup_active);

                return (
                    <div className="space-y-2">
                        <StatusBadge status={serviceType} configs={SERVICE_STATUS_CONFIGS} className="text-xs" />
                        <div className="text-xs text-muted-foreground">
                            <div>Min. orden: Q{formatNumber(restaurant.minimum_order_amount)}</div>
                            <div className="mt-1 flex items-center gap-1">
                                <Clock className="h-3 w-3" />
                                {restaurant.estimated_delivery_time}min
                            </div>
                        </div>
                    </div>
                );
            },
        },
        {
            key: 'status',
            title: 'Estado',
            width: 'sm' as const,
            sortable: true,
            render: (restaurant: Restaurant) => (
                <div className="space-y-1">
                    <StatusBadge status={restaurant.is_active ? 'active' : 'inactive'} configs={ACTIVE_STATUS_CONFIGS} className="text-xs" />
                    <div className="text-xs text-muted-foreground">{restaurant.is_open_now ? 'Abierto ahora' : 'Cerrado'}</div>
                </div>
            ),
        },
        {
            key: 'created_at',
            title: 'Creado',
            width: 'sm' as const,
            sortable: true,
            render: (restaurant: Restaurant) => <div className="text-sm text-muted-foreground">{formatDate(restaurant.created_at)}</div>,
        },
        {
            key: 'actions',
            title: 'Acciones',
            width: 'xs' as const,
            textAlign: 'right' as const,
            render: (restaurant: Restaurant) => (
                <TableActions
                    editHref={`/restaurants/${restaurant.id}/edit`}
                    onDelete={() => openDeleteDialog(restaurant)}
                    isDeleting={deletingRestaurant === restaurant.id}
                    editTooltip="Editar restaurante"
                    deleteTooltip="Eliminar restaurante"
                />
            ),
        },
    ];

    const RestaurantMobileCard = ({ restaurant }: { restaurant: Restaurant }) => (
        <StandardMobileCard
            icon={Building2}
            title={restaurant.name}
            subtitle={restaurant.email || 'Sin email'}
            badge={{
                children: (
                    <StatusBadge
                        status={restaurant.is_active ? 'active' : 'inactive'}
                        configs={ACTIVE_STATUS_CONFIGS}
                        showIcon={false}
                        className="text-xs"
                    />
                ),
            }}
            actions={{
                editHref: `/restaurants/${restaurant.id}/edit`,
                onDelete: () => openDeleteDialog(restaurant),
                isDeleting: deletingRestaurant === restaurant.id,
                editTooltip: 'Editar restaurante',
                deleteTooltip: 'Eliminar restaurante',
            }}
            dataFields={[
                {
                    label: 'Dirección',
                    value: (
                        <div className="flex items-center gap-2">
                            <MapPin className="h-3 w-3 text-muted-foreground" />
                            <span className="text-sm">{restaurant.address}</span>
                        </div>
                    ),
                },
                {
                    label: 'Teléfono',
                    value: (
                        <div className="flex items-center gap-2">
                            <Phone className="h-3 w-3 text-muted-foreground" />
                            <span>{restaurant.phone}</span>
                        </div>
                    ),
                    condition: !!restaurant.phone,
                },
                {
                    label: 'Geocerca',
                    value: (
                        <Badge variant="outline" className="border-green-200 bg-green-50 px-2 py-0.5 text-xs text-green-700">
                            <FileText className="mr-1 h-3 w-3" />
                            KML Cargado
                        </Badge>
                    ),
                    condition: restaurant.has_geofence,
                },
                {
                    label: 'Servicios',
                    value: (
                        <StatusBadge
                            status={getServiceType(restaurant.delivery_active, restaurant.pickup_active)}
                            configs={SERVICE_STATUS_CONFIGS}
                            className="text-xs"
                        />
                    ),
                },
                {
                    label: 'Pedido Mínimo',
                    value: `Q${formatNumber(restaurant.minimum_order_amount)}`,
                },
                {
                    label: 'Tiempo Entrega',
                    value: (
                        <div className="flex items-center gap-1">
                            <Clock className="h-3 w-3 text-muted-foreground" />
                            <span>{restaurant.estimated_delivery_time} min</span>
                        </div>
                    ),
                },
                {
                    label: 'Estado Actual',
                    value: restaurant.is_open_now ? 'Abierto ahora' : 'Cerrado',
                },
                {
                    label: 'Horario Hoy',
                    value: restaurant.today_schedule || 'No definido',
                },
                {
                    label: 'Creado',
                    value: formatDate(restaurant.created_at),
                },
            ]}
        />
    );

    return (
        <AppLayout>
            <Head title="Gestión de Restaurantes" />

            <DataTable
                title="Restaurantes"
                data={restaurants}
                columns={columns}
                stats={stats}
                filters={filters}
                createUrl="/restaurants/create"
                createLabel="Crear"
                searchPlaceholder="Buscar por nombre, dirección, teléfono o email..."
                loadingSkeleton={RestaurantsSkeleton}
                renderMobileCard={(restaurant) => <RestaurantMobileCard restaurant={restaurant} />}
                routeName="/restaurants"
                breakpoint="lg"
            />

            <DeleteConfirmationDialog
                isOpen={showDeleteDialog}
                onClose={closeDeleteDialog}
                onConfirm={handleDeleteRestaurant}
                isDeleting={deletingRestaurant !== null}
                entityName={restaurantToDelete?.name || ''}
                entityType="restaurante"
            />
        </AppLayout>
    );
}
