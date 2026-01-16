import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Command, CommandEmpty, CommandGroup, CommandInput, CommandItem, CommandList } from '@/components/ui/command';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { CURRENCY } from '@/constants/ui-constants';
import RestaurantLayout from '@/layouts/restaurant-layout';
import { Driver, RestaurantUser } from '@/types';
import { RestaurantDashboardStats } from '@/types/restaurant';
import { formatCurrency } from '@/utils/format';
import { cn } from '@/lib/utils';
import { Head, Link, router } from '@inertiajs/react';
import {
    AlertCircle,
    Banknote,
    Check,
    CheckCircle,
    ChefHat,
    ChevronsUpDown,
    Clock,
    CreditCard,
    Eye,
    Package,
    ShoppingBag,
    Truck,
    Users,
    XCircle,
} from 'lucide-react';
import { useState, useEffect } from 'react';

interface ActiveOrder {
    id: number;
    order_number: string;
    customer_name: string;
    customer_phone: string | null;
    status: string;
    service_type: string;
    total: number;
    payment_method: string;
    items_count: number;
    driver: { id: number; name: string } | null;
    created_at: string;
}

interface Props {
    restaurantAuth: {
        user: RestaurantUser;
        restaurant: {
            id: number;
            name: string;
        };
    };
    stats: RestaurantDashboardStats;
    active_orders: ActiveOrder[];
    available_drivers: Driver[];
}

/**
 * Calcula los minutos transcurridos desde una fecha
 */
function getMinutesElapsed(dateString: string): number {
    const date = new Date(dateString);
    const now = new Date();
    return Math.floor((now.getTime() - date.getTime()) / 60000);
}

/**
 * Helper para calcular tiempo relativo
 */
function timeAgo(dateString: string): string {
    const diffMins = getMinutesElapsed(dateString);

    if (diffMins < 1) return 'Ahora';
    if (diffMins < 60) return `${diffMins} min`;
    const diffHours = Math.floor(diffMins / 60);
    if (diffHours < 24) return `${diffHours}h ${diffMins % 60}m`;
    return new Date(dateString).toLocaleDateString('es-GT');
}

/**
 * Obtiene la clase de color segun urgencia
 */
function getUrgencyColor(dateString: string, status: string): string {
    if (['completed', 'delivered', 'cancelled'].includes(status)) {
        return 'border-l-gray-400';
    }
    const mins = getMinutesElapsed(dateString);
    if (mins < 10) return 'border-l-green-500';
    if (mins < 20) return 'border-l-yellow-500';
    if (mins < 30) return 'border-l-orange-500';
    return 'border-l-red-500';
}

/**
 * Obtiene clase de fondo basada en urgencia
 */
function getUrgencyBgColor(dateString: string, status: string): string {
    if (['completed', 'delivered', 'cancelled'].includes(status)) {
        return 'bg-gray-50 dark:bg-gray-800/50';
    }
    const mins = getMinutesElapsed(dateString);
    if (mins < 10) return 'bg-white dark:bg-gray-900';
    if (mins < 20) return 'bg-yellow-50/50 dark:bg-yellow-900/10';
    if (mins < 30) return 'bg-orange-50/50 dark:bg-orange-900/10';
    return 'bg-red-50/50 dark:bg-red-900/10';
}

/**
 * Labels de estados
 */
const STATUS_LABELS: Record<string, { label: string; color: string; icon: React.ReactNode }> = {
    pending: {
        label: 'Pendiente',
        color: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
        icon: <Clock className="h-3 w-3" />,
    },
    preparing: {
        label: 'Preparando',
        color: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
        icon: <Package className="h-3 w-3" />,
    },
    ready: {
        label: 'Lista',
        color: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
        icon: <CheckCircle className="h-3 w-3" />,
    },
    out_for_delivery: {
        label: 'En Camino',
        color: 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-300',
        icon: <Truck className="h-3 w-3" />,
    },
    completed: {
        label: 'Completada',
        color: 'bg-green-200 text-green-900 dark:bg-green-800 dark:text-green-200',
        icon: <CheckCircle className="h-3 w-3" />,
    },
    cancelled: {
        label: 'Cancelada',
        color: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
        icon: <XCircle className="h-3 w-3" />,
    },
};

/**
 * Dashboard del panel de restaurante - Estilo KDS
 */
