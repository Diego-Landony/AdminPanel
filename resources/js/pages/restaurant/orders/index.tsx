import { router } from '@inertiajs/react';
import { format } from 'date-fns';
import { es } from 'date-fns/locale';
import {
    CalendarIcon,
    Check,
    CheckCircle,
    ChevronsUpDown,
    Clock,
    Eye,
    Package,
    ShoppingBag,
    Truck,
    Users,
} from 'lucide-react';
import { useRef, useState, useEffect } from 'react';

import { PrintComanda } from '@/components/orders/PrintComanda';
import { PaginationWrapper } from '@/components/PaginationWrapper';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Calendar } from '@/components/ui/calendar';
import { Command, CommandEmpty, CommandGroup, CommandInput, CommandItem, CommandList } from '@/components/ui/command';
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
import { cn } from '@/lib/utils';
import { CURRENCY } from '@/constants/ui-constants';
import RestaurantLayout from '@/layouts/restaurant-layout';
import { Driver, Filters, Order, PaginatedData } from '@/types';
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
        date: string | null;
    };
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
 * Helper para calcular tiempo relativo corto
 */
function timeAgoShort(dateString: string): string {
    const diffMins = getMinutesElapsed(dateString);

    if (diffMins < 1) return 'ahora';
    if (diffMins < 60) return `${diffMins}m`;
    const diffHours = Math.floor(diffMins / 60);
    if (diffHours < 24) return `${diffHours}h`;
    return new Date(dateString).toLocaleDateString('es-GT');
}

/**
 * Obtiene clase de color del indicador de tiempo
 */
function getTimeColor(dateString: string, status: string): string {
    if (['completed', 'delivered', 'cancelled'].includes(status)) {
        return 'text-muted-foreground';
    }
    const mins = getMinutesElapsed(dateString);
    if (mins < 10) return 'text-green-600 dark:text-green-400';
    if (mins < 20) return 'text-yellow-600 dark:text-yellow-400';
    if (mins < 30) return 'text-orange-600 dark:text-orange-400';
    return 'text-red-600 dark:text-red-400 font-semibold';
}

/**
 * Obtiene clase de fondo para la fila
 */
