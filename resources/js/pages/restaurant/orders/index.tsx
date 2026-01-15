import { Link, router } from '@inertiajs/react';
import {
    AlertCircle,
    CheckCircle,
    Clock,
    Eye,
    Package,
    Phone,
    ShoppingBag,
    Truck,
    User,
    XCircle,
} from 'lucide-react';
import { useState } from 'react';

import { PaginationWrapper } from '@/components/PaginationWrapper';
import { StatusBadge, StatusConfig } from '@/components/status-badge';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { CURRENCY } from '@/constants/ui-constants';
import RestaurantLayout from '@/layouts/restaurant-layout';
import { Filters, Order, PaginatedData } from '@/types';
import { formatCurrency } from '@/utils/format';

interface Props {
    orders: PaginatedData<Order>;
    status_counts: {
        pending: number;
        preparing: number;
        ready: number;
        out_for_delivery: number;
    };
    filters: Filters & {
        status: string | null;
        service_type: string | null;
    };
}

/**
 * Configuraciones de estado de ordenes
 */
const ORDER_STATUS_CONFIGS: Record<string, StatusConfig> = {
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
        text: 'Lista',
        icon: <CheckCircle className="h-3 w-3" />,
    },
    out_for_delivery: {
        color: 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-300 border border-orange-200 dark:border-orange-700',
        text: 'En Camino',
        icon: <Truck className="h-3 w-3" />,
    },
    delivered: {
        color: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300 border border-green-200 dark:border-green-700',
        text: 'Entregada',
        icon: <CheckCircle className="h-3 w-3" />,
    },
    completed: {
        color: 'bg-green-200 text-green-900 dark:bg-green-800 dark:text-green-200 border border-green-300 dark:border-green-600',
        text: 'Completada',
        icon: <CheckCircle className="h-3 w-3" />,
    },
    cancelled: {
        color: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300 border border-red-200 dark:border-red-700',
        text: 'Cancelada',
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
 * Helper para calcular tiempo relativo
 */
function timeAgo(dateString: string): string {
    const date = new Date(dateString);
    const now = new Date();
    const diffMs = now.getTime() - date.getTime();
    const diffMins = Math.floor(diffMs / 60000);

    if (diffMins < 1) return 'Ahora';
    if (diffMins < 60) return `Hace ${diffMins} min`;
    const diffHours = Math.floor(diffMins / 60);
    if (diffHours < 24) return `Hace ${diffHours}h`;
    return date.toLocaleDateString('es-GT');
}

/**
 * Tabs de filtro por estado
 */
const statusTabs = [
    { value: 'all', label: 'Todas', countKey: null },
    { value: 'pending', label: 'Pendientes', countKey: 'pending' },
    { value: 'preparing', label: 'Preparando', countKey: 'preparing' },
    { value: 'ready', label: 'Listas', countKey: 'ready' },
    { value: 'out_for_delivery', label: 'En Camino', countKey: 'out_for_delivery' },
] as const;

/**
 * Pagina de ordenes del restaurante
 */
export default function RestaurantOrdersIndex({
    orders,
    status_counts,
    filters,
}: Props) {
    const [isUpdating, setIsUpdating] = useState<number | null>(null);

    const handleFilterChange = (key: string, value: string | null) => {
        const params: Record<string, string | number | undefined> = {
            per_page: filters.per_page,
        };
        if (key === 'status' && value && value !== 'all') params.status = value;
        else if (filters.status && key !== 'status') params.status = filters.status;
        if (key === 'service_type' && value && value !== 'all') params.service_type = value;
        else if (filters.service_type && key !== 'service_type') params.service_type = filters.service_type;

        router.get('/restaurant/orders', params, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    };

    const handleAcceptOrder = (orderId: number) => {
        setIsUpdating(orderId);
        router.patch(
            `/restaurant/orders/${orderId}/accept`,
            {},
            {
                preserveState: true,
                preserveScroll: true,
                onFinish: () => setIsUpdating(null),
            },
        );
    };

    const handleMarkReady = (orderId: number) => {
        setIsUpdating(orderId);
        router.patch(
            `/restaurant/orders/${orderId}/ready`,
            {},
            {
                preserveState: true,
                preserveScroll: true,
                onFinish: () => setIsUpdating(null),
            },
        );
    };

    const currentStatus = filters.status || 'all';

    return (
        <RestaurantLayout title="Ordenes">
            <div className="flex flex-col gap-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-3">
                        <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10">
                            <Package className="h-5 w-5 text-primary" />
                        </div>
                        <div>
                            <h1 className="text-2xl font-bold tracking-tight lg:text-3xl">Ordenes</h1>
                            <p className="text-sm text-muted-foreground">Gestiona las ordenes de tu restaurante</p>
                        </div>
                    </div>
                </div>

                {/* Tabs de estado */}
                <div className="flex flex-wrap gap-2">
                    {statusTabs.map((tab) => {
                        const count = tab.countKey ? status_counts[tab.countKey] : null;
                        const isActive = currentStatus === tab.value;

                        return (
                            <Button
                                key={tab.value}
                                variant={isActive ? 'default' : 'outline'}
                                size="sm"
                                onClick={() => handleFilterChange('status', tab.value === 'all' ? null : tab.value)}
                                className="gap-2"
                            >
                                {tab.label}
                                {count !== null && count > 0 && (
                                    <Badge
                                        variant={isActive ? 'secondary' : 'outline'}
                                        className="ml-1 px-2 py-0 text-xs"
                                    >
                                        {count}
                                    </Badge>
                                )}
                            </Button>
                        );
                    })}
                </div>

                {/* Filtro de tipo de servicio */}
                <div className="flex items-center gap-4">
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
                </div>

                {/* Tabla de ordenes */}
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <Package className="h-5 w-5" />
                            Listado de Ordenes
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        {orders.data.length === 0 ? (
                            <div className="py-12 text-center">
                                <Package className="mx-auto h-12 w-12 text-muted-foreground/50" />
                                <p className="mt-4 text-muted-foreground">No hay ordenes</p>
                            </div>
                        ) : (
                            <>
                                {/* Desktop Table */}
                                <div className="hidden md:block">
                                    <div className="overflow-x-auto rounded-md border">
                                        <Table>
                                            <TableHeader>
                                                <TableRow>
                                                    <TableHead># Orden</TableHead>
                                                    <TableHead>Cliente</TableHead>
                                                    <TableHead>Tipo</TableHead>
                                                    <TableHead>Estado</TableHead>
                                                    <TableHead>Total</TableHead>
                                                    <TableHead>Tiempo</TableHead>
                                                    <TableHead className="text-right">Acciones</TableHead>
                                                </TableRow>
                                            </TableHeader>
                                            <TableBody>
                                                {orders.data.map((order) => (
                                                    <TableRow key={order.id}>
                                                        <TableCell>
                                                            <span className="font-medium">#{order.order_number}</span>
                                                        </TableCell>
                                                        <TableCell>
                                                            <div className="flex flex-col">
                                                                <div className="flex items-center gap-1">
                                                                    <User className="h-3 w-3 text-muted-foreground" />
                                                                    <span className="text-sm font-medium">
                                                                        {order.customer?.full_name || 'N/A'}
                                                                    </span>
                                                                </div>
                                                                {order.customer?.phone && (
                                                                    <div className="flex items-center gap-1 text-muted-foreground">
                                                                        <Phone className="h-3 w-3" />
                                                                        <span className="text-xs">{order.customer.phone}</span>
                                                                    </div>
                                                                )}
                                                            </div>
                                                        </TableCell>
                                                        <TableCell>
                                                            <StatusBadge
                                                                status={order.service_type}
                                                                configs={SERVICE_TYPE_CONFIGS}
                                                                className="text-xs"
                                                            />
                                                        </TableCell>
                                                        <TableCell>
                                                            <StatusBadge
                                                                status={order.status}
                                                                configs={ORDER_STATUS_CONFIGS}
                                                                className="text-xs"
                                                            />
                                                        </TableCell>
                                                        <TableCell>
                                                            <span className="font-medium">
                                                                {CURRENCY.symbol}
                                                                {formatCurrency(order.total, false)}
                                                            </span>
                                                        </TableCell>
                                                        <TableCell>
                                                            <span className="text-sm text-muted-foreground">
                                                                {timeAgo(order.created_at)}
                                                            </span>
                                                        </TableCell>
                                                        <TableCell>
                                                            <div className="flex items-center justify-end gap-2">
                                                                {order.status === 'pending' && (
                                                                    <Button
                                                                        variant="default"
                                                                        size="sm"
                                                                        onClick={() => handleAcceptOrder(order.id)}
                                                                        disabled={isUpdating === order.id}
                                                                        className="bg-green-600 hover:bg-green-700"
                                                                    >
                                                                        {isUpdating === order.id ? 'Aceptando...' : 'Aceptar'}
                                                                    </Button>
                                                                )}
                                                                {order.status === 'preparing' && (
                                                                    <Button
                                                                        variant="default"
                                                                        size="sm"
                                                                        onClick={() => handleMarkReady(order.id)}
                                                                        disabled={isUpdating === order.id}
                                                                    >
                                                                        {isUpdating === order.id ? 'Marcando...' : 'Marcar Lista'}
                                                                    </Button>
                                                                )}
                                                                <Link href={`/restaurant/orders/${order.id}`}>
                                                                    <Button variant="ghost" size="sm">
                                                                        <Eye className="h-4 w-4" />
                                                                    </Button>
                                                                </Link>
                                                            </div>
                                                        </TableCell>
                                                    </TableRow>
                                                ))}
                                            </TableBody>
                                        </Table>
                                    </div>
                                </div>

                                {/* Mobile Cards */}
                                <div className="grid gap-4 md:hidden">
                                    {orders.data.map((order) => (
                                        <Card key={order.id} className="overflow-hidden">
                                            <CardContent className="p-4">
                                                <div className="flex items-start justify-between">
                                                    <div className="space-y-1">
                                                        <div className="flex items-center gap-2">
                                                            <span className="font-bold">#{order.order_number}</span>
                                                            <StatusBadge
                                                                status={order.status}
                                                                configs={ORDER_STATUS_CONFIGS}
                                                                className="text-xs"
                                                            />
                                                        </div>
                                                        <div className="flex items-center gap-1 text-sm text-muted-foreground">
                                                            <User className="h-3 w-3" />
                                                            <span>{order.customer?.full_name || 'N/A'}</span>
                                                        </div>
                                                        {order.customer?.phone && (
                                                            <div className="flex items-center gap-1 text-sm text-muted-foreground">
                                                                <Phone className="h-3 w-3" />
                                                                <span>{order.customer.phone}</span>
                                                            </div>
                                                        )}
                                                    </div>
                                                    <div className="text-right">
                                                        <div className="font-bold">
                                                            {CURRENCY.symbol}
                                                            {formatCurrency(order.total, false)}
                                                        </div>
                                                        <div className="text-xs text-muted-foreground">
                                                            {timeAgo(order.created_at)}
                                                        </div>
                                                    </div>
                                                </div>

                                                <div className="mt-3 flex items-center justify-between border-t pt-3">
                                                    <StatusBadge
                                                        status={order.service_type}
                                                        configs={SERVICE_TYPE_CONFIGS}
                                                        className="text-xs"
                                                    />
                                                    <div className="flex gap-2">
                                                        {order.status === 'pending' && (
                                                            <Button
                                                                variant="default"
                                                                size="sm"
                                                                onClick={() => handleAcceptOrder(order.id)}
                                                                disabled={isUpdating === order.id}
                                                                className="bg-green-600 hover:bg-green-700"
                                                            >
                                                                Aceptar
                                                            </Button>
                                                        )}
                                                        {order.status === 'preparing' && (
                                                            <Button
                                                                variant="default"
                                                                size="sm"
                                                                onClick={() => handleMarkReady(order.id)}
                                                                disabled={isUpdating === order.id}
                                                            >
                                                                Lista
                                                            </Button>
                                                        )}
                                                        <Link href={`/restaurant/orders/${order.id}`}>
                                                            <Button variant="outline" size="sm">
                                                                <Eye className="h-4 w-4" />
                                                            </Button>
                                                        </Link>
                                                    </div>
                                                </div>
                                            </CardContent>
                                        </Card>
                                    ))}
                                </div>

                                {/* Paginacion */}
                                <PaginationWrapper
                                    data={orders}
                                    routeName="/restaurant/orders"
                                    filters={{
                                        per_page: filters.per_page,
                                        ...(filters.status && { status: filters.status }),
                                        ...(filters.service_type && { service_type: filters.service_type }),
                                    }}
                                    className="mt-6"
                                />
                            </>
                        )}
                    </CardContent>
                </Card>
            </div>
        </RestaurantLayout>
    );
}