export default function RestaurantDashboard({ restaurantAuth, stats, active_orders, available_drivers }: Props) {
    const userName = restaurantAuth.user.name.split(' ')[0];
    const [isUpdating, setIsUpdating] = useState<number | null>(null);
    const [driverPopoverOpen, setDriverPopoverOpen] = useState<number | null>(null);
    const [, setTick] = useState(0);

    // Actualizar cada minuto para refrescar indicadores de urgencia
    useEffect(() => {
        const interval = setInterval(() => setTick((t) => t + 1), 60000);
        return () => clearInterval(interval);
    }, []);

    const handleAcceptOrder = (orderId: number) => {
        setIsUpdating(orderId);
        router.post(`/restaurant/orders/${orderId}/accept`, {}, {
            preserveState: true,
            preserveScroll: true,
            onFinish: () => setIsUpdating(null),
        });
    };

    const handleMarkReady = (orderId: number) => {
        setIsUpdating(orderId);
        router.post(`/restaurant/orders/${orderId}/ready`, {}, {
            preserveState: true,
            preserveScroll: true,
            onFinish: () => setIsUpdating(null),
        });
    };

    const handleMarkCompleted = (orderId: number) => {
        setIsUpdating(orderId);
        router.post(`/restaurant/orders/${orderId}/complete`, {}, {
            preserveState: true,
            preserveScroll: true,
            onFinish: () => setIsUpdating(null),
        });
    };

    const handleAssignDriver = (orderId: number, driverId: number) => {
        setIsUpdating(orderId);
        router.post(`/restaurant/orders/${orderId}/assign-driver`, { driver_id: driverId }, {
            preserveState: true,
            preserveScroll: true,
            onFinish: () => {
                setIsUpdating(null);
                setDriverPopoverOpen(null);
            },
        });
    };

    return (
        <RestaurantLayout title="Dashboard">
            <Head title="Dashboard - Restaurante" />

            <div className="flex flex-col gap-6">
                {/* Bienvenida */}
                <div className="flex flex-col gap-1">
                    <h1 className="text-2xl font-bold tracking-tight md:text-3xl">
                        Bienvenido, {userName}
                    </h1>
                    <p className="text-muted-foreground">
                        Resumen de hoy en {restaurantAuth.restaurant.name}
                    </p>
                </div>

                {/* Resumen del dia */}
                <div className="grid gap-4 lg:grid-cols-2">
                    {/* Estado de Ordenes */}
                    <Card>
                        <CardHeader className="pb-3">
                            <CardTitle className="text-base font-medium">Estado de Ordenes</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="grid grid-cols-4 gap-4">
                                <div className="text-center">
                                    <div className="mx-auto mb-2 flex h-10 w-10 items-center justify-center rounded-full bg-yellow-100 dark:bg-yellow-900/50">
                                        <Clock className="h-5 w-5 text-yellow-600 dark:text-yellow-400" />
                                    </div>
                                    <div className="text-2xl font-bold">{stats.pending_orders}</div>
                                    <div className="text-xs text-muted-foreground">Pendientes</div>
                                </div>
                                <div className="text-center">
                                    <div className="mx-auto mb-2 flex h-10 w-10 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-900/50">
                                        <ChefHat className="h-5 w-5 text-blue-600 dark:text-blue-400" />
                                    </div>
                                    <div className="text-2xl font-bold">{stats.preparing_orders}</div>
                                    <div className="text-xs text-muted-foreground">Preparando</div>
                                </div>
                                <div className="text-center">
                                    <div className="mx-auto mb-2 flex h-10 w-10 items-center justify-center rounded-full bg-green-100 dark:bg-green-900/50">
                                        <CheckCircle className="h-5 w-5 text-green-600 dark:text-green-400" />
                                    </div>
                                    <div className="text-2xl font-bold">{stats.ready_orders}</div>
                                    <div className="text-xs text-muted-foreground">Listas</div>
                                </div>
                                <div className="text-center">
                                    <div className="mx-auto mb-2 flex h-10 w-10 items-center justify-center rounded-full bg-muted">
                                        <Package className="h-5 w-5 text-muted-foreground" />
                                    </div>
                                    <div className="text-2xl font-bold">{stats.completed_today}</div>
                                    <div className="text-xs text-muted-foreground">Completadas</div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Ventas del Dia */}
                    <Card>
                        <CardHeader className="pb-3">
                            <CardTitle className="text-base font-medium">Ventas de Hoy</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="mb-4 text-center">
                                <div className="text-3xl font-bold text-primary">
                                    {CURRENCY.symbol}{formatCurrency(stats.total_sales_today, false)}
                                </div>
                                <div className="text-sm text-muted-foreground">Total en ventas</div>
                            </div>
                            <div className="grid grid-cols-2 gap-4 border-t pt-4">
                                <div className="flex items-center gap-3">
                                    <div className="flex h-9 w-9 items-center justify-center rounded-full bg-emerald-100 dark:bg-emerald-900/50">
                                        <Banknote className="h-4 w-4 text-emerald-600 dark:text-emerald-400" />
                                    </div>
                                    <div>
                                        <div className="font-semibold">
                                            {CURRENCY.symbol}{formatCurrency(stats.cash_sales_today, false)}
                                        </div>
                                        <div className="text-xs text-muted-foreground">Efectivo</div>
                                    </div>
                                </div>
                                <div className="flex items-center gap-3">
                                    <div className="flex h-9 w-9 items-center justify-center rounded-full bg-violet-100 dark:bg-violet-900/50">
                                        <CreditCard className="h-4 w-4 text-violet-600 dark:text-violet-400" />
                                    </div>
                                    <div>
                                        <div className="font-semibold">
                                            {CURRENCY.symbol}{formatCurrency(stats.card_sales_today, false)}
                                        </div>
                                        <div className="text-xs text-muted-foreground">Tarjeta</div>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {/* Ordenes Activas - KDS Style */}
                <Card>
                    <CardHeader>
                        <div className="flex items-center justify-between">
                            <div>
                                <CardTitle className="flex items-center gap-2">
                                    <ShoppingBag className="h-5 w-5" />
                                    Ordenes Activas
                                </CardTitle>
                                <CardDescription>
                                    Ordenes pendientes, en preparacion o listas
                                </CardDescription>
                            </div>
                            <Link
                                href="/restaurant/orders"
                                className="text-sm font-medium text-primary hover:underline"
                            >
                                Ver todas
                            </Link>
                        </div>
                    </CardHeader>
                    <CardContent>
                        {active_orders.length === 0 ? (
                            <div className="flex flex-col items-center justify-center py-12 text-center">
                                <ShoppingBag className="h-12 w-12 text-muted-foreground/50" />
                                <p className="mt-4 text-muted-foreground">
                                    No hay ordenes activas
                                </p>
                                <p className="text-sm text-muted-foreground">
                                    Las nuevas ordenes apareceran aqui
                                </p>
                            </div>
                        ) : (
                            <div className="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                                {active_orders.map((order) => {
                                    const statusConfig = STATUS_LABELS[order.status] || {
                                        label: 'Desconocido',
                                        color: 'bg-gray-100 text-gray-800',
                                        icon: <AlertCircle className="h-3 w-3" />,
                                    };
                                    const canAccept = order.status === 'pending';
                                    const canMarkReady = order.status === 'preparing';
                                    const canAssignDriver = order.status === 'ready' && order.service_type === 'delivery' && !order.driver;
                                    const canMarkCompleted = order.status === 'ready' && order.service_type === 'pickup';

                                    return (
                                        <div
                                            key={order.id}
                                            className={cn(
                                                'relative flex flex-col rounded-lg border-l-4 shadow-sm transition-all hover:shadow-md',
                                                getUrgencyColor(order.created_at, order.status),
                                                getUrgencyBgColor(order.created_at, order.status),
                                                'border border-gray-200 dark:border-gray-700'
                                            )}
                                        >
                                            {/* Header */}
                                            <div className="flex items-start justify-between p-3 pb-2">
                                                <div className="flex flex-col gap-1">
                                                    <span className="text-lg font-bold">#{order.order_number}</span>
                                                    <span className="text-sm text-muted-foreground line-clamp-1">
                                                        {order.customer_name}
                                                    </span>
                                                </div>
                                                <div className="flex flex-col items-end gap-1">
                                                    <Badge className={cn('flex items-center gap-1 text-xs', statusConfig.color)}>
                                                        {statusConfig.icon}
                                                        {statusConfig.label}
                                                    </Badge>
                                                    <div className="flex items-center gap-1 text-xs text-muted-foreground">
                                                        <Clock className="h-3 w-3" />
                                                        {timeAgo(order.created_at)}
                                                    </div>
                                                </div>
                                            </div>

                                            {/* Info */}
                                            <div className="flex items-center justify-between border-t border-dashed px-3 py-2">
                                                <div className="flex items-center gap-2">
                                                    {order.service_type === 'delivery' ? (
                                                        <Badge variant="outline" className="gap-1 text-xs">
                                                            <Truck className="h-3 w-3" />
                                                            Delivery
                                                        </Badge>
                                                    ) : (
                                                        <Badge variant="outline" className="gap-1 text-xs">
                                                            <ShoppingBag className="h-3 w-3" />
                                                            Pickup
                                                        </Badge>
                                                    )}
                                                </div>
                                                <span className="text-base font-semibold">
                                                    {CURRENCY.symbol}{formatCurrency(order.total, false)}
                                                </span>
                                            </div>

                                            {/* Asignacion de motorista inline */}
                                            {canAssignDriver && (
                                                <div className="border-t px-3 py-2">
                                                    <Popover
                                                        open={driverPopoverOpen === order.id}
                                                        onOpenChange={(open) => setDriverPopoverOpen(open ? order.id : null)}
                                                    >
                                                        <PopoverTrigger asChild>
                                                            <Button
                                                                variant="outline"
                                                                size="sm"
                                                                className="w-full justify-between text-xs"
                                                                disabled={isUpdating === order.id}
                                                            >
                                                                <span className="flex items-center gap-1">
                                                                    <Users className="h-3 w-3" />
                                                                    Asignar motorista
                                                                </span>
                                                                <ChevronsUpDown className="h-3 w-3 opacity-50" />
                                                            </Button>
                                                        </PopoverTrigger>
                                                        <PopoverContent className="w-[200px] p-0" align="start">
                                                            <Command>
                                                                <CommandInput placeholder="Buscar..." className="h-8 text-sm" />
                                                                <CommandList>
                                                                    <CommandEmpty>Sin motoristas</CommandEmpty>
                                                                    <CommandGroup>
                                                                        {available_drivers.map((driver) => (
                                                                            <CommandItem
                                                                                key={driver.id}
                                                                                value={driver.name}
                                                                                onSelect={() => handleAssignDriver(order.id, driver.id)}
                                                                                className="text-sm"
                                                                            >
                                                                                <Check className="mr-2 h-3 w-3 opacity-0" />
                                                                                {driver.name}
                                                                            </CommandItem>
                                                                        ))}
                                                                    </CommandGroup>
                                                                </CommandList>
                                                            </Command>
                                                        </PopoverContent>
                                                    </Popover>
                                                </div>
                                            )}

                                            {/* Motorista asignado */}
                                            {order.driver && order.service_type === 'delivery' && (
                                                <div className="flex items-center gap-1 border-t px-3 py-2 text-xs text-muted-foreground">
                                                    <Users className="h-3 w-3" />
                                                    <span>{order.driver.name}</span>
                                                </div>
                                            )}

                                            {/* Acciones */}
                                            <div className="mt-auto flex items-center gap-1 border-t p-2">
                                                {canAccept && (
                                                    <Button
                                                        size="sm"
                                                        onClick={() => handleAcceptOrder(order.id)}
                                                        disabled={isUpdating === order.id}
                                                        className="flex-1 bg-green-600 text-xs hover:bg-green-700"
                                                    >
                                                        {isUpdating === order.id ? '...' : 'Aceptar'}
                                                    </Button>
                                                )}
                                                {canMarkReady && (
                                                    <Button
                                                        size="sm"
                                                        onClick={() => handleMarkReady(order.id)}
                                                        disabled={isUpdating === order.id}
                                                        className="flex-1 text-xs"
                                                    >
                                                        {isUpdating === order.id ? '...' : 'Lista'}
                                                    </Button>
                                                )}
                                                {canMarkCompleted && (
                                                    <Button
                                                        size="sm"
                                                        onClick={() => handleMarkCompleted(order.id)}
                                                        disabled={isUpdating === order.id}
                                                        className="flex-1 bg-green-600 text-xs hover:bg-green-700"
                                                    >
                                                        {isUpdating === order.id ? '...' : 'Completar'}
                                                    </Button>
                                                )}
                                                <Link href={`/restaurant/orders/${order.id}`}>
                                                    <Button variant="outline" size="sm" className="px-2">
                                                        <Eye className="h-4 w-4" />
                                                    </Button>
                                                </Link>
                                            </div>
                                        </div>
                                    );
                                })}
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </RestaurantLayout>
    );
}