function getRowBg(dateString: string, status: string): string {
    if (['completed', 'delivered', 'cancelled'].includes(status)) {
        return '';
    }
    const mins = getMinutesElapsed(dateString);
    if (mins < 10) return '';
    if (mins < 20) return 'bg-yellow-50/50 dark:bg-yellow-900/10';
    if (mins < 30) return 'bg-orange-50/50 dark:bg-orange-900/10';
    return 'bg-red-50/50 dark:bg-red-900/10';
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
}: Props) {
    const [isUpdating, setIsUpdating] = useState<number | null>(null);
    const [dateOpen, setDateOpen] = useState(false);
    const [selectedOrder, setSelectedOrder] = useState<Order | null>(null);
    const [driverPopoverOpen, setDriverPopoverOpen] = useState<number | null>(null);
    const [, setTick] = useState(0);
    const printRef = useRef<HTMLDivElement>(null);

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
                setDriverPopoverOpen(null);
            },
        });
    };

    const handlePrint = () => {
        window.print();
    };

    const currentStatus = filters.status || 'all';

    // Ordenar por fecha de creacion (mas antiguas primero)
    const sortedOrders = [...orders.data].sort(
        (a, b) => new Date(a.created_at).getTime() - new Date(b.created_at).getTime()
    );

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
                {sortedOrders.length === 0 ? (
                    <div className="flex flex-col items-center justify-center py-16">
                        <Package className="h-12 w-12 text-muted-foreground/50" />
                        <p className="mt-4 text-muted-foreground">
                            No hay ordenes {isToday ? 'para hoy' : `para el ${format(selectedDate, "d 'de' MMMM", { locale: es })}`}
                        </p>
                    </div>
                ) : (
                    <>
                        <div className="rounded-lg border">
                            {sortedOrders.map((order, index) => {
                                const canAccept = order.status === 'pending';
                                const canMarkReady = order.status === 'preparing';
                                const canAssignDriver = order.status === 'ready' && order.service_type === 'delivery' && !order.driver;
                                const canMarkCompleted = order.status === 'ready' && order.service_type === 'pickup';

                                return (
                                    <div
                                        key={order.id}
                                        className={cn(
                                            'flex items-center justify-between gap-2 px-3 py-2',
                                            index !== 0 && 'border-t',
                                            getRowBg(order.created_at, order.status)
                                        )}
                                    >
                                        {/* Info principal */}
                                        <div className="flex items-center gap-3 min-w-0">
                                            {/* Numero de orden */}
                                            <span className="font-bold text-sm w-16 shrink-0">
                                                #{order.order_number}
                                            </span>

                                            {/* Tipo de servicio */}
                                            {order.service_type === 'delivery' ? (
                                                <Truck className="h-4 w-4 text-blue-600 shrink-0" />
                                            ) : (
                                                <ShoppingBag className="h-4 w-4 text-orange-600 shrink-0" />
                                            )}

                                            {/* Cliente (solo en desktop) */}
                                            <span className="text-sm text-muted-foreground truncate hidden sm:block max-w-[120px]">
                                                {order.customer?.full_name || 'N/A'}
                                            </span>

                                            {/* Items count */}
                                            <span className="text-xs text-muted-foreground shrink-0">
                                                {order.items_count} items
                                            </span>

                                            {/* Tiempo */}
                                            <span className={cn('text-xs shrink-0', getTimeColor(order.created_at, order.status))}>
                                                {timeAgoShort(order.created_at)}
                                            </span>

                                            {/* Motorista (si esta asignado) */}
                                            {order.driver && (
                                                <span className="text-xs text-muted-foreground shrink-0 hidden md:flex items-center gap-1">
                                                    <Users className="h-3 w-3" />
                                                    {order.driver.name}
                                                </span>
                                            )}
                                        </div>

                                        {/* Acciones */}
                                        <div className="flex items-center gap-1 shrink-0">
                                            {/* Boton de accion principal */}
                                            {canAccept && (
                                                <Button
                                                    size="sm"
                                                    onClick={() => handleAcceptOrder(order.id)}
                                                    disabled={isUpdating === order.id}
                                                    className="h-7 px-2 text-xs bg-green-600 hover:bg-green-700"
                                                >
                                                    {isUpdating === order.id ? '...' : 'Aceptar'}
                                                </Button>
                                            )}
                                            {canMarkReady && (
                                                <Button
                                                    size="sm"
                                                    onClick={() => handleMarkReady(order.id)}
                                                    disabled={isUpdating === order.id}
                                                    className="h-7 px-2 text-xs"
                                                >
                                                    {isUpdating === order.id ? '...' : 'Lista'}
                                                </Button>
                                            )}
                                            {canMarkCompleted && (
                                                <Button
                                                    size="sm"
                                                    onClick={() => handleMarkCompleted(order.id)}
                                                    disabled={isUpdating === order.id}
                                                    className="h-7 px-2 text-xs bg-green-600 hover:bg-green-700"
                                                >
                                                    {isUpdating === order.id ? '...' : 'Completar'}
                                                </Button>
                                            )}

                                            {/* Asignar motorista */}
                                            {canAssignDriver && (
                                                <Popover
                                                    open={driverPopoverOpen === order.id}
                                                    onOpenChange={(open) => setDriverPopoverOpen(open ? order.id : null)}
                                                >
                                                    <PopoverTrigger asChild>
                                                        <Button
                                                            variant="outline"
                                                            size="sm"
                                                            className="h-7 px-2 text-xs"
                                                            disabled={isUpdating === order.id}
                                                        >
                                                            <Users className="h-3 w-3 mr-1" />
                                                            <span className="hidden sm:inline">Asignar</span>
                                                            <ChevronsUpDown className="h-3 w-3 ml-1" />
                                                        </Button>
                                                    </PopoverTrigger>
                                                    <PopoverContent className="w-[180px] p-0" align="end">
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
                                            )}

                                            {/* Ver detalle */}
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                onClick={() => setSelectedOrder(order)}
                                                className="h-7 w-7 p-0"
                                            >
                                                <Eye className="h-4 w-4" />
                                            </Button>
                                        </div>
                                    </div>
                                );
                            })}
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
                                <DialogHeader>
                                    <DialogTitle className="flex items-center justify-between">
                                        <span>Orden #{selectedOrder.order_number}</span>
                                        <Badge variant={selectedOrder.service_type === 'delivery' ? 'default' : 'secondary'}>
                                            {selectedOrder.service_type === 'delivery' ? 'Delivery' : 'Pickup'}
                                        </Badge>
                                    </DialogTitle>
                                </DialogHeader>

                                <div className="space-y-4">
                                    {/* Info del cliente */}
                                    <div className="rounded-lg bg-muted/50 p-3">
                                        <p className="font-medium">{selectedOrder.customer?.full_name || 'N/A'}</p>
                                        {selectedOrder.customer?.phone && (
                                            <p className="text-sm text-muted-foreground">{selectedOrder.customer.phone}</p>
                                        )}
                                    </div>

                                    <Separator />

                                    {/* Items del pedido */}
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
                                    <Button variant="outline" onClick={handlePrint} className="w-full">
                                        Imprimir Comanda
                                    </Button>
                                </div>

                                {/* Componente de impresion */}
                                <PrintComanda ref={printRef} order={selectedOrder} />
                            </>
                        )}
                    </DialogContent>
                </Dialog>
            </div>
        </RestaurantLayout>
    );
}
