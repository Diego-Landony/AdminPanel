import { Head } from '@inertiajs/react';
import { Award, Check, Clock, CreditCard, Phone, Users, X } from 'lucide-react';
import React from 'react';

import { DataTable } from '@/components/DataTable';
import { EntityInfoCell } from '@/components/EntityInfoCell';
import { CustomersSkeleton } from '@/components/skeletons';
import { StandardMobileCard } from '@/components/StandardMobileCard';
import { CUSTOMER_TYPE_COLORS } from '@/components/status-badge';
import { TableActions } from '@/components/TableActions';
import { Badge } from '@/components/ui/badge';
import AppLayout from '@/layouts/app-layout';
import { calculateAge, daysSince, formatDate, formatPoints } from '@/utils/format';

/**
 * Interfaz para los datos del cliente
 */
interface Customer {
    id: number;
    name: string;
    email: string;
    subway_card: string;
    birth_date: string;
    gender: string | null;
    customer_type: {
        id: number;
        name: string;
        color: string | null;
        multiplier: number;
    } | null;
    phone: string | null;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
    last_activity: string | null;
    last_purchase: string | null;
    points: number;
    points_updated_at: string | null;
    is_online: boolean;
    status: string;
}

/**
 * Interfaz para las estadísticas de tipos de clientes
 */
interface CustomerTypeStat {
    id: number;
    name: string;
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
const getCustomerTypeIcon = (color: string, size: string = 'h-3 w-3'): React.ReactElement => {
    const colorClass = getCustomerTypeIconColor(color);
    return <Award className={`${size} ${colorClass}`} />;
};

/**
 * Obtiene el color del icono basado en el color del tipo de cliente
 */
const getCustomerTypeIconColor = (color: string): string => {
    switch (color) {
        case 'green':
            return 'text-green-600';
        case 'orange':
            return 'text-orange-600';
        case 'gray':
            return 'text-gray-600';
        case 'yellow':
            return 'text-yellow-600';
        case 'purple':
            return 'text-purple-600';
        case 'blue':
            return 'text-blue-600';
        case 'red':
            return 'text-red-600';
        default:
            return 'text-primary';
    }
};

/**
 * Obtiene el color del tipo de cliente basado en el nuevo sistema
 */
const getClientTypeColor = (customerType: Customer['customer_type']): string => {
    if (customerType && customerType.color && CUSTOMER_TYPE_COLORS[customerType.color]) {
        return CUSTOMER_TYPE_COLORS[customerType.color].color;
    }

    // Fallback por defecto si no hay customer_type o color
    return CUSTOMER_TYPE_COLORS.gray.color;
};

/**
 * Página principal de gestión de clientes
 * Refactorizada para usar DataTable unificado directamente
 */
export default function CustomersIndex({ customers, customer_type_stats, filters }: CustomersPageProps) {
    // Generar estadísticas dinámicas basadas en los tipos de cliente reales
    const stats = [
        {
            title: 'Total Clientes',
            value: customers.total,
            icon: <Users className="h-4 w-4 text-blue-600" />,
            description: `${customers.total} cliente${customers.total !== 1 ? 's' : ''} registrados`,
        },
        ...customer_type_stats.map((stat) => ({
            title: stat.name,
            value: stat.customer_count,
            icon: getCustomerTypeIcon(stat.color, 'h-4 w-4'),
            description: `${stat.customer_count} cliente${stat.customer_count !== 1 ? 's' : ''}`,
        })),
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
                        <Badge key="gender" variant="outline" className="border-indigo-200 bg-indigo-50 px-2 py-0.5 text-xs text-indigo-700">
                            {customer.gender}
                        </Badge>,
                    );
                }

                if (customer.birth_date) {
                    badges.push(
                        <Badge key="age" variant="outline" className="border-sky-200 bg-sky-50 px-2 py-0.5 text-xs text-sky-700">
                            {calculateAge(customer.birth_date)} años
                        </Badge>,
                    );
                }

                badges.push(
                    customer.email_verified_at ? (
                        <Badge key="verified" variant="outline" className="border-green-200 bg-green-50 px-2 py-0.5 text-xs text-green-700">
                            <Check className="mr-1 h-3 w-3" />
                            Verificado
                        </Badge>
                    ) : (
                        <Badge key="not-verified" variant="outline" className="border-red-200 bg-red-50 px-2 py-0.5 text-xs text-red-700">
                            <X className="mr-1 h-3 w-3" />
                            No verificado
                        </Badge>
                    ),
                );

