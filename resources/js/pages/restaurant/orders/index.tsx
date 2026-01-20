import { router } from '@inertiajs/react';
import { format } from 'date-fns';
import { es } from 'date-fns/locale';
import {
    Bike,
    CalendarIcon,
    Clock,
    CreditCard,
    MapPin,
    Package,
    Phone,
    Printer,
    User,
    Wallet,
} from 'lucide-react';
import { useState, useEffect } from 'react';

import { printOrder } from '@/components/orders/PrintComanda';
import { PaginationWrapper } from '@/components/PaginationWrapper';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Calendar } from '@/components/ui/calendar';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Separator } from '@/components/ui/separator';
import { CURRENCY } from '@/constants/ui-constants';
import { useOrderPolling } from '@/hooks/useOrderPolling';
import RestaurantLayout from '@/layouts/restaurant-layout';
import { Driver, Filters, Order, PaginatedData } from '@/types';
import { formatCurrency } from '@/utils/format';
import { OrdersListTable, OrderListItem } from '@/components/restaurant/OrdersListTable';

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
    config?: {
        polling_interval: number;
        auto_print_new_orders: boolean;
    };
}

/**
 * Formatea el SubwayCard en formato 8XXX-XXXX-XXX
 */
const formatSubwayCard = (card: string | null | undefined): string | null => {
    if (!card) return null;
    // Si tiene 11 dígitos, formatear como 8XXX-XXXX-XXX
    if (card.length === 11) {
        return `${card.slice(0, 4)}-${card.slice(4, 8)}-${card.slice(8)}`;
    }
    return card;
};

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
    config,
}: Props) {
    const [isUpdating, setIsUpdating] = useState<number | null>(null);
    const [dateOpen, setDateOpen] = useState(false);
    const [selectedOrder, setSelectedOrder] = useState<Order | null>(null);
    const [, setTick] = useState(0);

    // Polling para detectar nuevas órdenes en tiempo real
    useOrderPolling({
        intervalSeconds: config?.polling_interval || 15,
        autoPrint: config?.auto_print_new_orders ?? false,
        enabled: true,
        reloadProps: ['orders', 'status_counts'],
    });

    // Actualizar el tiempo cada minuto
    useEffect(() => {
        const interval = setInterval(() => setTick((t) => t + 1), 60000);
        return () => clearInterval(interval);
    }, []);

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
            preserveState: true,
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

    const handlePrint = () => {
        if (selectedOrder) {
            printOrder(selectedOrder);
        }
    };

    const handleViewOrder = (order: OrderListItem) => {
        // Buscar la orden completa en orders.data
        const fullOrder = orders.data.find(o => o.id === order.id);
        if (fullOrder) {
            setSelectedOrder(fullOrder);
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

                {/* Modal de Comanda */}
                <Dialog open={!!selectedOrder} onOpenChange={(open) => !open && setSelectedOrder(null)}>
                    <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-lg">
                        {selectedOrder && (
                            <>
                                <DialogHeader className="pb-2">
                                    <DialogTitle className="flex items-center justify-between">
                                        <span className="text-xl">Orden #{selectedOrder.order_number}</span>
                                        <div className="flex items-center gap-2">
                                            <Badge variant={selectedOrder.service_type === 'delivery' ? 'default' : 'secondary'}>
                                                {selectedOrder.service_type === 'delivery' ? 'Delivery' : 'Pickup'}
                                            </Badge>
                                            <Badge variant="outline" className="gap-1">
                                                {selectedOrder.payment_method === 'card' ? (
                                                    <><CreditCard className="h-3 w-3" /> Tarjeta</>
                                                ) : (
                                                    <><Wallet className="h-3 w-3" /> Efectivo</>
                                                )}
                                            </Badge>
                                        </div>
                                    </DialogTitle>
                                    {/* Fecha y hora */}
                                    <div className="flex items-center gap-1.5 text-sm text-muted-foreground mt-1">
                                        <Clock className="h-3.5 w-3.5" />
                                        <span>{format(new Date(selectedOrder.created_at), "d MMM yyyy 'a las' HH:mm", { locale: es })}</span>
                                    </div>
                                </DialogHeader>

                                <div className="space-y-4">
                                    {/* Info del cliente */}
                                    <div className="rounded-lg bg-muted/50 p-3 space-y-2">
                                        <div className="flex items-center gap-2">
                                            <User className="h-4 w-4 text-muted-foreground" />
                                            <span className="font-medium">{selectedOrder.customer?.full_name || 'Cliente sin nombre'}</span>
                                        </div>
                                        {selectedOrder.customer?.phone && (
                                            <div className="flex items-center gap-2">
                                                <Phone className="h-4 w-4 text-muted-foreground" />
                                                <a href={`tel:${selectedOrder.customer.phone}`} className="text-sm text-primary hover:underline">
                                                    {selectedOrder.customer.phone}
                                                </a>
                                            </div>
                                        )}
                                        {(selectedOrder.customer as any)?.subway_card && (
                                            <div className="flex items-center gap-2">
                                                <CreditCard className="h-4 w-4 text-green-600" />
                                                <span className="text-sm font-mono font-medium text-green-700 dark:text-green-400">
                                                    SubwayCard: {formatSubwayCard((selectedOrder.customer as any).subway_card)}
                                                </span>
                                            </div>
                                        )}
                                    </div>

                                    {/* Direccion de entrega (solo delivery) */}
                                    {selectedOrder.service_type === 'delivery' && selectedOrder.delivery_address && (
                                        <div className="rounded-lg border border-blue-200 bg-blue-50 p-3 dark:border-blue-800 dark:bg-blue-950">
                                            <div className="flex items-start gap-2">
                                                <MapPin className="h-4 w-4 text-blue-600 mt-0.5" />
                                                <div>
                                                    <p className="text-sm font-medium text-blue-800 dark:text-blue-200">Direccion de Entrega</p>
                                                    <p className="text-sm text-blue-700 dark:text-blue-300">
                                                        {typeof selectedOrder.delivery_address === 'string'
                                                            ? selectedOrder.delivery_address
                                                            : (selectedOrder.delivery_address as any)?.address_line || 'Sin direccion'}
                                                    </p>
                                                    {typeof selectedOrder.delivery_address === 'object' && (selectedOrder.delivery_address as any)?.delivery_notes && (
                                                        <p className="text-xs text-blue-600 dark:text-blue-400 mt-1 italic">
                                                            Notas: {(selectedOrder.delivery_address as any).delivery_notes}
                                                        </p>
                                                    )}
                                                </div>
                                            </div>
                                        </div>
                                    )}

                                    {/* Info del motorista (solo si delivery y tiene motorista) */}
                                    {selectedOrder.service_type === 'delivery' && selectedOrder.driver && (
                                        <div className="rounded-lg border border-green-200 bg-green-50 p-3 dark:border-green-800 dark:bg-green-950">
                                            <div className="flex items-center gap-2">
                                                <Bike className="h-4 w-4 text-green-600" />
                                                <span className="text-sm font-medium text-green-800 dark:text-green-200">
                                                    Motorista: {selectedOrder.driver.name}
                                                </span>
                                                {selectedOrder.driver.phone && (
                                                    <a href={`tel:${selectedOrder.driver.phone}`} className="text-sm text-green-700 dark:text-green-300 hover:underline ml-auto">
                                                        {selectedOrder.driver.phone}
                                                    </a>
                                                )}
                                            </div>
                                        </div>
                                    )}

                                    <Separator />

                                    {/* Items del pedido */}
                                    <div>
                                        <h4 className="font-semibold text-sm text-muted-foreground mb-3">
                                            PRODUCTOS ({selectedOrder.items?.length || 0})
                                        </h4>
                                        <div className="space-y-4">
                                        {selectedOrder.items?.map((item) => {
                                            // Agrupar opciones por seccion
                                            const groupedOptions: Record<string, { name: string; price: number }[]> = {};
                                            if (item.options) {
                                                for (const opt of item.options) {
                                                    const sectionName = opt.section_name || 'Opciones';
                                                    if (!groupedOptions[sectionName]) {
                                                        groupedOptions[sectionName] = [];
                                                    }
                                                    groupedOptions[sectionName].push({ name: opt.name, price: opt.price });
                                                }
                                            }
                                            const extrasTotal = item.options_price || 0;
                                            const basePrice = (item.unit_price || 0) - extrasTotal;

                                            return (
                                                <div key={item.id} className="rounded-lg border bg-muted/30 p-4">
                                                    {/* Header del producto */}
                                                    <div className="flex items-start justify-between border-b pb-3 mb-3">
                                                        <div>
                                                            {item.category && (
                                                                <p className="text-xs text-muted-foreground uppercase tracking-wide mb-0.5">
                                                                    {item.category}
                                                                </p>
                                                            )}
                                                            <p className="font-bold text-base">{item.name}</p>
                                                            {item.variant && (
                                                                <p className="text-sm text-muted-foreground">{item.variant}</p>
                                                            )}
                                                        </div>
                                                        <Badge variant="secondary" className="text-sm font-bold">
                                                            x{item.quantity}
                                                        </Badge>
                                                    </div>

                                                    {/* Opciones agrupadas por seccion */}
                                                    {Object.keys(groupedOptions).length > 0 && (
                                                        <div className="space-y-1 mb-3">
                                                            {Object.entries(groupedOptions).map(([sectionName, options]) => (
                                                                <p key={sectionName} className="text-sm">
                                                                    <span className="font-semibold">{sectionName}:</span>{' '}
                                                                    <span className="text-muted-foreground">
                                                                        {options.map((o) => o.name).join(', ')}
                                                                    </span>
                                                                </p>
                                                            ))}
                                                        </div>
                                                    )}

                                                    {/* Notas del item */}
                                                    {item.notes && (
                                                        <p className="text-sm italic text-orange-600 dark:text-orange-400 mb-3">
                                                            Nota: {item.notes}
                                                        </p>
                                                    )}

                                                    {/* Desglose de precios */}
                                                    <div className="border-t pt-3 space-y-1">
                                                        <div className="flex justify-between text-sm">
                                                            <span className="text-muted-foreground">Base</span>
                                                            <span>{CURRENCY.symbol}{formatCurrency(basePrice > 0 ? basePrice : item.unit_price || 0, false)}</span>
                                                        </div>
                                                        {extrasTotal > 0 && (
                                                            <div className="flex justify-between text-sm">
                                                                <span className="text-muted-foreground">Extras</span>
                                                                <span>{CURRENCY.symbol}{formatCurrency(extrasTotal, false)}</span>
                                                            </div>
                                                        )}
                                                        <div className="flex justify-between font-semibold text-primary pt-1">
                                                            <span>TOTAL</span>
                                                            <span>{CURRENCY.symbol}{formatCurrency(item.total_price, false)}</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            );
                                        })}
                                        </div>
                                    </div>

                                    {/* Notas del pedido */}
                                    {selectedOrder.notes && (
                                        <div className="rounded-lg border border-orange-200 bg-orange-50 p-3 dark:border-orange-800 dark:bg-orange-950">
                                            <p className="text-sm font-medium text-orange-800 dark:text-orange-200">
                                                Notas: {selectedOrder.notes}
                                            </p>
                                        </div>
                                    )}

                                    <Separator />

                                    {/* Totales */}
                                    <div className="space-y-1">
                                        <div className="flex justify-between text-sm">
                                            <span className="text-muted-foreground">Subtotal</span>
                                            <span>{CURRENCY.symbol}{formatCurrency(selectedOrder.subtotal || 0, false)}</span>
                                        </div>
                                        {(selectedOrder.delivery_fee ?? 0) > 0 && (
                                            <div className="flex justify-between text-sm">
                                                <span className="text-muted-foreground">Envio</span>
                                                <span>{CURRENCY.symbol}{formatCurrency(selectedOrder.delivery_fee || 0, false)}</span>
                                            </div>
                                        )}
                                        {(selectedOrder.discount ?? 0) > 0 && (
                                            <div className="flex justify-between text-sm text-green-600">
                                                <span>Descuento</span>
                                                <span>-{CURRENCY.symbol}{formatCurrency(selectedOrder.discount || 0, false)}</span>
                                            </div>
                                        )}
                                        <Separator className="my-2" />
                                        <div className="flex justify-between text-lg font-bold">
                                            <span>Total</span>
                                            <span>{CURRENCY.symbol}{formatCurrency(selectedOrder.total, false)}</span>
                                        </div>
                                    </div>

                                    {/* Boton de imprimir */}
                                    <Button variant="default" onClick={handlePrint} className="w-full gap-2">
                                        <Printer className="h-4 w-4" />
                                        Imprimir Comanda
                                    </Button>
                                </div>
                            </>
                        )}
                    </DialogContent>
                </Dialog>
            </div>
        </RestaurantLayout>
    );
}
