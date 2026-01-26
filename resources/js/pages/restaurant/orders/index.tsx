import { router } from '@inertiajs/react';
import { format } from 'date-fns';
import { es } from 'date-fns/locale';
import {
    CalendarIcon,
    Package,
} from 'lucide-react';
import { useState, useEffect } from 'react';

import { printOrder } from '@/components/orders/PrintComanda';
import { PaginationWrapper } from '@/components/PaginationWrapper';
import { OrderDetailContent, OrderDetailData } from '@/components/restaurant/OrderDetailContent';
import { OrdersListTable, OrderListItem } from '@/components/restaurant/OrdersListTable';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Calendar } from '@/components/ui/calendar';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Sheet,
    SheetContent,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import { useOrderWebSocket } from '@/hooks/useOrderWebSocket';
import RestaurantLayout from '@/layouts/restaurant-layout';
import { Driver, Filters, Order, PaginatedData } from '@/types';

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
        date: string | null;
    };
    available_drivers: Driver[];
    restaurant_id: number;
    config?: {
        auto_print_new_orders: boolean;
    };
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
 * Pagina de ordenes del restaurante - Lista Simplificada
 */
export default function RestaurantOrdersIndex({
    orders,
    status_counts,
    filters,
    available_drivers,
    restaurant_id,
    config,
}: Props) {
    const [isUpdating, setIsUpdating] = useState<number | null>(null);
    const [dateOpen, setDateOpen] = useState(false);
    const [selectedOrder, setSelectedOrder] = useState<Order | null>(null);
    const [sheetOpen, setSheetOpen] = useState(false);
    const [, setTick] = useState(0);

    // WebSocket para detectar nuevas órdenes en tiempo real
    useOrderWebSocket({
        restaurantId: restaurant_id,
        autoPrint: config?.auto_print_new_orders ?? false,
        enabled: true,
        reloadProps: ['orders', 'status_counts'],
    });

    // Actualizar el tiempo cada minuto
    useEffect(() => {
        const interval = setInterval(() => setTick((t) => t + 1), 60000);
        return () => clearInterval(interval);
    }, []);

    // Actualizar la orden seleccionada cuando cambian los datos
    useEffect(() => {
        if (selectedOrder) {
            const updatedOrder = orders.data.find(o => o.id === selectedOrder.id);
            if (updatedOrder) {
                setSelectedOrder(updatedOrder);
            } else {
                // La orden ya no está en la lista (cambió de estado/página)
                setSheetOpen(false);
                setSelectedOrder(null);
            }
        }
    }, [orders.data]);

    const selectedDate = filters.date ? new Date(filters.date + 'T12:00:00') : new Date();

    const handleFilterChange = (key: string, value: string | null) => {
        const params: Record<string, string | number | undefined> = {
            per_page: filters.per_page,
        };

        if (key === 'date' && value) params.date = value;
        else if (filters.date && key !== 'date') params.date = filters.date;

        if (key === 'status' && value && value !== 'all') params.status = value;
        else if (filters.status && key !== 'status') params.status = filters.status;
        if (key === 'service_type' && value && value !== 'all') params.service_type = value;
        else if (filters.service_type && key !== 'service_type') params.service_type = filters.service_type;

        router.get('/restaurant/orders', params, {
            preserveScroll: true,
            replace: true,
        });
    };

    const handleDateChange = (date: Date | undefined) => {
        if (date) {
            const dateStr = format(date, 'yyyy-MM-dd');
            handleFilterChange('date', dateStr);
        }
        setDateOpen(false);
    };

    const isToday = filters.date === format(new Date(), 'yyyy-MM-dd');

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

    const handleMarkDelivered = (orderId: number) => {
        setIsUpdating(orderId);
        router.post(`/restaurant/orders/${orderId}/delivered`, {}, {
            preserveState: true,
            preserveScroll: true,
            onFinish: () => setIsUpdating(null),
        });
    };

    const handleViewOrder = (order: OrderListItem) => {
        // Buscar la orden completa en orders.data
        const fullOrder = orders.data.find(o => o.id === order.id);
        if (fullOrder) {
            setSelectedOrder(fullOrder);
            setSheetOpen(true);
        }
    };

    const handlePrintOrder = (order: OrderListItem) => {
        // Buscar la orden completa y imprimir
        const fullOrder = orders.data.find(o => o.id === order.id);
        if (fullOrder) {
            printOrder(fullOrder);
        }
    };

    const currentStatus = filters.status || 'all';

    // Ordenar por fecha de creacion (mas antiguas primero)
    const sortedOrders = [...orders.data].sort(
        (a, b) => new Date(a.created_at).getTime() - new Date(b.created_at).getTime()
    );

    // Adaptar órdenes al formato de OrdersListTable
    const tableOrders: OrderListItem[] = sortedOrders.map(order => ({
        id: order.id,
        order_number: order.order_number,
        customer: order.customer ? {
            full_name: order.customer.full_name,
            phone: order.customer.phone || null,
            subway_card: (order.customer as any).subway_card || null,
        } : null,
        status: order.status,
        service_type: order.service_type as 'pickup' | 'delivery',
        driver: order.driver,
        created_at: order.created_at,
        updated_at: (order as any).updated_at,
        total: order.total,
        items: order.items,
    }));

    return (
        <RestaurantLayout title="Ordenes">
            <div className="flex flex-col gap-4">
                {/* Header compacto */}
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div className="flex items-center gap-2">
                        <Package className="h-5 w-5 text-primary" />
                        <h1 className="text-xl font-bold">Ordenes</h1>
                    </div>

                    <div className="flex flex-wrap items-center gap-2">
                        {/* Selector de fecha */}
                        <Popover open={dateOpen} onOpenChange={setDateOpen}>
                            <PopoverTrigger asChild>
                                <Button variant="outline" size="sm" className="w-[160px] justify-start">
                                    <CalendarIcon className="mr-2 h-4 w-4" />
                                    {isToday ? 'Hoy' : format(selectedDate, "d MMM", { locale: es })}
                                </Button>
                            </PopoverTrigger>
                            <PopoverContent className="w-auto p-0" align="end">
                                <Calendar
                                    mode="single"
                                    selected={selectedDate}
                                    onSelect={handleDateChange}
                                    initialFocus
                                    locale={es}
                                />
                            </PopoverContent>
                        </Popover>

                        {!isToday && (
                            <Button
                                variant="ghost"
                                size="sm"
                                onClick={() => handleFilterChange('date', format(new Date(), 'yyyy-MM-dd'))}
                            >
                                Hoy
                            </Button>
                        )}

                        {/* Tipo de servicio */}
                        <Select
                            value={filters.service_type || 'all'}
                            onValueChange={(value) => handleFilterChange('service_type', value === 'all' ? null : value)}
                        >
                            <SelectTrigger className="w-[120px]">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">Todos</SelectItem>
                                <SelectItem value="delivery">Delivery</SelectItem>
                                <SelectItem value="pickup">Pickup</SelectItem>
                            </SelectContent>
                        </Select>
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
                                className="gap-1.5"
                            >
                                {tab.label}
                                {count !== null && count > 0 && (
                                    <Badge
                                        variant={isActive ? 'secondary' : 'outline'}
                                        className="ml-1 h-5 px-1.5 text-xs"
                                    >
                                        {count}
                                    </Badge>
                                )}
                            </Button>
                        );
                    })}
                </div>

                {/* Lista de ordenes */}
                {tableOrders.length === 0 ? (
                    <div className="flex flex-col items-center justify-center py-16">
                        <Package className="h-12 w-12 text-muted-foreground/50" />
                        <p className="mt-4 text-muted-foreground">
                            No hay ordenes {isToday ? 'para hoy' : `para el ${format(selectedDate, "d 'de' MMMM", { locale: es })}`}
                        </p>
                    </div>
                ) : (
                    <>
                        <div className="rounded-lg border">
                            <OrdersListTable
                                orders={tableOrders}
                                availableDrivers={available_drivers}
                                onAccept={handleAcceptOrder}
                                onMarkReady={handleMarkReady}
                                onMarkCompleted={handleMarkCompleted}
                                onMarkDelivered={handleMarkDelivered}
                                onAssignDriver={handleAssignDriver}
                                onViewOrder={handleViewOrder}
                                onPrintOrder={handlePrintOrder}
                                isUpdating={isUpdating}
                            />
                        </div>

                        {/* Paginacion */}
                        <PaginationWrapper
                            data={orders}
                            routeName="/restaurant/orders"
                            filters={{
                                per_page: filters.per_page,
                                ...(filters.date && { date: filters.date }),
                                ...(filters.status && { status: filters.status }),
                                ...(filters.service_type && { service_type: filters.service_type }),
                            }}
                            className="mt-4"
                        />
                    </>
                )}

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
                                    order={selectedOrder as unknown as OrderDetailData}
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
                                    onMarkDelivered={(orderId) => {
                                        handleMarkDelivered(orderId);
                                    }}
                                    onAssignDriver={(orderId, driverId) => {
                                        handleAssignDriver(orderId, driverId);
                                    }}
                                    onPrint={() => {
                                        printOrder(selectedOrder);
                                    }}
                                    isUpdating={isUpdating === selectedOrder.id}
                                    variant="sheet"
                                />
                            </div>
                        )}
                    </SheetContent>
                </Sheet>
            </div>
        </RestaurantLayout>
    );
}