                return <EntityInfoCell icon={Users} primaryText={customer.name} secondaryText={customer.email} badges={<>{badges}</>} />;
            },
        },
        {
            key: 'subway_card',
            title: 'SubwayCard',
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
                                <span className="flex items-center">{getCustomerTypeIcon(customer.customer_type.color || 'gray', 'h-3 w-3')}</span>
                            )}
                            <Badge className={getClientTypeColor(customer.customer_type)}>{customer.customer_type?.name || 'Sin tipo'}</Badge>
                            {customer.customer_type && <span className="text-xs text-muted-foreground">{customer.customer_type.multiplier}x</span>}
                        </div>
                    </div>
                </div>
            ),
        },
        {
            key: 'phone',
            title: 'Teléfono',
            width: 'md' as const,
            render: (customer: Customer) => <div className="text-sm">{customer.phone || 'N/A'}</div>,
        },
        {
            key: 'last_purchase',
            title: 'Última Compra',
            width: 'md' as const,
            sortable: true,
            render: (customer: Customer) => (
                <div>
                    <div className="text-sm">{customer.last_purchase ? formatDate(customer.last_purchase) : 'Sin compras'}</div>
                    {customer.last_purchase && <div className="text-xs text-muted-foreground">{daysSince(customer.last_purchase)} días</div>}
                </div>
            ),
        },
        {
            key: 'points',
            title: 'Puntos',
            width: 'sm' as const,
            sortable: true,
            render: (customer: Customer) => (
                <div>
                    <div className="text-sm font-medium text-blue-600">{formatPoints(customer.points || 0)}</div>
                    {customer.points_updated_at && (
                        <div className="text-xs text-muted-foreground">Actualizado: {formatDate(customer.points_updated_at)}</div>
                    )}
                </div>
            ),
        },
        {
            key: 'created_at',
            title: 'Registro',
            width: 'sm' as const,
            sortable: true,
            render: (customer: Customer) => <div className="text-sm text-muted-foreground">{formatDate(customer.created_at)}</div>,
        },
        {
            key: 'actions',
            title: 'Acciones',
            width: 'xs' as const,
            textAlign: 'right' as const,
            render: (customer: Customer) => <TableActions editHref={`/customers/${customer.id}/edit`} editTooltip="Editar cliente" />,
        },
    ];

    const CustomerMobileCard = ({ customer }: { customer: Customer }) => (
        <StandardMobileCard
            icon={Users}
            title={customer.name}
            subtitle={customer.email}
            actions={{
                editHref: `/customers/${customer.id}/edit`,
                editTooltip: 'Editar cliente',
            }}
            dataFields={[
                {
                    label: 'SubwayCard',
                    value: (
                        <div className="flex items-center gap-2">
                            <CreditCard className="h-3 w-3 text-muted-foreground" />
                            <code className="rounded bg-muted px-2 py-1 font-mono text-xs">{customer.subway_card}</code>
                        </div>
                    ),
                },
                {
                    label: 'Puntos',
                    value: <div className="font-medium text-blue-600">{formatPoints(customer.points || 0)}</div>,
                },
                {
                    label: 'Tipo de Cliente',
                    value: (
                        <div className="flex items-center gap-2">
                            {getCustomerTypeIcon(customer.customer_type?.color || 'gray', 'h-4 w-4')}
                            <Badge className={getClientTypeColor(customer.customer_type)}>{customer.customer_type?.name || 'Sin tipo'}</Badge>
                            {customer.customer_type && <span className="text-xs text-muted-foreground">{customer.customer_type.multiplier}x</span>}
                        </div>
                    ),
                    condition: !!customer.customer_type,
                },
                {
                    label: 'Teléfono',
                    value: (
                        <div className="flex items-center gap-2">
                            <Phone className="h-3 w-3 text-muted-foreground" />
                            <span>{customer.phone}</span>
                        </div>
                    ),
                    condition: !!customer.phone,
                },
                {
                    label: 'Verificación Email',
                    value: customer.email_verified_at ? (
                        <Badge variant="outline" className="border-green-200 bg-green-50 px-2 py-0.5 text-xs text-green-700">
                            <Check className="mr-1 h-3 w-3" />
                            Verificado
                        </Badge>
                    ) : (
                        <Badge variant="outline" className="border-red-200 bg-red-50 px-2 py-0.5 text-xs text-red-700">
                            <X className="mr-1 h-3 w-3" />
                            No verificado
                        </Badge>
                    ),
                },
                {
                    label: 'Última Actividad',
                    value: (
                        <div className="flex items-center gap-1">
                            <Clock className="h-3 w-3 text-muted-foreground" />
                            <span>{formatDate(customer.last_activity)}</span>
                        </div>
                    ),
                    condition: !!customer.last_activity,
                },
                {
                    label: 'Última Compra',
                    value: (
                        <div>
                            {formatDate(customer.last_purchase)}
                            {customer.last_purchase && (
                                <span className="ml-2 text-xs text-muted-foreground">({daysSince(customer.last_purchase)} días)</span>
                            )}
                        </div>
                    ),
                    condition: !!customer.last_purchase,
                },
            ]}
        />
    );

    return (
        <AppLayout>
            <Head title="Gestión de Clientes" />

            <DataTable
                title="Clientes"
                data={customers}
                columns={columns}
                stats={stats}
                filters={filters}
                createUrl="/customers/create"
                createLabel="Crear"
                searchPlaceholder="Buscar por nombre, email, tarjeta subway o teléfono..."
                loadingSkeleton={CustomersSkeleton}
                renderMobileCard={(customer) => <CustomerMobileCard customer={customer} />}
                routeName="/customers"
                breakpoint="lg"
            />
        </AppLayout>
    );
}
