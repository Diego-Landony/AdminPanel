import { useState } from 'react';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Button } from '@/components/ui/button';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import {
    Bike,
    Check,
    ChefHat,
    Clock,
    Eye,
    Loader2,
    Phone,
    Printer,
    User,
    CreditCard,
    Package,
    HandPlatter,
} from 'lucide-react';
import { ORDER_TABLE_STATUS_CONFIG } from '@/constants/restaurant-constants';
import {
    formatSubwayCard,
    getTimeAgo,
    formatTime,
    getMinutesElapsed,
    getTimeUrgencyColor,
} from '@/utils/restaurant-helpers';

// Types
export interface OrderCustomer {
    full_name: string;
    phone: string | null;
    subway_card: string | null;
}

export interface OrderDriver {
    id: number;
    name: string;
    phone?: string | null;
}

export interface OrderListItem {
    id: number;
    order_number: string;
    customer?: OrderCustomer | null;
    customer_name?: string;
    customer_phone?: string | null;
    customer_subway_card?: string | null;
    status: string;
    service_type: 'pickup' | 'delivery';
    driver: OrderDriver | null;
    created_at: string;
    updated_at?: string;
    total?: number;
    items_count?: number;
    items?: any[];
}

export interface Driver {
    id: number;
    name: string;
    phone?: string | null;
}

interface OrdersListTableProps {
    orders: OrderListItem[];
    availableDrivers: Driver[];
    onAccept: (orderId: number) => void;
    onMarkReady: (orderId: number) => void;
    onMarkCompleted: (orderId: number) => void;
    onMarkDelivered: (orderId: number) => void;
    onAssignDriver: (orderId: number, driverId: number) => void;
    onViewOrder: (order: OrderListItem) => void;
    onPrintOrder: (order: OrderListItem) => void;
    isUpdating?: number | null;
}

