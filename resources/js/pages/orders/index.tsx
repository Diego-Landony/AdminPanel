import { Head, Link, router } from '@inertiajs/react';
import {
    AlertCircle,
    Calendar,
    CheckCircle,
    Clock,
    Eye,
    Package,
    ShoppingBag,
    Truck,
    UserPlus,
    Users,
    XCircle,
} from 'lucide-react';
import { useState } from 'react';

import { DataTable } from '@/components/DataTable';
import { EntityInfoCell } from '@/components/EntityInfoCell';
import { StandardMobileCard } from '@/components/StandardMobileCard';
import { StatusBadge, StatusConfig } from '@/components/status-badge';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { DatePicker } from '@/components/ui/date-picker';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { CURRENCY } from '@/constants/ui-constants';
import AppLayout from '@/layouts/app-layout';
import { Driver, Filters, Order, PaginatedData, Restaurant } from '@/types';
import { formatCurrency, formatDate } from '@/utils/format';

interface OrdersPageProps {
    orders: PaginatedData<Order>;
    restaurants: Restaurant[];
    drivers: Driver[];
    total_orders: number;
    pending_orders: number;
    preparing_orders: number;
    out_for_delivery_orders: number;
    completed_today: number;
    filters: Filters & {
        restaurant_id?: string | null;
        status?: string | null;
        service_type?: string | null;
        driver_id?: string | null;
        date_from?: string | null;
        date_to?: string | null;
    };
}

/**
 * Configuraciones de estado de ordenes
 */
export const ORDER_STATUS_CONFIGS: Record<string, StatusConfig> = {
    pending: {
        color: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300 border border-yellow-200 dark:border-yellow-700',
        text: 'Pendiente',
        icon: <Clock className="h-3 w-3" />,
    },
    confirmed: {
        color: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300 border border-blue-200 dark:border-blue-700',
        text: 'Confirmado',
        icon: <CheckCircle className="h-3 w-3" />,
    },
    preparing: {
        color: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300 border border-blue-200 dark:border-blue-700',
        text: 'Preparando',
        icon: <Package className="h-3 w-3" />,
    },
    ready: {
        color: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300 border border-green-200 dark:border-green-700',
        text: 'Listo',
        icon: <CheckCircle className="h-3 w-3" />,
    },
    out_for_delivery: {
        color: 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-300 border border-orange-200 dark:border-orange-700',
        text: 'En Camino',
        icon: <Truck className="h-3 w-3" />,
    },
    delivered: {
        color: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300 border border-green-200 dark:border-green-700',
        text: 'Entregado',
        icon: <CheckCircle className="h-3 w-3" />,
    },
    completed: {
        color: 'bg-green-200 text-green-900 dark:bg-green-800 dark:text-green-200 border border-green-300 dark:border-green-600',
        text: 'Completado',
        icon: <CheckCircle className="h-3 w-3" />,
    },
    cancelled: {
        color: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300 border border-red-200 dark:border-red-700',
        text: 'Cancelado',
        icon: <XCircle className="h-3 w-3" />,
    },
    default: {
        color: 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300 border border-gray-200 dark:border-gray-600',
        text: 'Desconocido',
        icon: <AlertCircle className="h-3 w-3" />,
    },
};

/**
 * Configuraciones de tipo de servicio
 */
const SERVICE_TYPE_CONFIGS: Record<string, StatusConfig> = {
    delivery: {
        color: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300 border border-blue-200 dark:border-blue-700',
        text: 'Delivery',
        icon: <Truck className="h-3 w-3" />,
    },
    pickup: {
        color: 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-300 border border-orange-200 dark:border-orange-700',
        text: 'Pickup',
        icon: <ShoppingBag className="h-3 w-3" />,
    },
    default: {
        color: 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300 border border-gray-200 dark:border-gray-600',
        text: 'Desconocido',
        icon: <AlertCircle className="h-3 w-3" />,
    },
};

/**
 * Pagina principal de gestion de ordenes
 */
