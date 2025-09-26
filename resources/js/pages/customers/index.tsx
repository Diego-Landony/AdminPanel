import React, { useState, useCallback } from 'react';
import { Head, router } from '@inertiajs/react';
import { Users, Clock, CreditCard, MapPin, Phone, Check, X, Award } from 'lucide-react';
import { showNotification } from '@/hooks/useNotifications';

import AppLayout from '@/layouts/app-layout';
import { EntityInfoCell } from '@/components/EntityInfoCell';
import { StandardMobileCard } from '@/components/StandardMobileCard';
import { DataTable } from '@/components/DataTable';
import { StatusBadge, CONNECTION_STATUS_CONFIGS, CUSTOMER_TYPE_COLORS } from '@/components/status-badge';
import { TableActions } from '@/components/TableActions';
import { DeleteConfirmationDialog } from '@/components/DeleteConfirmationDialog';
import { Badge } from '@/components/ui/badge';
import { formatDate, calculateAge, daysSince, formatPoints } from '@/utils/format';
import { CustomersSkeleton } from '@/components/skeletons';


/**
 * Interfaz para los datos del cliente
 */
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

/**
 * Interfaz para las estadísticas de tipos de clientes
 */
interface CustomerTypeStat {
    id: number;
    display_name: string;
    color: string;
    customer_count: number;
}

/**
 * Interfaz para las props de la página
 */
