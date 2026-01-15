import { StatusBadge, StatusConfig } from '@/components/status-badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { CURRENCY } from '@/constants/ui-constants';
import RestaurantLayout from '@/layouts/restaurant-layout';
import { Order, RestaurantUser } from '@/types';
import { RestaurantDashboardStats } from '@/types/restaurant';
import { formatCurrency } from '@/utils/format';
import { Head, Link } from '@inertiajs/react';
import {
    AlertCircle,
    CheckCircle,
    ChefHat,
    Clock,
    Eye,
    Package,
    ShoppingBag,
    Truck,
    User,
    XCircle,
} from 'lucide-react';

interface Props {
    restaurantAuth: {
        user: RestaurantUser;
        restaurant: {
            id: number;
            name: string;
        };
    };
    stats: RestaurantDashboardStats;
    recent_orders: Order[];
}

/**
 * Configuraciones de estado de ordenes para el dashboard
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
 * Dashboard del panel de restaurante
 * Muestra estadisticas y ordenes recientes
 */
export default function RestaurantDashboard({ restaurantAuth, stats, recent_orders }: Props) {
    const userName = restaurantAuth.user.name.split(' ')[0];

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
                        Aqui tienes un resumen de las ordenes de hoy en {restaurantAuth.restaurant.name}
                    </p>
                </div>

                {/* Cards de estadisticas */}
                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    {/* Ordenes Pendientes */}
                    <Card className="border-l-4 border-l-yellow-500">
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Pendientes</CardTitle>
                            <div className="flex h-8 w-8 items-center justify-center rounded-full bg-yellow-100 dark:bg-yellow-900">
                                <Clock className="h-4 w-4 text-yellow-600 dark:text-yellow-400" />
                            </div>
                        </CardHeader>
                        <CardContent>
                            <div className="text-3xl font-bold text-yellow-600 dark:text-yellow-400">
                                {stats.pending_orders}
                            </div>
                            <p className="text-xs text-muted-foreground">
                                Esperando confirmacion
                            </p>
                        </CardContent>
                    </Card>

                    {/* Ordenes en Preparacion */}
                    <Card className="border-l-4 border-l-blue-500">
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Preparando</CardTitle>
                            <div className="flex h-8 w-8 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-900">
                                <ChefHat className="h-4 w-4 text-blue-600 dark:text-blue-400" />
                            </div>
                        </CardHeader>
                        <CardContent>
                            <div className="text-3xl font-bold text-blue-600 dark:text-blue-400">
                                {stats.preparing_orders}
                            </div>
                            <p className="text-xs text-muted-foreground">
                                En cocina actualmente
                            </p>
                        </CardContent>
                    </Card>

                    {/* Ordenes Listas */}
                    <Card className="border-l-4 border-l-green-500">
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Listas</CardTitle>
                            <div className="flex h-8 w-8 items-center justify-center rounded-full bg-green-100 dark:bg-green-900">
                                <CheckCircle className="h-4 w-4 text-green-600 dark:text-green-400" />
                            </div>
                        </CardHeader>
                        <CardContent>
                            <div className="text-3xl font-bold text-green-600 dark:text-green-400">
                                {stats.ready_orders}
                            </div>
                            <p className="text-xs text-muted-foreground">
                                Para entrega o recoleccion
                            </p>
                        </CardContent>
                    </Card>

                    {/* Ordenes Completadas Hoy */}
                    <Card className="border-l-4 border-l-gray-500">
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Completadas Hoy</CardTitle>
                            <div className="flex h-8 w-8 items-center justify-center rounded-full bg-gray-100 dark:bg-gray-800">
                                <Package className="h-4 w-4 text-gray-600 dark:text-gray-400" />
                            </div>
                        </CardHeader>
                        <CardContent>
                            <div className="text-3xl font-bold">
                                {stats.completed_today}
                            </div>
                            <p className="text-xs text-muted-foreground">
                                De {stats.total_today} ordenes totales
                            </p>
                        </CardContent>
                    </Card>
                </div>

                {/* Ordenes Recientes */}
                <Card>
                    <CardHeader>
                        <div className="flex items-center justify-between">
                            <div>
                                <CardTitle className="flex items-center gap-2">
                                    <ShoppingBag className="h-5 w-5" />
                                    Ordenes Recientes
                                </CardTitle>
                                <CardDescription>
                                    Ultimas 5 ordenes recibidas
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
                        {recent_orders.length === 0 ? (
                            <div className="flex flex-col items-center justify-center py-12 text-center">
                                <ShoppingBag className="h-12 w-12 text-muted-foreground/50" />
                                <p className="mt-4 text-muted-foreground">
                                    No hay ordenes recientes
                                </p>
                                <p className="text-sm text-muted-foreground">
                                    Las nuevas ordenes apareceran aqui
                                </p>
                            </div>
                        ) : (
                            <div className="space-y-4">
                                {recent_orders.map((order) => (
                                    <div
                                        key={order.id}
                                        className="flex items-center justify-between rounded-lg border p-4 transition-colors hover:bg-muted/50"
                                    >
                                        <div className="flex items-center gap-4">
                                            <div className="flex h-10 w-10 items-center justify-center rounded-full bg-muted">
                                                <Package className="h-5 w-5 text-muted-foreground" />
                                            </div>
                                            <div className="space-y-1">
                                                <div className="flex items-center gap-2">
                                                    <span className="font-medium">
                                                        #{order.order_number}
                                                    </span>
                                                    <StatusBadge
                                                        status={order.status}
                                                        configs={ORDER_STATUS_CONFIGS}
                                                        className="text-xs"
                                                    />
                                                    <StatusBadge
                                                        status={order.service_type}
                                                        configs={SERVICE_TYPE_CONFIGS}
                                                        className="text-xs"
                                                    />
                                                </div>
                                                <div className="flex items-center gap-2 text-sm text-muted-foreground">
                                                    <User className="h-3 w-3" />
                                                    <span>{order.customer?.full_name || 'Cliente'}</span>
                                                    <span className="text-muted-foreground/50">|</span>
                                                    <span>{timeAgo(order.created_at)}</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div className="flex items-center gap-4">
                                            <div className="text-right">
                                                <div className="font-semibold">
                                                    {CURRENCY.symbol}{formatCurrency(order.total, false)}
                                                </div>
                                                <div className="text-xs text-muted-foreground">
                                                    {order.items?.length || 0} items
                                                </div>
                                            </div>
                                            <Link
                                                href={`/restaurant/orders/${order.id}`}
                                                className="flex h-8 w-8 items-center justify-center rounded-lg text-muted-foreground transition-colors hover:bg-muted hover:text-foreground"
                                            >
                                                <Eye className="h-4 w-4" />
                                            </Link>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </RestaurantLayout>
    );
}