export default function OrdersIndex({
    orders,
    restaurants,
    drivers,
    total_orders,
    pending_orders,
    preparing_orders,
    out_for_delivery_orders,
    completed_today,
    filters,
}: OrdersPageProps) {
    const [assignModalOpen, setAssignModalOpen] = useState(false);
    const [selectedOrder, setSelectedOrder] = useState<Order | null>(null);
    const [selectedDriverId, setSelectedDriverId] = useState<string>('');

    const handleFilterChange = (key: string, value: string | null) => {
        const params: Record<string, string | number | undefined> = {
            per_page: filters.per_page,
        };
        if (filters.search) params.search = filters.search;
        if (key === 'restaurant_id' && value) params.restaurant_id = value;
        else if (filters.restaurant_id) params.restaurant_id = filters.restaurant_id;
        if (key === 'status' && value) params.status = value;
        else if (filters.status) params.status = filters.status;
        if (key === 'service_type' && value) params.service_type = value;
        else if (filters.service_type) params.service_type = filters.service_type;
        if (key === 'driver_id' && value) params.driver_id = value;
        else if (filters.driver_id) params.driver_id = filters.driver_id;
        if (key === 'date_from' && value) params.date_from = value;
        else if (filters.date_from) params.date_from = filters.date_from;
        if (key === 'date_to' && value) params.date_to = value;
        else if (filters.date_to) params.date_to = filters.date_to;

        router.get('/orders', params, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    };

    const openAssignModal = (order: Order) => {
        setSelectedOrder(order);
        setSelectedDriverId('');
        setAssignModalOpen(true);
    };

    const handleAssignDriver = () => {
        if (!selectedOrder || !selectedDriverId) return;

        router.patch(
            `/orders/${selectedOrder.id}/assign-driver`,
            { driver_id: selectedDriverId },
            {
                preserveState: true,
                preserveScroll: true,
                onSuccess: () => {
                    setAssignModalOpen(false);
                    setSelectedOrder(null);
                    setSelectedDriverId('');
                },
            },
        );
    };

    const availableDrivers = drivers.filter((driver) => driver.is_active && driver.is_available);

    const stats = [
        {
            title: 'total',
            value: total_orders,
            icon: <Package className="h-4 w-4" />,
        },
        {
            title: 'pendientes',
            value: pending_orders,
            icon: <Clock className="h-4 w-4 text-yellow-600" />,
        },
        {
            title: 'preparando',
            value: preparing_orders,
            icon: <Package className="h-4 w-4 text-blue-600" />,
        },
        {
            title: 'en camino',
            value: out_for_delivery_orders,
            icon: <Truck className="h-4 w-4 text-orange-600" />,
        },
        {
            title: 'completados hoy',
            value: completed_today,
            icon: <CheckCircle className="h-4 w-4 text-green-600" />,
        },
    ];

    const columns = [
        {
            key: 'order',
            title: '# Orden',
            width: 'md' as const,
            sortable: true,
            render: (order: Order) => (
                <EntityInfoCell
                    icon={Package}
                    primaryText={`#${order.order_number}`}
                    secondaryText={formatDate(order.created_at)}
                />
            ),
        },
        {
            key: 'customer',
            title: 'Cliente',
            width: 'md' as const,
            render: (order: Order) => (
                <div className="text-sm">
                    <p className="font-medium">{order.customer?.full_name || 'N/A'}</p>
                    <p className="text-muted-foreground">{order.customer?.phone || ''}</p>
                </div>
            ),
        },
        {
            key: 'restaurant',
            title: 'Restaurante',
            width: 'md' as const,
            render: (order: Order) => <div className="text-sm">{order.restaurant?.name || 'N/A'}</div>,
        },
        {
            key: 'service_type',
            title: 'Tipo',
            width: 'sm' as const,
            render: (order: Order) => (
                <StatusBadge status={order.service_type} configs={SERVICE_TYPE_CONFIGS} className="text-xs" />
            ),
        },
        {
            key: 'status',
            title: 'Estado',
            width: 'sm' as const,
            sortable: true,
            render: (order: Order) => <StatusBadge status={order.status} configs={ORDER_STATUS_CONFIGS} className="text-xs" />,
        },
        {
            key: 'total',
            title: 'Total',
            width: 'sm' as const,
            sortable: true,
            render: (order: Order) => (
                <div className="text-sm font-medium">
                    {CURRENCY.symbol}
                    {formatCurrency(order.total, false)}
                </div>
            ),
        },
        {
            key: 'driver',
            title: 'Motorista',
            width: 'md' as const,
            render: (order: Order) => (
                <div className="text-sm">
                    {order.driver ? (
                        <div className="flex items-center gap-2">
                            <Users className="h-4 w-4 text-muted-foreground" />
                            <span>{order.driver.name}</span>
                        </div>
                    ) : order.service_type === 'delivery' && order.status === 'ready' ? (
                        <Button variant="outline" size="sm" className="h-7 text-xs" onClick={() => openAssignModal(order)}>
                            <UserPlus className="mr-1 h-3 w-3" />
                            Asignar
                        </Button>
                    ) : (
                        <span className="text-muted-foreground">-</span>
                    )}
                </div>
            ),
        },
        {
            key: 'actions',
            title: 'Acciones',
            width: 'xs' as const,
            align: 'right' as const,
            render: (order: Order) => (
                <Link href={`/orders/${order.id}`}>
                    <Button variant="ghost" size="sm">
                        <Eye className="h-4 w-4" />
                    </Button>
                </Link>
            ),
        },
    ];

    const OrderMobileCard = ({ order }: { order: Order }) => (
        <StandardMobileCard
            icon={Package}
            title={`#${order.order_number}`}
            subtitle={order.customer?.full_name || 'N/A'}
            badge={{
                children: <StatusBadge status={order.status} configs={ORDER_STATUS_CONFIGS} showIcon={false} className="text-xs" />,
            }}
            actions={{
                viewHref: `/orders/${order.id}`,
                viewTooltip: 'Ver detalle',
            }}
            dataFields={[
                {
                    label: 'Restaurante',
                    value: order.restaurant?.name || 'N/A',
                },
                {
                    label: 'Tipo',
                    value: <StatusBadge status={order.service_type} configs={SERVICE_TYPE_CONFIGS} className="text-xs" />,
                },
                {
                    label: 'Total',
                    value: `${CURRENCY.symbol}${formatCurrency(order.total, false)}`,
                },
                {
                    label: 'Motorista',
                    value: order.driver?.name || 'Sin asignar',
                    condition: order.service_type === 'delivery',
                },
                {
                    label: 'Fecha',
                    value: formatDate(order.created_at),
                },
            ]}
        />
    );

    return (
        <AppLayout>
            <Head title="Gestion de Ordenes" />

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
                            <SelectItem value="pending">Pendiente</SelectItem>
                            <SelectItem value="confirmed">Confirmado</SelectItem>
                            <SelectItem value="preparing">Preparando</SelectItem>
                            <SelectItem value="ready">Listo</SelectItem>
                            <SelectItem value="out_for_delivery">En Camino</SelectItem>
                            <SelectItem value="delivered">Entregado</SelectItem>
                            <SelectItem value="completed">Completado</SelectItem>
                            <SelectItem value="cancelled">Cancelado</SelectItem>
                        </SelectContent>
                    </Select>

                    <Select
                        value={filters.service_type || 'all'}
                        onValueChange={(value) => handleFilterChange('service_type', value === 'all' ? null : value)}
                    >
                        <SelectTrigger className="w-[150px]">
                            <SelectValue placeholder="Tipo" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">Todos</SelectItem>
                            <SelectItem value="delivery">Delivery</SelectItem>
                            <SelectItem value="pickup">Pickup</SelectItem>
                        </SelectContent>
                    </Select>

                    <Select
                        value={filters.driver_id || 'all'}
                        onValueChange={(value) => handleFilterChange('driver_id', value === 'all' ? null : value)}
                    >
                        <SelectTrigger className="w-[180px]">
                            <SelectValue placeholder="Motorista" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">Todos los motoristas</SelectItem>
                            {drivers.map((driver) => (
                                <SelectItem key={driver.id} value={driver.id.toString()}>
                                    {driver.name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>

                    <div className="flex items-center gap-2">
                        <Label className="text-sm text-muted-foreground">Desde:</Label>
                        <DatePicker
                            date={filters.date_from ? new Date(filters.date_from) : undefined}
                            onSelect={(date) => handleFilterChange('date_from', date ? date.toISOString().split('T')[0] : null)}
                        />
                    </div>

                    <div className="flex items-center gap-2">
                        <Label className="text-sm text-muted-foreground">Hasta:</Label>
                        <DatePicker
                            date={filters.date_to ? new Date(filters.date_to) : undefined}
                            onSelect={(date) => handleFilterChange('date_to', date ? date.toISOString().split('T')[0] : null)}
                        />
                    </div>
                </div>

                <DataTable
                    title="Ordenes"
                    data={orders}
                    columns={columns}
                    stats={stats}
                    filters={filters}
                    searchPlaceholder="Buscar por numero de orden, cliente o restaurante..."
                    renderMobileCard={(order) => <OrderMobileCard order={order} />}
                    routeName="/orders"
                    breakpoint="lg"
                />

                {/* Modal de asignar motorista */}
                <Dialog open={assignModalOpen} onOpenChange={setAssignModalOpen}>
                    <DialogContent className="sm:max-w-md">
                        <DialogHeader>
                            <DialogTitle>Asignar Motorista</DialogTitle>
                            <DialogDescription>
                                Selecciona un motorista disponible para la orden #{selectedOrder?.order_number}
                            </DialogDescription>
                        </DialogHeader>

                        <div className="space-y-4">
                            {availableDrivers.length > 0 ? (
                                <>
                                    <Select value={selectedDriverId} onValueChange={setSelectedDriverId}>
                                        <SelectTrigger>
                                            <SelectValue placeholder="Seleccionar motorista" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {availableDrivers.map((driver) => (
                                                <SelectItem key={driver.id} value={driver.id.toString()}>
                                                    <div className="flex items-center gap-2">
                                                        <Users className="h-4 w-4" />
                                                        <span>{driver.name}</span>
                                                        {driver.active_orders_count !== undefined && driver.active_orders_count > 0 && (
                                                            <Badge variant="secondary" className="text-xs">
                                                                {driver.active_orders_count} orden(es)
                                                            </Badge>
                                                        )}
                                                    </div>
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>

                                    <div className="flex justify-end gap-2">
                                        <Button variant="outline" onClick={() => setAssignModalOpen(false)}>
                                            Cancelar
                                        </Button>
                                        <Button onClick={handleAssignDriver} disabled={!selectedDriverId}>
                                            Asignar
                                        </Button>
                                    </div>
                                </>
                            ) : (
                                <div className="py-4 text-center">
                                    <p className="text-muted-foreground">No hay motoristas disponibles en este momento.</p>
                                </div>
                            )}
                        </div>
                    </DialogContent>
                </Dialog>
            </div>
        </AppLayout>
    );
}
