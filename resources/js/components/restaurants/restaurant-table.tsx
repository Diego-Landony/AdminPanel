import React from 'react';
import { MapPin, Phone, Clock, Star, Truck, ShoppingBag, Building2, CheckCircle, XCircle, Badge as BadgeIcon } from 'lucide-react';

import { DataTable } from '@/components/DataTable';
import { StatusBadge } from '@/components/status-badge';
import { ResponsiveCard, ResponsiveCardHeader, ResponsiveCardContent, DataField, BadgeGroup } from '@/components/CardLayout';
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

interface RestaurantTableProps {
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
    filters: {
        search: string | null;
        per_page: number;
        sort_field?: string;
        sort_direction?: 'asc' | 'desc';
    };
}

// Configuraciones específicas para estados de restaurante
const RESTAURANT_STATUS_CONFIGS = {
    active: {
        color: 'bg-green-100 text-green-800 border-green-200',
        text: 'Activo',
        icon: <CheckCircle className="h-3 w-3" />
    },
    inactive: {
        color: 'bg-red-100 text-red-800 border-red-200',
        text: 'Inactivo',
        icon: <XCircle className="h-3 w-3" />
    },
    default: {
        color: 'bg-gray-100 text-gray-700 border-gray-200',
        text: 'Desconocido',
        icon: <XCircle className="h-3 w-3" />
    }
};

const SERVICE_STATUS_CONFIGS = {
    both: {
        color: 'bg-blue-100 text-blue-800 border-blue-200',
        text: 'Delivery + Pickup',
        icon: <BadgeIcon className="h-3 w-3" />
    },
    delivery: {
        color: 'bg-green-100 text-green-800 border-green-200',
        text: 'Solo Delivery',
        icon: <Truck className="h-3 w-3" />
    },
    pickup: {
        color: 'bg-orange-100 text-orange-800 border-orange-200',
        text: 'Solo Pickup',
        icon: <ShoppingBag className="h-3 w-3" />
    },
    none: {
        color: 'bg-gray-100 text-gray-700 border-gray-200',
        text: 'No disponible',
        icon: <XCircle className="h-3 w-3" />
    },
    default: {
        color: 'bg-gray-100 text-gray-700 border-gray-200',
        text: 'No disponible',
        icon: <XCircle className="h-3 w-3" />
    }
};

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

export function RestaurantTable({
    restaurants,
    total_restaurants,
    active_restaurants,
    delivery_restaurants,
    pickup_restaurants,
    filters
}: RestaurantTableProps) {

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
                    <div className="flex items-center gap-3">
                        <div className="w-10 h-10 rounded-full bg-primary/10 flex items-center justify-center flex-shrink-0">
                            <Building2 className="w-5 h-5 text-primary" />
                        </div>
                        <div className="min-w-0 flex-1">
                            <div className="font-medium text-sm text-foreground break-words">
                                {restaurant.name}
                            </div>
                            <div className="text-sm text-muted-foreground break-words">
                                {restaurant.email || 'Sin email'}
                            </div>
                            <div className="flex flex-wrap gap-1 mt-1">
                                {badges}
                            </div>
                        </div>
                    </div>
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
                        configs={RESTAURANT_STATUS_CONFIGS} 
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
    ];

    const RestaurantMobileCard = ({ restaurant }: { restaurant: Restaurant }) => (
        <ResponsiveCard>
            <ResponsiveCardHeader
                icon={<Building2 className="w-4 h-4 text-primary" />}
                title={restaurant.name}
                subtitle={restaurant.email || 'Sin email'}
                badge={{
                    children: restaurant.is_active ? 'Activo' : 'Inactivo',
                    variant: restaurant.is_active ? "default" : "destructive"
                }}
            />

            <ResponsiveCardContent>

                {restaurant.rating > 0 && (
                    <DataField
                        label="Rating"
                        value={renderStars(restaurant.rating, restaurant.total_reviews)}
                    />
                )}

                {restaurant.manager_name && (
                    <DataField
                        label="Manager"
                        value={
                            <Badge variant="outline" className="text-xs px-2 py-0.5">
                                {restaurant.manager_name}
                            </Badge>
                        }
                    />
                )}

                <DataField
                    label="Dirección"
                    value={
                        <div className="flex items-center gap-2">
                            <MapPin className="h-3 w-3 text-muted-foreground" />
                            <span className="text-sm">{restaurant.address}</span>
                        </div>
                    }
                />

                {restaurant.phone && (
                    <DataField
                        label="Teléfono"
                        value={
                            <div className="flex items-center gap-2">
                                <Phone className="h-3 w-3 text-muted-foreground" />
                                <span>{restaurant.phone}</span>
                            </div>
                        }
                    />
                )}

                <DataField
                    label="Servicios"
                    value={
                        <StatusBadge
                            status={getServiceType(restaurant.delivery_active, restaurant.pickup_active)}
                            configs={SERVICE_STATUS_CONFIGS}
                            className="text-xs"
                        />
                    }
                />

                <DataField
                    label="Pedido Mínimo"
                    value={`Q${formatNumber(restaurant.minimum_order_amount)}`}
                />

                {restaurant.delivery_active && (
                    <DataField
                        label="Costo Envío"
                        value={`Q${formatNumber(restaurant.delivery_fee)}`}
                    />
                )}

                <DataField
                    label="Tiempo Entrega"
                    value={
                        <div className="flex items-center gap-1">
                            <Clock className="h-3 w-3 text-muted-foreground" />
                            <span>{restaurant.estimated_delivery_time} min</span>
                        </div>
                    }
                />

                {restaurant.description && (
                    <DataField
                        label="Descripción"
                        value={
                            <p className="text-sm line-clamp-2">
                                {restaurant.description}
                            </p>
                        }
                    />
                )}

                <DataField
                    label="Orden"
                    value={restaurant.sort_order}
                />

                <DataField
                    label="Creado"
                    value={formatDate(restaurant.created_at)}
                />
            </ResponsiveCardContent>
        </ResponsiveCard>
    );

    return (
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
    );
}