export function OrdersListTable({
    orders,
    availableDrivers,
    onAccept,
    onMarkReady,
    onMarkCompleted,
    onMarkDelivered,
    onAssignDriver,
    onViewOrder,
    onPrintOrder,
    isUpdating,
}: OrdersListTableProps) {
    const [driverPopoverOpen, setDriverPopoverOpen] = useState<number | null>(null);

    // Helper para obtener datos del cliente (soporta ambos formatos)
    const getCustomerData = (order: OrderListItem) => {
        if (order.customer) {
            return {
                name: order.customer.full_name,
                phone: order.customer.phone,
                subwayCard: order.customer.subway_card,
            };
        }
        return {
            name: order.customer_name || 'Cliente',
            phone: order.customer_phone,
            subwayCard: order.customer_subway_card,
        };
    };

    if (orders.length === 0) {
        return (
            <div className="flex flex-col items-center justify-center py-12 text-muted-foreground">
                <Package className="h-12 w-12 mb-4 opacity-50" />
                <p className="text-lg font-medium">No hay órdenes</p>
                <p className="text-sm">Las órdenes aparecerán aquí cuando lleguen</p>
            </div>
        );
    }

    return (
        <Table>
            <TableHeader>
                <TableRow className="bg-muted/50">
                    <TableHead className="w-[140px]"># Orden</TableHead>
                    <TableHead className="w-[180px]">Cliente</TableHead>
                    <TableHead className="w-[280px]">Estado / Acción</TableHead>
                    <TableHead className="w-[100px] text-right"></TableHead>
                </TableRow>
            </TableHeader>
            <TableBody>
                {orders.map((order) => {
                    const customer = getCustomerData(order);
                    const minutes = getMinutesElapsed(order.created_at);
                    const status = ORDER_TABLE_STATUS_CONFIG[order.status] || ORDER_TABLE_STATUS_CONFIG.pending;
                    const isLoading = isUpdating === order.id;

                    // Color de urgencia basado en tiempo
                    const timeColor = getTimeUrgencyColor(minutes);

                    return (
                        <TableRow key={order.id} className={`${status.rowBg} hover:opacity-90 transition-opacity`}>
                            {/* Columna 1: Número de orden + tipo + tiempo */}
                            <TableCell className="py-3">
                                <div className="flex flex-col gap-1">
                                    <span className="font-bold text-base">#{order.order_number}</span>
                                    <div className="flex items-center gap-1">
                                        {order.service_type === 'delivery' ? (
                                            <span className="inline-flex items-center text-xs font-medium text-blue-700 dark:text-blue-400">
                                                <Bike className="h-3 w-3 mr-1" />
                                                Delivery
                                            </span>
                                        ) : (
                                            <span className="inline-flex items-center text-xs font-medium text-orange-700 dark:text-orange-400">
                                                <HandPlatter className="h-3 w-3 mr-1" />
                                                Pickup
                                            </span>
                                        )}
                                    </div>
                                    {/* Tiempo: para órdenes activas muestra "hace X min", para completadas muestra la hora */}
                                    {['completed', 'delivered', 'cancelled'].includes(order.status) ? (
                                        <span className="inline-flex items-center text-xs text-muted-foreground">
                                            <Check className="h-3 w-3 mr-1" />
                                            {order.updated_at ? `a las ${formatTime(order.updated_at)}` : formatTime(order.created_at)}
                                        </span>
                                    ) : (
                                        <span className={`inline-flex items-center text-xs ${timeColor}`}>
                                            <Clock className="h-3 w-3 mr-1" />
                                            {getTimeAgo(order.created_at)}
                                        </span>
                                    )}
                                </div>
                            </TableCell>

                            {/* Columna 2: Cliente */}
                            <TableCell className="py-3">
                                <div className="flex flex-col gap-0.5">
                                    <span className="font-medium text-sm">{customer.name}</span>
                                    {customer.phone && (
                                        <span className="text-xs text-muted-foreground flex items-center gap-1">
                                            <Phone className="h-3 w-3" />
                                            {customer.phone}
                                        </span>
                                    )}
                                    {customer.subwayCard && (
                                        <span className="text-xs text-muted-foreground flex items-center gap-1 font-mono">
                                            <CreditCard className="h-3 w-3" />
                                            {formatSubwayCard(customer.subwayCard)}
                                        </span>
                                    )}
                                </div>
                            </TableCell>

                            {/* Columna 3: Estado + Acción Principal */}
                            <TableCell className="py-3">
                                <div className="flex items-center gap-3">
                                    {/* Indicador de estado */}
                                    <div className={`flex items-center gap-2 ${status.textColor}`}>
                                        {status.icon}
                                        <div className="flex flex-col">
                                            <span className="font-semibold text-sm">{status.label}</span>
                                            <span className="text-xs opacity-80">{status.description}</span>
                                        </div>
                                    </div>

                                    {/* Separador visual */}
                                    <div className="h-8 w-px bg-border" />

                                    {/* Botón de acción */}
                                    <div className="flex items-center gap-2">
                                        {order.status === 'pending' && (
                                            <Button
                                                size="sm"
                                                className="bg-amber-500 hover:bg-amber-600 text-white font-medium"
                                                onClick={() => onAccept(order.id)}
                                                disabled={isLoading}
                                            >
                                                {isLoading ? (
                                                    <Loader2 className="h-4 w-4 animate-spin" />
                                                ) : (
                                                    'Aceptar Orden'
                                                )}
                                            </Button>
                                        )}

                                        {order.status === 'preparing' && (
                                            <Button
                                                size="sm"
                                                className="bg-blue-600 hover:bg-blue-700 text-white font-medium"
                                                onClick={() => onMarkReady(order.id)}
                                                disabled={isLoading}
                                            >
                                                {isLoading ? (
                                                    <Loader2 className="h-4 w-4 animate-spin" />
                                                ) : (
                                                    'Marcar Lista'
                                                )}
                                            </Button>
                                        )}

                                        {order.status === 'ready' && order.service_type === 'pickup' && (
                                            <Button
                                                size="sm"
                                                className="bg-green-600 hover:bg-green-700 text-white font-medium"
                                                onClick={() => onMarkCompleted(order.id)}
                                                disabled={isLoading}
                                            >
                                                {isLoading ? (
                                                    <Loader2 className="h-4 w-4 animate-spin" />
                                                ) : (
                                                    'Entregar al Cliente'
                                                )}
                                            </Button>
                                        )}

                                        {order.status === 'ready' && order.service_type === 'delivery' && !order.driver && (
                                            <Popover
                                                open={driverPopoverOpen === order.id}
                                                onOpenChange={(open) => setDriverPopoverOpen(open ? order.id : null)}
                                            >
                                                <PopoverTrigger asChild>
                                                    <Button
                                                        size="sm"
                                                        className="bg-purple-600 hover:bg-purple-700 text-white font-medium"
                                                        disabled={isLoading}
                                                    >
                                                        {isLoading ? (
                                                            <Loader2 className="h-4 w-4 animate-spin" />
                                                        ) : (
                                                            'Asignar Motorista'
                                                        )}
                                                    </Button>
                                                </PopoverTrigger>
                                                <PopoverContent className="w-56 p-2" align="start">
                                                    <p className="text-sm font-medium mb-2 px-2">Seleccionar motorista</p>
                                                    {availableDrivers.length === 0 ? (
                                                        <p className="text-sm text-muted-foreground py-2 px-2">
                                                            No hay motoristas disponibles
                                                        </p>
                                                    ) : (
                                                        <div className="space-y-1">
                                                            {availableDrivers.map((driver) => (
                                                                <Button
                                                                    key={driver.id}
                                                                    variant="ghost"
                                                                    className="w-full justify-start"
                                                                    onClick={() => {
                                                                        onAssignDriver(order.id, driver.id);
                                                                        setDriverPopoverOpen(null);
                                                                    }}
                                                                >
                                                                    <User className="h-4 w-4 mr-2" />
                                                                    {driver.name}
                                                                </Button>
                                                            ))}
                                                        </div>
                                                    )}
                                                </PopoverContent>
                                            </Popover>
                                        )}

                                        {order.status === 'ready' && order.service_type === 'delivery' && order.driver && (
                                            <div className="flex items-center gap-2 text-sm text-purple-700 dark:text-purple-400">
                                                <Bike className="h-4 w-4" />
                                                <span className="font-medium">{order.driver.name}</span>
                                            </div>
                                        )}

                                        {order.status === 'out_for_delivery' && order.driver && (
                                            <div className="flex items-center gap-2 text-sm text-orange-700 dark:text-orange-400">
                                                <Bike className="h-4 w-4" />
                                                <span className="font-medium">En camino con {order.driver.name}</span>
                                            </div>
                                        )}
                                    </div>
                                </div>
                            </TableCell>

                            {/* Columna 4: Acciones secundarias */}
                            <TableCell className="py-3">
                                <div className="flex flex-col gap-1">
                                    <Button
                                        size="sm"
                                        variant="outline"
                                        className="h-7 text-xs justify-start"
                                        onClick={() => onViewOrder(order)}
                                    >
                                        <Eye className="h-3.5 w-3.5 mr-1.5" />
                                        Ver
                                    </Button>
                                    <Button
                                        size="sm"
                                        variant="outline"
                                        className="h-7 text-xs justify-start"
                                        onClick={() => onPrintOrder(order)}
                                    >
                                        <Printer className="h-3.5 w-3.5 mr-1.5" />
                                        Imprimir
                                    </Button>
                                </div>
                            </TableCell>
                        </TableRow>
                    );
                })}
            </TableBody>
        </Table>
    );
}
