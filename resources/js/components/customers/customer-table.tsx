import React from 'react';
import { Users, Clock, CreditCard, MapPin, Phone, Check, X, Award } from 'lucide-react';

import { DataTable } from '@/components/data-table';
import { StatusBadge, CONNECTION_STATUS_CONFIGS, CUSTOMER_TYPE_COLORS } from '@/components/status-badge';
import { AvatarColumn } from '@/components/table-columns/avatar-column';
import { Badge } from '@/components/ui/badge';
import { formatDate, calculateAge, daysSince, formatPoints } from '@/utils/format';
import { CustomersSkeleton } from '@/components/skeletons';

interface Customer {
    id: number;
    full_name: string;
    email: string;
    subway_card: string;
    birth_date: string;
    gender: string | null;
    client_type: string | null;
    customer_type: {
        id: number;
        name: string;
        display_name: string;
        color: string | null;
        multiplier: number;
    } | null;
    phone: string | null;
    location: string | null;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
    last_activity: string | null;
    last_purchase: string | null;
    puntos: number;
    puntos_updated_at: string | null;
    is_online: boolean;
    status: string;
}


interface CustomerTypeStat {
    id: number;
    display_name: string;
    color: string;
    customer_count: number;
}

interface CustomerTableProps {
    customers: {
        data: Customer[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        from: number;
        to: number;
    };
    customer_type_stats: CustomerTypeStat[];
    filters: {
        search: string | null;
        per_page: number;
        sort_field?: string;
        sort_direction?: 'asc' | 'desc';
    };
}

/**
 * Obtiene el icono uniforme para todos los tipos de clientes con el color apropiado
 */
const getCustomerTypeIcon = (color: string, size: string = "h-3 w-3"): React.ReactElement => {
    const colorClass = getCustomerTypeIconColor(color);
    return <Award className={`${size} ${colorClass}`} />;
};

/**
 * Obtiene el color del icono basado en el color del tipo de cliente
 */
const getCustomerTypeIconColor = (color: string): string => {
    switch (color) {
        case 'green': return 'text-green-600';
        case 'orange': return 'text-orange-600';
        case 'gray': return 'text-gray-600';
        case 'yellow': return 'text-yellow-600';
        case 'purple': return 'text-purple-600';
        case 'blue': return 'text-blue-600';
        case 'red': return 'text-red-600';
        default: return 'text-primary';
    }
};

/**
 * Obtiene el color del tipo de cliente basado en el nuevo sistema
 */
const getClientTypeColor = (customerType: Customer['customer_type'], fallbackType?: string | null): string => {
    if (customerType && customerType.color && CUSTOMER_TYPE_COLORS[customerType.color]) {
        return CUSTOMER_TYPE_COLORS[customerType.color].color;
    }

    // Fallback al sistema anterior si no hay customer_type
    switch (fallbackType) {
        case 'premium':
            return CUSTOMER_TYPE_COLORS.yellow.color;
        case 'vip':
            return CUSTOMER_TYPE_COLORS.purple.color;
        case 'regular':
        default:
            return CUSTOMER_TYPE_COLORS.gray.color;
    }
};

export function CustomerTable({
    customers,
    customer_type_stats,
    filters
}: CustomerTableProps) {

    // Generar estadísticas dinámicas basadas en los tipos de cliente reales
    const stats = [
        {
            title: 'Total Clientes',
            value: customers.total,
            icon: <Users className="h-4 w-4 text-blue-600" />,
            description: `${customers.total} cliente${customers.total !== 1 ? 's' : ''} registrados`
        },
        ...customer_type_stats.map(stat => ({
            title: stat.display_name,
            value: stat.customer_count,
            icon: getCustomerTypeIcon(stat.color, "h-4 w-4"),
            description: `${stat.customer_count} cliente${stat.customer_count !== 1 ? 's' : ''}`
        }))
    ];

    const columns = [
        {
            key: 'customer',
            title: 'Cliente',
            sortable: true,
            render: (customer: Customer) => {
                const badges = [];

                if (customer.gender) {
                    badges.push(
                        <Badge key="gender" variant="outline" className="text-xs px-2 py-0.5 bg-indigo-50 text-indigo-700 border-indigo-200">
                            {customer.gender}
                        </Badge>
                    );
                }

                if (customer.birth_date) {
                    badges.push(
                        <Badge key="age" variant="outline" className="text-xs px-2 py-0.5 bg-sky-50 text-sky-700 border-sky-200">
                            {calculateAge(customer.birth_date)} años
                        </Badge>
                    );
                }

                badges.push(
                    customer.email_verified_at ? (
                        <Badge key="verified" variant="outline" className="text-xs px-2 py-0.5 bg-green-50 text-green-700 border-green-200">
                            <Check className="h-3 w-3 mr-1" />
                            Verificado
                        </Badge>
                    ) : (
                        <Badge key="not-verified" variant="outline" className="text-xs px-2 py-0.5 bg-red-50 text-red-700 border-red-200">
                            <X className="h-3 w-3 mr-1" />
                            No verificado
                        </Badge>
                    )
                );

                return (
                    <AvatarColumn
                        icon={<Users className="w-5 h-5 text-primary" />}
                        title={customer.full_name}
                        subtitle={customer.email}
                        badges={badges}
                    />
                );
            }
        },
        {
            key: 'subway_card',
            title: 'Tarjeta Subway',
            render: (customer: Customer) => (
                <div>
                    <div className="flex items-center gap-2">
                        <CreditCard className="h-4 w-4 text-muted-foreground" />
                        <code className="text-sm">{customer.subway_card}</code>
                    </div>
                    <div className="mt-1">
                        <div className="flex items-center gap-2">
                            {customer.customer_type && (
                                <span className="flex items-center">
                                    {getCustomerTypeIcon(customer.customer_type.color, "h-3 w-3")}
                                </span>
                            )}
                            <Badge className={getClientTypeColor(customer.customer_type, customer.client_type)}>
                                {customer.customer_type?.display_name || customer.client_type || 'Regular'}
                            </Badge>
                            {customer.customer_type && (
                                <span className="text-xs text-muted-foreground">
                                    {customer.customer_type.multiplier}x
                                </span>
                            )}
                        </div>
                    </div>
                </div>
            )
        },
        {
            key: 'status',
            title: 'Estatus',
            sortable: true,
            render: (customer: Customer) => (
                <div>
                    <StatusBadge status={customer.status} configs={CONNECTION_STATUS_CONFIGS} />
                    {customer.last_activity && (
                        <div className="text-xs text-muted-foreground mt-1">
                            <Clock className="h-3 w-3 inline mr-1" />
                            {formatDate(customer.last_activity)}
                        </div>
                    )}
                </div>
            )
        },
        {
            key: 'phone',
            title: 'Teléfono',
            render: (customer: Customer) => (
                <div>
                    <div className="text-sm">
                        {customer.phone || 'N/A'}
                    </div>
                    {customer.location && (
                        <div className="text-xs text-muted-foreground flex items-center">
                            <MapPin className="h-3 w-3 inline mr-1" />
                            {customer.location}
                        </div>
                    )}
                </div>
            )
        },
        {
            key: 'last_purchase',
            title: 'Última Compra',
            render: (customer: Customer) => (
                <div>
                    <div className="text-sm">
                        {customer.last_purchase ? formatDate(customer.last_purchase) : 'Sin compras'}
                    </div>
                    {customer.last_purchase && (
                        <div className="text-xs text-muted-foreground">
                            {daysSince(customer.last_purchase)} días
                        </div>
                    )}
                </div>
            )
        },
        {
            key: 'puntos',
            title: 'Puntos',
            render: (customer: Customer) => (
                <div>
                    <div className="text-sm font-medium text-blue-600">
                        {formatPoints(customer.puntos || 0)}
                    </div>
                    {customer.puntos_updated_at && (
                        <div className="text-xs text-muted-foreground">
                            Actualizado: {formatDate(customer.puntos_updated_at)}
                        </div>
                    )}
                </div>
            )
        },
    ];

    const renderMobileCard = (customer: Customer) => (
        <div className="space-y-3 rounded-lg border border-border bg-card p-4 sm:p-5 transition-colors hover:bg-muted/50 hover:shadow-sm">
            {/* Header del card - Nombre y estado */}
            <div className="flex flex-col space-y-2 sm:flex-row sm:items-start sm:justify-between sm:space-y-0">
                <div className="flex-1 min-w-0">
                    <h3 className="font-medium text-foreground truncate mb-1">
                        {customer.full_name}
                    </h3>
                    <p className="text-sm text-muted-foreground truncate">
                        {customer.email}
                    </p>
                </div>
                <div className="flex items-center gap-1 flex-shrink-0">
                    <StatusBadge 
                        status={customer.status} 
                        configs={CONNECTION_STATUS_CONFIGS} 
                        className="text-xs"
                    />
                </div>
            </div>

            {/* Información básica */}
            <div className="grid grid-cols-2 gap-3 text-sm">
                {/* Tarjeta Subway */}
                <div className="space-y-1">
                    <div className="flex items-center gap-1 text-muted-foreground">
                        <CreditCard className="h-3 w-3" />
                        <span className="text-xs">Tarjeta</span>
                    </div>
                    <code className="text-xs font-mono bg-muted px-2 py-1 rounded">
                        {customer.subway_card}
                    </code>
                </div>

                {/* Puntos */}
                <div className="space-y-1">
                    <div className="text-xs text-muted-foreground">Puntos</div>
                    <div className="font-medium text-blue-600">
                        {formatPoints(customer.puntos || 0)}
                    </div>
                </div>
            </div>

            {/* Tipo de cliente */}
            {customer.customer_type && (
                <div className="flex items-center gap-2">
                    {getCustomerTypeIcon(customer.customer_type.color, "h-4 w-4")}
                    <Badge className={getClientTypeColor(customer.customer_type, customer.client_type)}>
                        {customer.customer_type.display_name}
                    </Badge>
                    <span className="text-xs text-muted-foreground">
                        {customer.customer_type.multiplier}x
                    </span>
                </div>
            )}

            {/* Información de contacto */}
            <div className="grid grid-cols-1 gap-2 text-sm">
                {customer.phone && (
                    <div className="flex items-center gap-2 text-muted-foreground">
                        <Phone className="h-3 w-3" />
                        <span>{customer.phone}</span>
                    </div>
                )}
                {customer.location && (
                    <div className="flex items-center gap-2 text-muted-foreground">
                        <MapPin className="h-3 w-3" />
                        <span>{customer.location}</span>
                    </div>
                )}
            </div>

            {/* Verificación de email */}
            <div className="flex items-center justify-between">
                <div className="flex items-center gap-2">
                    {customer.email_verified_at ? (
                        <Badge variant="outline" className="text-xs px-2 py-0.5 bg-green-50 text-green-700 border-green-200">
                            <Check className="h-3 w-3 mr-1" />
                            Verificado
                        </Badge>
                    ) : (
                        <Badge variant="outline" className="text-xs px-2 py-0.5 bg-red-50 text-red-700 border-red-200">
                            <X className="h-3 w-3 mr-1" />
                            No verificado
                        </Badge>
                    )}
                </div>
                
                {/* Última actividad */}
                {customer.last_activity && (
                    <div className="text-xs text-muted-foreground flex items-center gap-1">
                        <Clock className="h-3 w-3" />
                        <span>{formatDate(customer.last_activity)}</span>
                    </div>
                )}
            </div>

            {/* Última compra */}
            {customer.last_purchase && (
                <div className="text-xs text-muted-foreground">
                    <span className="font-medium">Última compra:</span> {formatDate(customer.last_purchase)}
                    <span className="ml-2">
                        ({daysSince(customer.last_purchase)} días)
                    </span>
                </div>
            )}
        </div>
    );

    return (
        <DataTable
            title="Gestión de Clientes"
            description="Administra los clientes del sistema."
            data={customers}
            columns={columns}
            stats={stats}
            filters={filters}
            createUrl="/customers/create"
            createLabel="Nuevo Cliente"
            searchPlaceholder="Buscar por nombre, email, tarjeta subway o teléfono..."
            loadingSkeleton={CustomersSkeleton}
            renderMobileCard={renderMobileCard}
            route="customers.index"
        />
    );
}