interface CustomersPageProps {
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

/**
 * Página principal de gestión de clientes
 * Refactorizada para usar DataTable unificado directamente
 */
export default function CustomersIndex({
    customers,
    customer_type_stats,
    filters,
}: CustomersPageProps) {
    const [deletingCustomer, setDeletingCustomer] = useState<number | null>(null);
    const [customerToDelete, setCustomerToDelete] = useState<Customer | null>(null);
    const [showDeleteDialog, setShowDeleteDialog] = useState(false);

    const openDeleteDialog = useCallback((customer: Customer) => {
        setCustomerToDelete(customer);
        setShowDeleteDialog(true);
    }, []);

    const closeDeleteDialog = useCallback(() => {
        setCustomerToDelete(null);
        setShowDeleteDialog(false);
        setDeletingCustomer(null);
    }, []);

    const handleDeleteCustomer = async () => {
        if (!customerToDelete) return;

        setDeletingCustomer(customerToDelete.id);
        router.delete(`/customers/${customerToDelete.id}`, {
            onSuccess: () => {
                closeDeleteDialog();
            },
            onError: (error) => {
                setDeletingCustomer(null);
                if (error.message) {
                    showNotification.error(error.message);
                } else {
                    showNotification.error('Error al eliminar el cliente');
                }
            }
        });
    };
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
            width: 'lg' as const,
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
                    <EntityInfoCell
                        icon={Users}
                        primaryText={customer.full_name}
                        secondaryText={customer.email}
                        badges={<>{badges}</>}
                    />
                );
            }
        },
        {
            key: 'subway_card',
            title: 'Tarjeta Subway',
            width: 'md' as const,
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
                                    {getCustomerTypeIcon(customer.customer_type.color || 'gray', "h-3 w-3")}
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
            width: 'sm' as const,
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
            width: 'md' as const,
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
            width: 'md' as const,
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
            width: 'sm' as const,
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
        {
            key: 'actions',
            title: 'Acciones',
            width: 'xs' as const,
            textAlign: 'right' as const,
            render: (customer: Customer) => (
                <TableActions
                    editHref={`/customers/${customer.id}/edit`}
                    onDelete={() => openDeleteDialog(customer)}
                    isDeleting={deletingCustomer === customer.id}
                    editTooltip="Editar cliente"
                    deleteTooltip="Eliminar cliente"
                />
            )
        },
    ];

    const CustomerMobileCard = ({ customer }: { customer: Customer }) => (
        <StandardMobileCard
            icon={Users}
            title={customer.full_name}
            subtitle={customer.email}
            badge={{
                children: <StatusBadge status={customer.status} configs={CONNECTION_STATUS_CONFIGS} className="text-xs" />
            }}
            actions={{
                editHref: `/customers/${customer.id}/edit`,
                onDelete: () => openDeleteDialog(customer),
                isDeleting: deletingCustomer === customer.id,
                editTooltip: "Editar cliente",
                deleteTooltip: "Eliminar cliente"
            }}
            dataFields={[
                {
                    label: "Tarjeta Subway",
                    value: (
                        <div className="flex items-center gap-2">
                            <CreditCard className="h-3 w-3 text-muted-foreground" />
                            <code className="text-xs font-mono bg-muted px-2 py-1 rounded">
                                {customer.subway_card}
                            </code>
                        </div>
                    )
                },
                {
                    label: "Puntos",
                    value: (
                        <div className="font-medium text-blue-600">
                            {formatPoints(customer.puntos || 0)}
                        </div>
                    )
                },
                {
                    label: "Tipo de Cliente",
                    value: (
                        <div className="flex items-center gap-2">
                            {getCustomerTypeIcon(customer.customer_type?.color || 'gray', "h-4 w-4")}
                            <Badge className={getClientTypeColor(customer.customer_type, customer.client_type)}>
                                {customer.customer_type?.display_name || customer.client_type || 'Regular'}
                            </Badge>
                            {customer.customer_type && (
                                <span className="text-xs text-muted-foreground">
                                    {customer.customer_type.multiplier}x
                                </span>
                            )}
                        </div>
                    ),
                    condition: !!customer.customer_type
                },
                {
                    label: "Teléfono",
                    value: (
                        <div className="flex items-center gap-2">
                            <Phone className="h-3 w-3 text-muted-foreground" />
                            <span>{customer.phone}</span>
                        </div>
                    ),
                    condition: !!customer.phone
                },
                {
                    label: "Ubicación",
                    value: (
                        <div className="flex items-center gap-2">
                            <MapPin className="h-3 w-3 text-muted-foreground" />
                            <span>{customer.location}</span>
                        </div>
                    ),
                    condition: !!customer.location
                },
                {
                    label: "Verificación Email",
                    value: customer.email_verified_at ? (
                        <Badge variant="outline" className="text-xs px-2 py-0.5 bg-green-50 text-green-700 border-green-200">
                            <Check className="h-3 w-3 mr-1" />
                            Verificado
                        </Badge>
                    ) : (
                        <Badge variant="outline" className="text-xs px-2 py-0.5 bg-red-50 text-red-700 border-red-200">
                            <X className="h-3 w-3 mr-1" />
                            No verificado
                        </Badge>
                    )
                },
                {
                    label: "Última Actividad",
                    value: (
                        <div className="flex items-center gap-1">
                            <Clock className="h-3 w-3 text-muted-foreground" />
                            <span>{formatDate(customer.last_activity)}</span>
                        </div>
                    ),
                    condition: !!customer.last_activity
                },
                {
                    label: "Última Compra",
                    value: (
                        <div>
                            {formatDate(customer.last_purchase)}
                            <span className="ml-2 text-xs text-muted-foreground">
                                ({daysSince(customer.last_purchase)} días)
                            </span>
                        </div>
                    ),
                    condition: !!customer.last_purchase
                }
            ]}
        />
    );

    return (
        <AppLayout>
            <Head title="Gestión de Clientes" />

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
                renderMobileCard={(customer) => <CustomerMobileCard customer={customer} />}
                routeName="/customers"
                breakpoint="md"
            />

            <DeleteConfirmationDialog
                isOpen={showDeleteDialog}
                onClose={closeDeleteDialog}
                onConfirm={handleDeleteCustomer}
                isDeleting={deletingCustomer !== null}
                entityName={customerToDelete?.full_name || ''}
                entityType="cliente"
            />
        </AppLayout>
    );
}