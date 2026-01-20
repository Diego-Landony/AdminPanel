import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { CURRENCY } from '@/constants/ui-constants';
import RestaurantLayout from '@/layouts/restaurant-layout';
import { Driver, RestaurantUser } from '@/types';
import { RestaurantDashboardStats } from '@/types/restaurant';
import { formatCurrency } from '@/utils/format';
import { Head, Link, router } from '@inertiajs/react';
import {
    Banknote,
    CheckCircle,
    ChefHat,
    Clock,
    CreditCard,
    Package,
    ShoppingBag,
} from 'lucide-react';
import { useState, useEffect } from 'react';
import { OrdersListTable, OrderListItem } from '@/components/restaurant/OrdersListTable';
import { useOrderPolling } from '@/hooks/useOrderPolling';
import {
    Sheet,
    SheetContent,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import { OrderDetailContent, OrderDetailData } from '@/components/restaurant/OrderDetailContent';

interface OrderItem {
    id: number;
    name: string;
    variant?: string | null;
    category?: string | null;
    quantity: number;
    unit_price: number;
    options_price?: number;
    total_price: number;
    notes?: string | null;
    options?: Array<{
        section_name: string;
        name: string;
        price: number;
    }>;
}

interface ActiveOrder {
    id: number;
    order_number: string;
    customer_name: string;
    customer_phone: string | null;
    customer_email?: string | null;
    customer_subway_card: string | null;
    status: string;
    service_type: 'pickup' | 'delivery';
    subtotal?: number;
    discount?: number;
    total: number;
    payment_method: string;
    payment_status?: string;
    notes?: string | null;
    delivery_address?: {
        label?: string;
        address_line_1?: string;
        address_line?: string;
        city?: string;
        reference?: string;
    } | null;
    items_count: number;
    items?: OrderItem[];
    driver: { id: number; name: string; phone?: string } | null;
    created_at: string;
    estimated_ready_at?: string | null;
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
    config: {
        polling_interval: number;
        auto_print_new_orders: boolean;
    };
}

/**
 * Dashboard del panel de restaurante - Estilo KDS
 */
export default function RestaurantDashboard({ restaurantAuth, stats, active_orders, available_drivers, config }: Props) {
    const userName = restaurantAuth.user.name.split(' ')[0];
    const [isUpdating, setIsUpdating] = useState<number | null>(null);
    const [, setTick] = useState(0);
    const [selectedOrder, setSelectedOrder] = useState<ActiveOrder | null>(null);
    const [sheetOpen, setSheetOpen] = useState(false);

    // Polling para detectar nuevas órdenes (actualización automática en tiempo real)
    useOrderPolling({
        intervalSeconds: config?.polling_interval || 15,
        autoPrint: config?.auto_print_new_orders ?? true,
        enabled: true,
        reloadProps: ['active_orders', 'stats'],
    });

    // Actualizar cada minuto para refrescar indicadores de urgencia
    useEffect(() => {
        const interval = setInterval(() => setTick((t) => t + 1), 60000);
        return () => clearInterval(interval);
    }, []);

    // Actualizar la orden seleccionada cuando cambian los datos
    useEffect(() => {
        if (selectedOrder) {
            const updatedOrder = active_orders.find(o => o.id === selectedOrder.id);
            if (updatedOrder) {
                setSelectedOrder(updatedOrder);
            } else {
                // La orden ya no está activa (fue completada/cancelada)
                setSheetOpen(false);
                setSelectedOrder(null);
            }
        }
    }, [active_orders]);

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
            },
        });
    };

    const handleViewOrder = (order: OrderListItem) => {
        const fullOrder = active_orders.find(o => o.id === order.id);
        if (fullOrder) {
            setSelectedOrder(fullOrder);
            setSheetOpen(true);
        }
    };

    const handlePrintOrder = (order: OrderListItem) => {
        // Abrir en nueva pestaña para imprimir
        window.open(`/restaurant/orders/${order.id}?print=1`, '_blank');
    };

    return (
        <RestaurantLayout title="Dashboard">
            <Head title="Dashboard - Restaurante" />

            <div className="flex flex-col gap-6">
                {/* Bienvenida */}
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-bold tracking-tight md:text-3xl">
                        Bienvenido, {userName}
                    </h1>
                    <div className="flex items-center gap-2 text-sm text-muted-foreground">
                        <span className="relative flex h-2 w-2">
                            <span className="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                            <span className="relative inline-flex rounded-full h-2 w-2 bg-green-500"></span>
                        </span>
                        <span className="hidden sm:inline text-green-600 dark:text-green-400 font-medium">
                            En vivo
                        </span>
                    </div>
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
                        <OrdersListTable
                            orders={active_orders}
                            availableDrivers={available_drivers}
                            onAccept={handleAcceptOrder}
                            onMarkReady={handleMarkReady}
                            onMarkCompleted={handleMarkCompleted}
                            onAssignDriver={handleAssignDriver}
                            onViewOrder={handleViewOrder}
                            onPrintOrder={handlePrintOrder}
                            isUpdating={isUpdating}
                        />
                    </CardContent>
                </Card>
            </div>

            {/* Order Detail Sheet */}
            <Sheet open={sheetOpen} onOpenChange={setSheetOpen}>
                <SheetContent className="w-full sm:max-w-lg overflow-y-auto">
                    <SheetHeader className="pb-4 border-b">
                        <SheetTitle className="text-xl font-bold">
                            Orden #{selectedOrder?.order_number}
                        </SheetTitle>
                    </SheetHeader>
                    {selectedOrder && (
                        <div className="py-4">
                            <OrderDetailContent
                                order={selectedOrder as OrderDetailData}
                                availableDrivers={available_drivers}
                                onAccept={(orderId) => {
                                    handleAcceptOrder(orderId);
                                }}
                                onMarkReady={(orderId) => {
                                    handleMarkReady(orderId);
                                }}
                                onMarkCompleted={(orderId) => {
                                    handleMarkCompleted(orderId);
                                }}
                                onAssignDriver={(orderId, driverId) => {
                                    handleAssignDriver(orderId, driverId);
                                }}
                                onPrint={() => {
                                    window.open(`/restaurant/orders/${selectedOrder.id}?print=1`, '_blank');
                                }}
                                isUpdating={isUpdating === selectedOrder.id}
                                variant="sheet"
                            />
                        </div>
                    )}
                </SheetContent>
            </Sheet>
        </RestaurantLayout>
    );
}
