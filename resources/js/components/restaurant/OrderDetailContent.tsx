import { CURRENCY } from '@/constants/ui-constants';
import {
    ORDER_STATUS_CONFIGS,
    SERVICE_TYPE_CONFIGS,
    PAYMENT_STATUS_CONFIGS,
    PAYMENT_METHOD_LABELS,
} from '@/constants/restaurant-constants';
import { formatDateTime } from '@/utils/restaurant-helpers';
import { formatCurrency } from '@/utils/format';
import {
    Clock,
    CreditCard,
    Loader2,
    MapPin,
    Phone,
    Printer,
    ShoppingBag,
    Truck,
    User,
    Users,
    UserPlus,
    CheckCircle,
} from 'lucide-react';
import { useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { Command, CommandEmpty, CommandGroup, CommandInput, CommandItem, CommandList } from '@/components/ui/command';
import { cn } from '@/lib/utils';
import { StatusBadge } from '@/components/status-badge';

// Types
export interface OrderItem {
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

export interface DeliveryAddress {
    label?: string;
    address_line_1?: string;
    address_line?: string;
    city?: string;
    reference?: string;
    delivery_notes?: string;
}

export interface OrderDriver {
    id: number;
    name: string;
    phone?: string;
}

export interface OrderCustomer {
    full_name?: string;
    email?: string | null;
    phone?: string | null;
    subway_card?: string | null;
}

export interface OrderStatusHistoryItem {
    id: number;
    status: string;
    created_at: string;
    changed_by_type: string;
    notes?: string | null;
}

export interface OrderDetailData {
    id: number;
    order_number: string;
    customer?: OrderCustomer | null;
    customer_name?: string;
    customer_phone?: string | null;
    customer_email?: string | null;
    customer_subway_card?: string | null;
    status: string;
    service_type: 'pickup' | 'delivery';
    subtotal?: number;
    discount?: number;
    discount_total?: number;
    total: number;
    payment_method: string;
    payment_status?: string;
    notes?: string | null;
    delivery_address?: DeliveryAddress | null;
    items: OrderItem[];
    items_count?: number;
    driver?: OrderDriver | null;
    driver_id?: number | null;
    created_at: string;
    updated_at?: string;
    estimated_ready_at?: string | null;
    ready_at?: string | null;
    completed_at?: string | null;
    cancelled_at?: string | null;
    cancellation_reason?: string | null;
    assigned_to_driver_at?: string | null;
    picked_up_at?: string | null;
    status_history?: OrderStatusHistoryItem[];
}

export interface AvailableDriver {
    id: number;
    name: string;
    phone?: string;
    active_orders_count?: number;
}

interface OrderDetailContentProps {
    order: OrderDetailData;
    availableDrivers: AvailableDriver[];
    onAccept: (orderId: number) => void;
    onMarkReady: (orderId: number) => void;
    onMarkCompleted: (orderId: number) => void;
    onAssignDriver: (orderId: number, driverId: number) => void;
    onPrint: () => void;
    isUpdating?: boolean;
    variant?: 'page' | 'sheet';
}

export function OrderDetailContent({
    order,
    availableDrivers,
    onAccept,
    onMarkReady,
    onMarkCompleted,
    onAssignDriver,
    onPrint,
    isUpdating = false,
    variant = 'page',
}: OrderDetailContentProps) {
    const [driverSearchOpen, setDriverSearchOpen] = useState(false);
    const [selectedDriverId, setSelectedDriverId] = useState<string>('');

    // Determine customer info
    const customerName = order.customer?.full_name || order.customer_name || 'Cliente';
    const customerPhone = order.customer?.phone || order.customer_phone;
    const customerEmail = order.customer?.email || order.customer_email;
    const customerSubwayCard = order.customer?.subway_card || order.customer_subway_card;

    // Delivery address
    const deliveryAddressLine = order.delivery_address?.address_line || order.delivery_address?.address_line_1 || '';

    // Actions available
    const canAccept = order.status === 'pending';
    const canMarkReady = order.status === 'preparing';
    const canAssignDriver = order.service_type === 'delivery' && order.status === 'ready' && !order.driver_id;
    const canMarkCompleted = order.service_type === 'pickup' && order.status === 'ready';

    // Discount value
    const discountValue = order.discount ?? order.discount_total ?? 0;

    const handleAssignDriver = () => {
        if (selectedDriverId) {
            onAssignDriver(order.id, parseInt(selectedDriverId));
            setSelectedDriverId('');
        }
    };

    const isSheet = variant === 'sheet';

    return (
        <div className={cn('space-y-6', isSheet && 'px-1')}>
            {/* Action buttons */}
            <div className="flex flex-wrap items-center gap-2">
                <Button variant="outline" size="sm" onClick={onPrint}>
                    <Printer className="mr-2 h-4 w-4" />
                    Imprimir
                </Button>
                {canAccept && (
                    <Button
                        size="sm"
                        onClick={() => onAccept(order.id)}
                        disabled={isUpdating}
                        className="bg-green-600 hover:bg-green-700"
                    >
                        {isUpdating ? <Loader2 className="mr-2 h-4 w-4 animate-spin" /> : <CheckCircle className="mr-2 h-4 w-4" />}
                        Aceptar Orden
                    </Button>
                )}
                {canMarkReady && (
                    <Button
                        size="sm"
                        onClick={() => onMarkReady(order.id)}
                        disabled={isUpdating}
                    >
                        {isUpdating ? <Loader2 className="mr-2 h-4 w-4 animate-spin" /> : <CheckCircle className="mr-2 h-4 w-4" />}
                        Marcar Lista
                    </Button>
                )}
                {canAssignDriver && (
                    <Popover open={driverSearchOpen} onOpenChange={setDriverSearchOpen}>
                        <PopoverTrigger asChild>
                            <Button size="sm">
                                <UserPlus className="mr-2 h-4 w-4" />
                                Asignar Motorista
                            </Button>
                        </PopoverTrigger>
                        <PopoverContent className="w-64 p-0" align="start">
                            <Command>
                                <CommandInput placeholder="Buscar motorista..." />
                                <CommandList>
                                    <CommandEmpty>No hay motoristas disponibles.</CommandEmpty>
                                    <CommandGroup>
                                        {availableDrivers.map((driver) => (
                                            <CommandItem
                                                key={driver.id}
                                                value={driver.name}
                                                onSelect={() => {
                                                    onAssignDriver(order.id, driver.id);
                                                    setDriverSearchOpen(false);
                                                }}
                                            >
                                                <User className="mr-2 h-4 w-4" />
                                                <span>{driver.name}</span>
                                            </CommandItem>
                                        ))}
                                    </CommandGroup>
                                </CommandList>
                            </Command>
                        </PopoverContent>
                    </Popover>
                )}
                {canMarkCompleted && (
                    <Button
                        size="sm"
                        onClick={() => onMarkCompleted(order.id)}
                        disabled={isUpdating}
                        className="bg-green-600 hover:bg-green-700"
                    >
                        {isUpdating ? <Loader2 className="mr-2 h-4 w-4 animate-spin" /> : <CheckCircle className="mr-2 h-4 w-4" />}
                        Marcar Completada
                    </Button>
                )}
            </div>

            {/* Status badges */}
            <div className="flex flex-wrap gap-2">
                <StatusBadge status={order.status} configs={ORDER_STATUS_CONFIGS} />
                <StatusBadge status={order.service_type} configs={SERVICE_TYPE_CONFIGS} />
                {order.payment_status && (
                    <StatusBadge status={order.payment_status} configs={PAYMENT_STATUS_CONFIGS} />
                )}
            </div>

            {/* Customer info */}
            <Card>
                <CardHeader className="pb-3">
                    <CardTitle className="flex items-center gap-2 text-base">
                        <User className="h-4 w-4" />
                        Cliente
                    </CardTitle>
                </CardHeader>
                <CardContent className="space-y-2">
                    <p className="font-medium">{customerName}</p>
                    {customerPhone && (
                        <p className="text-sm text-muted-foreground flex items-center gap-2">
                            <Phone className="h-3 w-3" />
                            {customerPhone}
                        </p>
                    )}
                    {customerEmail && (
                        <p className="text-sm text-muted-foreground">{customerEmail}</p>
                    )}
                    {customerSubwayCard && (
                        <p className="text-sm text-muted-foreground flex items-center gap-2 font-mono">
                            <CreditCard className="h-3 w-3" />
                            {customerSubwayCard}
                        </p>
                    )}

                    {order.service_type === 'delivery' && order.delivery_address && deliveryAddressLine && (
                        <div className="mt-3 pt-3 border-t">
                            <p className="text-xs text-muted-foreground mb-1">Direccion de Entrega</p>
                            <div className="flex items-start gap-2">
                                <MapPin className="h-4 w-4 text-muted-foreground mt-0.5" />
                                <div>
                                    {order.delivery_address.label && (
                                        <p className="font-medium">{order.delivery_address.label}</p>
                                    )}
                                    <p className="text-sm">{deliveryAddressLine}</p>
                                    {order.delivery_address.reference && (
                                        <p className="text-sm text-muted-foreground italic mt-1">
                                            Ref: {order.delivery_address.reference}
                                        </p>
                                    )}
                                </div>
                            </div>
                        </div>
                    )}
                </CardContent>
            </Card>

            {/* Driver info */}
            {order.service_type === 'delivery' && (
                <Card>
                    <CardHeader className="pb-3">
                        <CardTitle className="flex items-center gap-2 text-base">
                            <Truck className="h-4 w-4" />
                            Motorista
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        {order.driver ? (
                            <div className="space-y-1">
                                <div className="flex items-center gap-2">
                                    <Users className="h-4 w-4 text-muted-foreground" />
                                    <p className="font-medium">{order.driver.name}</p>
                                </div>
                                {order.driver.phone && (
                                    <p className="text-sm text-muted-foreground flex items-center gap-2">
                                        <Phone className="h-3 w-3" />
                                        {order.driver.phone}
                                    </p>
                                )}
                            </div>
                        ) : (
                            <p className="text-sm text-muted-foreground">Sin motorista asignado</p>
                        )}
                    </CardContent>
                </Card>
            )}

            {/* Order items */}
            <Card>
                <CardHeader className="pb-3">
                    <CardTitle className="flex items-center gap-2 text-base">
                        <ShoppingBag className="h-4 w-4" />
                        Items ({order.items?.length || 0})
                    </CardTitle>
                </CardHeader>
                <CardContent className="space-y-3">
                    {order.items?.map((item) => {
                        const groupedOptions: Record<string, string[]> = {};
                        if (item.options) {
                            for (const opt of item.options) {
                                const sectionName = opt.section_name || 'Opciones';
                                if (!groupedOptions[sectionName]) {
                                    groupedOptions[sectionName] = [];
                                }
                                groupedOptions[sectionName].push(opt.name);
                            }
                        }

                        return (
                            <div
                                key={item.id}
                                className="flex items-start justify-between rounded-lg border bg-muted/50 p-3"
                            >
                                <div className="flex-1">
                                    <div className="flex items-center gap-2">
                                        <Badge variant="secondary" className="text-xs">
                                            x{item.quantity}
                                        </Badge>
                                        <p className="font-medium">{item.name}</p>
                                    </div>
                                    {item.variant && (
                                        <p className="text-xs text-muted-foreground mt-0.5">{item.variant}</p>
                                    )}
                                    {Object.keys(groupedOptions).length > 0 && (
                                        <div className="mt-1 space-y-0.5">
                                            {Object.entries(groupedOptions).map(([sectionName, options]) => (
                                                <p key={sectionName} className="text-xs text-muted-foreground">
                                                    <span className="font-medium">{sectionName}:</span>{' '}
                                                    {options.join(', ')}
                                                </p>
                                            ))}
                                        </div>
                                    )}
                                    {item.notes && (
                                        <p className="mt-1 text-xs italic text-orange-600 dark:text-orange-400">
                                            Nota: {item.notes}
                                        </p>
                                    )}
                                </div>
                                <div className="text-right">
                                    <p className="text-xs text-muted-foreground">
                                        {CURRENCY.symbol}{formatCurrency(item.unit_price, false)} c/u
                                    </p>
                                    <p className="font-medium">
                                        {CURRENCY.symbol}{formatCurrency(item.total_price, false)}
                                    </p>
                                </div>
                            </div>
                        );
                    })}

                    <Separator />

                    {/* Totals */}
                    <div className="space-y-2">
                        {order.subtotal !== undefined && (
                            <div className="flex justify-between text-sm">
                                <span className="text-muted-foreground">Subtotal</span>
                                <span>{CURRENCY.symbol}{formatCurrency(order.subtotal, false)}</span>
                            </div>
                        )}
                        {discountValue > 0 && (
                            <div className="flex justify-between text-sm text-green-600">
                                <span>Descuento</span>
                                <span>-{CURRENCY.symbol}{formatCurrency(discountValue, false)}</span>
                            </div>
                        )}
                        <Separator />
                        <div className="flex justify-between text-lg font-bold">
                            <span>Total</span>
                            <span>{CURRENCY.symbol}{formatCurrency(order.total, false)}</span>
                        </div>
                    </div>
                </CardContent>
            </Card>

            {/* Order notes */}
            {order.notes && (
                <Card>
                    <CardHeader className="pb-3">
                        <CardTitle className="text-base">Notas del Pedido</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="bg-orange-50 dark:bg-orange-950/30 border border-orange-200 dark:border-orange-800 rounded-lg p-3">
                            <p className="text-sm text-orange-800 dark:text-orange-200">{order.notes}</p>
                        </div>
                    </CardContent>
                </Card>
            )}

            {/* Payment info */}
            <Card>
                <CardHeader className="pb-3">
                    <CardTitle className="flex items-center gap-2 text-base">
                        <CreditCard className="h-4 w-4" />
                        Pago
                    </CardTitle>
                </CardHeader>
                <CardContent className="space-y-2">
                    <div className="flex justify-between text-sm">
                        <span className="text-muted-foreground">Metodo</span>
                        <span>{PAYMENT_METHOD_LABELS[order.payment_method] || order.payment_method}</span>
                    </div>
                    {order.payment_status && (
                        <div className="flex justify-between text-sm items-center">
                            <span className="text-muted-foreground">Estado</span>
                            <StatusBadge status={order.payment_status} configs={PAYMENT_STATUS_CONFIGS} className="text-xs" />
                        </div>
                    )}
                </CardContent>
            </Card>

            {/* System info */}
            <Card>
                <CardHeader className="pb-3">
                    <CardTitle className="flex items-center gap-2 text-base">
                        <Clock className="h-4 w-4" />
                        Informacion
                    </CardTitle>
                </CardHeader>
                <CardContent className="space-y-2 text-sm">
                    <div className="flex justify-between">
                        <span className="text-muted-foreground">ID</span>
                        <span className="font-mono">#{order.id}</span>
                    </div>
                    <div className="flex justify-between">
                        <span className="text-muted-foreground">Creado</span>
                        <span>{formatDateTime(order.created_at)}</span>
                    </div>
                    {order.estimated_ready_at && (
                        <div className="flex justify-between">
                            <span className="text-muted-foreground">Estimado</span>
                            <span>{formatDateTime(order.estimated_ready_at)}</span>
                        </div>
                    )}
                    {order.ready_at && (
                        <div className="flex justify-between">
                            <span className="text-muted-foreground">Lista</span>
                            <span>{formatDateTime(order.ready_at)}</span>
                        </div>
                    )}
                </CardContent>
            </Card>
        </div>
    );
}
