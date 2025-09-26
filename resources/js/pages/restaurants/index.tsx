import React, { useState, useCallback } from 'react';
import { Head, router } from "@inertiajs/react";
import { MapPin, Phone, Clock, Star, Truck, ShoppingBag, Building2, CheckCircle, XCircle, Badge as BadgeIcon } from 'lucide-react';
import { showNotification } from '@/hooks/useNotifications';

import AppLayout from "@/layouts/app-layout";
import { EntityInfoCell } from '@/components/EntityInfoCell';
import { DataTable } from '@/components/DataTable';
import { StatusBadge, ACTIVE_STATUS_CONFIGS, SERVICE_STATUS_CONFIGS } from '@/components/status-badge';
import { StandardMobileCard } from '@/components/StandardMobileCard';
import { TableActions } from '@/components/TableActions';
import { DeleteConfirmationDialog } from '@/components/DeleteConfirmationDialog';
import { Badge } from '@/components/ui/badge';
import { formatDate, formatNumber } from '@/utils/format';
import { RestaurantsSkeleton } from '@/components/skeletons';

interface Restaurant {
    id: number;
    name: string;
    description: string | null;
    latitude: number;
    longitude: number;
    address: string;
    is_active: boolean;
    delivery_active: boolean;
    pickup_active: boolean;
    phone: string | null;
    schedule: Record<string, unknown>; // JSON
    minimum_order_amount: number;
    delivery_area: Record<string, unknown>; // JSON
    image: string | null;
    email: string | null;
    manager_name: string | null;
    delivery_fee: number;
    estimated_delivery_time: number;
    rating: number;
    total_reviews: number;
    sort_order: number;
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
 * Renderiza las estrellas de rating
 */
const renderStars = (rating: number, total_reviews: number = 0) => {
    const stars = [];
    const fullStars = Math.floor(rating);
    const hasHalfStar = rating % 1 !== 0;

    for (let i = 0; i < 5; i++) {
        if (i < fullStars) {
            stars.push(
                <Star key={i} className="h-3 w-3 fill-yellow-400 text-yellow-400" />
            );
        } else if (i === fullStars && hasHalfStar) {
            stars.push(
                <Star key={i} className="h-3 w-3 fill-yellow-400/50 text-yellow-400" />
            );
        } else {
            stars.push(
                <Star key={i} className="h-3 w-3 text-gray-300" />
            );
        }
    }

    return (
        <div className="flex items-center gap-1">
            <div className="flex">{stars}</div>
            <span className="text-xs text-muted-foreground ml-1">
                ({total_reviews})
            </span>
        </div>
    );
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
            onSuccess: () => {
                closeDeleteDialog();
            },
            onError: (error) => {
                setDeletingRestaurant(null);
                if (error.message) {
                    showNotification.error(error.message);
                } else {
                    showNotification.error('Error al eliminar el restaurante');
                }
            }
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

                // Rating badge
                if (restaurant.rating > 0) {
                    badges.push(
                        <div key="rating" className="flex items-center gap-1">
                            {renderStars(restaurant.rating, restaurant.total_reviews)}
                        </div>
                    );
                }

                // Manager badge si existe
                if (restaurant.manager_name) {
                    badges.push(
                        <Badge key="manager" variant="outline" className="text-xs px-2 py-0.5">
                            Mgr: {restaurant.manager_name}
                        </Badge>
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
            }
        },
        {
            key: 'location',
            title: 'Ubicación',
            width: 'xl' as const,
            render: (restaurant: Restaurant) => (
                <div>
                    <div className="flex items-center gap-2 mb-1">
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
            )
        },
        {
            key: 'services',
            title: 'Servicios',
            width: 'md' as const,
            render: (restaurant: Restaurant) => {
                const serviceType = getServiceType(restaurant.delivery_active, restaurant.pickup_active);

                return (
                    <div className="space-y-2">
                        <StatusBadge
                            status={serviceType}
                            configs={SERVICE_STATUS_CONFIGS}
                            className="text-xs"
                        />
                        <div className="text-xs text-muted-foreground">
                            <div>Min. orden: Q{formatNumber(restaurant.minimum_order_amount)}</div>
                            {restaurant.delivery_active && (
                                <div>Envío: Q{formatNumber(restaurant.delivery_fee)}</div>
                            )}
                            <div className="flex items-center gap-1 mt-1">
                                <Clock className="h-3 w-3" />
                                {restaurant.estimated_delivery_time}min
                            </div>
                        </div>
                    </div>
                );
            }
        },
        {
            key: 'status',
            title: 'Estado',
            width: 'sm' as const,
            sortable: true,
            render: (restaurant: Restaurant) => (
                <div className="space-y-1">
                    <StatusBadge
                        status={restaurant.is_active ? 'active' : 'inactive'}
                        configs={ACTIVE_STATUS_CONFIGS}
                        className="text-xs"
                    />
                    <div className="text-xs text-muted-foreground">
                        Orden: {restaurant.sort_order}
                    </div>
                </div>
            )
        },
        {
            key: 'created_at',
            title: 'Creado',
            width: 'sm' as const,
            sortable: true,
            render: (restaurant: Restaurant) => (
                <div className="text-sm text-muted-foreground">
                    {formatDate(restaurant.created_at)}
                </div>
            )
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
            )
        },
    ];

    const RestaurantMobileCard = ({ restaurant }: { restaurant: Restaurant }) => (
        <StandardMobileCard
            icon={Building2}
            title={restaurant.name}
            subtitle={restaurant.email || 'Sin email'}
            badge={{
                children: <StatusBadge
                    status={restaurant.is_active ? 'active' : 'inactive'}
                    configs={ACTIVE_STATUS_CONFIGS}
                    showIcon={false}
                    className="text-xs"
                />
            }}
            actions={{
                editHref: `/restaurants/${restaurant.id}/edit`,
                onDelete: () => openDeleteDialog(restaurant),
                isDeleting: deletingRestaurant === restaurant.id,
                editTooltip: "Editar restaurante",
                deleteTooltip: "Eliminar restaurante"
            }}
            dataFields={[
                {
                    label: "Rating",
                    value: renderStars(restaurant.rating, restaurant.total_reviews),
                    condition: restaurant.rating > 0
                },
                {
                    label: "Manager",
                    value: (
                        <Badge variant="outline" className="text-xs px-2 py-0.5">
                            {restaurant.manager_name}
                        </Badge>
                    ),
                    condition: !!restaurant.manager_name
                },
                {
                    label: "Dirección",
                    value: (
                        <div className="flex items-center gap-2">
                            <MapPin className="h-3 w-3 text-muted-foreground" />
                            <span className="text-sm">{restaurant.address}</span>
                        </div>
                    )
                },
                {
                    label: "Teléfono",
                    value: (
                        <div className="flex items-center gap-2">
                            <Phone className="h-3 w-3 text-muted-foreground" />
                            <span>{restaurant.phone}</span>
                        </div>
                    ),
                    condition: !!restaurant.phone
                },
                {
                    label: "Servicios",
                    value: (
                        <StatusBadge
                            status={getServiceType(restaurant.delivery_active, restaurant.pickup_active)}
                            configs={SERVICE_STATUS_CONFIGS}
                            className="text-xs"
                        />
                    )
                },
                {
                    label: "Pedido Mínimo",
                    value: `Q${formatNumber(restaurant.minimum_order_amount)}`
                },
                {
                    label: "Costo Envío",
                    value: `Q${formatNumber(restaurant.delivery_fee)}`,
                    condition: restaurant.delivery_active
                },
                {
                    label: "Tiempo Entrega",
                    value: (
                        <div className="flex items-center gap-1">
                            <Clock className="h-3 w-3 text-muted-foreground" />
                            <span>{restaurant.estimated_delivery_time} min</span>
                        </div>
                    )
                },
                {
                    label: "Descripción",
                    value: (
                        <p className="text-sm line-clamp-2">
                            {restaurant.description}
                        </p>
                    ),
                    condition: !!restaurant.description
                },
                {
                    label: "Orden",
                    value: restaurant.sort_order
                },
                {
                    label: "Creado",
                    value: formatDate(restaurant.created_at)
                }
            ]}
        />
    );

    return (
        <AppLayout>
            <Head title="Gestión de Restaurantes" />

            <DataTable
                title="Gestión de Restaurantes"
                description="Administra los restaurantes y sus servicios."
                data={restaurants}
                columns={columns}
                stats={stats}
                filters={filters}
                createUrl="/restaurants/create"
                createLabel="Nuevo Restaurante"
                searchPlaceholder="Buscar por nombre, dirección, teléfono o manager..."
                loadingSkeleton={RestaurantsSkeleton}
                renderMobileCard={(restaurant) => <RestaurantMobileCard restaurant={restaurant} />}
                routeName="/restaurants"
                breakpoint="md"
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