import { Head, Link, router } from '@inertiajs/react';
import {
    AlertCircle,
    ArrowLeft,
    Ban,
    Building2,
    Calendar,
    Check,
    CheckCircle,
    ChevronsUpDown,
    Clock,
    CreditCard,
    MapPin,
    Package,
    Phone,
    Printer,
    ShoppingBag,
    Truck,
    User,
    Users,
    XCircle,
} from 'lucide-react';
import { useMemo, useState } from 'react';

import { printOrder } from '@/components/orders/PrintComanda';

import { StatusBadge, StatusConfig } from '@/components/status-badge';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Command, CommandEmpty, CommandGroup, CommandInput, CommandItem, CommandList } from '@/components/ui/command';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { Separator } from '@/components/ui/separator';
import { Textarea } from '@/components/ui/textarea';
import { CURRENCY } from '@/constants/ui-constants';
import AppLayout from '@/layouts/app-layout';
import { Order, OrderStatusHistory } from '@/types';
import { formatCurrency } from '@/utils/format';

interface Restaurant {
    id: number;
    name: string;
}

interface ShowOrderProps {
    order: Order;
    restaurants?: Restaurant[];
    can_change_restaurant?: boolean;
    can_cancel?: boolean;
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
 * Configuraciones de metodo de pago
 */
const PAYMENT_METHOD_LABELS: Record<string, string> = {
    cash: 'Efectivo',
    card: 'Tarjeta',
    online: 'En Linea',
};

/**
 * Configuraciones de estado de pago
 */
const PAYMENT_STATUS_CONFIGS: Record<string, StatusConfig> = {
    pending: {
        color: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300 border border-yellow-200 dark:border-yellow-700',
        text: 'Pendiente',
        icon: <Clock className="h-3 w-3" />,
    },
    paid: {
        color: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300 border border-green-200 dark:border-green-700',
        text: 'Pagado',
        icon: <CheckCircle className="h-3 w-3" />,
    },
    refunded: {
        color: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300 border border-red-200 dark:border-red-700',
        text: 'Reembolsado',
        icon: <XCircle className="h-3 w-3" />,
    },
    default: {
        color: 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300 border border-gray-200 dark:border-gray-600',
        text: 'Desconocido',
        icon: <AlertCircle className="h-3 w-3" />,
    },
};

const formatDate = (dateString: string | null): string => {
    if (!dateString) return 'N/A';
    return new Date(dateString).toLocaleDateString('es-GT', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        timeZone: 'America/Guatemala',
    });
};

const formatTime = (dateString: string | null): string => {
    if (!dateString) return '';
    return new Date(dateString).toLocaleTimeString('es-GT', {
        hour: '2-digit',
        minute: '2-digit',
        timeZone: 'America/Guatemala',
    });
};

interface DeliveryAddressSnapshot {
    label?: string;
    address_line?: string;
    delivery_notes?: string;
    zone?: string;
}

const getDeliveryAddressDisplay = (address: string | DeliveryAddressSnapshot | null | undefined): { main: string; notes?: string } | null => {
    if (!address) return null;
    if (typeof address === 'string') return { main: address };
    const mainParts: string[] = [];
    if (address.label) mainParts.push(address.label);
    if (address.address_line) mainParts.push(address.address_line);
    if (address.zone) mainParts.push(`Zona: ${address.zone}`);
    return mainParts.length > 0 ? { main: mainParts.join(' - '), notes: address.delivery_notes || undefined } : null;
};

/**
 * Componente de Timeline de estados
 */
const OrderTimeline = ({ statusHistory }: { statusHistory: OrderStatusHistory[] }) => {
    const sortedHistory = [...statusHistory].sort(
        (a, b) => new Date(a.created_at).getTime() - new Date(b.created_at).getTime(),
    );

    const getChangedByLabel = (type: string): string => {
        switch (type) {
            case 'user':
                return 'Usuario';
            case 'customer':
                return 'Cliente';
            case 'driver':
                return 'Motorista';
            case 'system':
                return 'Sistema';
            default:
                return 'Desconocido';
        }
    };

    return (
        <div className="space-y-4">
            {sortedHistory.map((history, index) => (
                <div key={history.id} className="flex gap-4">
                    <div className="flex flex-col items-center">
                        <div
                            className={`flex h-8 w-8 items-center justify-center rounded-full ${
                                index === sortedHistory.length - 1 ? 'bg-primary text-primary-foreground' : 'bg-muted'
                            }`}
                        >
                            {ORDER_STATUS_CONFIGS[history.status]?.icon || <Clock className="h-4 w-4" />}
                        </div>
                        {index < sortedHistory.length - 1 && <div className="h-full w-0.5 bg-muted" />}
                    </div>
                    <div className="flex-1 pb-4">
                        <div className="flex items-center gap-2">
                            <StatusBadge status={history.status} configs={ORDER_STATUS_CONFIGS} className="text-xs" />
                            <span className="text-xs text-muted-foreground">{formatDate(history.created_at)}</span>
                        </div>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Por: {getChangedByLabel(history.changed_by_type)}
                        </p>
                        {history.notes && <p className="mt-1 text-sm">{history.notes}</p>}
                    </div>
                </div>
            ))}
        </div>
    );
};

export default function ShowOrder({
    order,
    restaurants = [],
    can_change_restaurant = false,
    can_cancel = false,
}: ShowOrderProps) {
    const [changeRestaurantModalOpen, setChangeRestaurantModalOpen] = useState(false);
    const [selectedRestaurantId, setSelectedRestaurantId] = useState<string>('');
    const [restaurantSearch, setRestaurantSearch] = useState('');
    const [restaurantComboboxOpen, setRestaurantComboboxOpen] = useState(false);
    const [cancelModalOpen, setCancelModalOpen] = useState(false);
    const [cancellationReason, setCancellationReason] = useState('');
    const [isSubmitting, setIsSubmitting] = useState(false);
    const handlePrint = () => {
        printOrder(order as any);
    };

    // Filtrar restaurantes disponibles (excluyendo el actual) y por búsqueda
    const filteredRestaurants = useMemo(() => {
        const available = restaurants.filter((r) => r.id !== order.restaurant?.id);
        if (!restaurantSearch) return available;
        const searchLower = restaurantSearch.toLowerCase();
        return available.filter((r) => r.name.toLowerCase().includes(searchLower));
    }, [restaurants, order.restaurant?.id, restaurantSearch]);

    const selectedRestaurant = restaurants.find((r) => r.id.toString() === selectedRestaurantId);

    const handleChangeRestaurant = () => {
        if (!selectedRestaurantId) return;

        router.post(
            `/orders/${order.id}/change-restaurant`,
            { restaurant_id: selectedRestaurantId },
            {
                preserveState: true,
                preserveScroll: true,
                onSuccess: () => {
                    setChangeRestaurantModalOpen(false);
                    setSelectedRestaurantId('');
                },
            },
        );
    };

    const handleCancelOrder = () => {
        if (!cancellationReason.trim()) return;

        setIsSubmitting(true);
        router.post(
            `/orders/${order.id}/cancel`,
            { cancellation_reason: cancellationReason },
            {
                preserveState: true,
                preserveScroll: true,
                onSuccess: () => {
                    setCancelModalOpen(false);
                    setCancellationReason('');
                },
                onFinish: () => setIsSubmitting(false),
            },
        );
    };

    // Verificar si la orden puede ser cancelada (no completada, cancelada o reembolsada)
    const canBeCancelled = can_cancel && !['completed', 'cancelled', 'refunded'].includes(order.status);

    return (
        <AppLayout>
            <Head title={`Orden #${order.order_number}`} />

            <div className="mx-auto flex h-full w-full flex-1 flex-col gap-6 p-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-3">
                        <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10">
                            <Package className="h-5 w-5 text-primary" />
                        </div>
                        <div>
                            <h1 className="text-3xl font-bold tracking-tight">Orden #{order.order_number}</h1>
                            <p className="text-muted-foreground">{formatDate(order.created_at)}</p>
                        </div>
                    </div>
                    <div className="flex items-center gap-2">
                        <Button variant="outline" onClick={handlePrint}>
                            <Printer className="mr-2 h-4 w-4" />
                            Imprimir Comanda
                        </Button>
                        {canBeCancelled && (
                            <Button variant="destructive" onClick={() => setCancelModalOpen(true)}>
                                <Ban className="mr-2 h-4 w-4" />
                                Cancelar Orden
                            </Button>
                        )}
                        <Link href="/orders">
                            <Button variant="outline">
                                <ArrowLeft className="mr-2 h-4 w-4" />
                                Volver
                            </Button>
                        </Link>
                    </div>
                </div>

                {/* Estado y tipo de servicio */}
                <div className="flex flex-wrap gap-4">
                    <StatusBadge status={order.status} configs={ORDER_STATUS_CONFIGS} />
                    <StatusBadge status={order.service_type} configs={SERVICE_TYPE_CONFIGS} />
                    <StatusBadge status={order.payment_status} configs={PAYMENT_STATUS_CONFIGS} />
                </div>

                <div className="grid gap-6 lg:grid-cols-3">
                    {/* Columna principal */}
                    <div className="space-y-6 lg:col-span-2">
                        {/* Informacion del Cliente */}
                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2">
                                    <User className="h-5 w-5" />
                                    Informacion del Cliente
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                {order.customer ? (
                                    <>
                                        <div className="grid grid-cols-2 gap-4">
                                            <div>
                                                <span className="text-xs text-muted-foreground">Nombre</span>
                                                <p className="font-medium">{order.customer.full_name}</p>
                                            </div>
                                            <div>
                                                <span className="text-xs text-muted-foreground">Email</span>
                                                <p className="font-medium">{order.customer.email}</p>
                                            </div>
                                        </div>
                                        {order.customer.phone && (
                                            <div>
                                                <span className="text-xs text-muted-foreground">Telefono</span>
                                                <div className="flex items-center gap-2">
                                                    <Phone className="h-4 w-4 text-muted-foreground" />
                                                    <p className="font-medium">{order.customer.phone}</p>
                                                </div>
                                            </div>
                                        )}
                                    </>
                                ) : (
                                    <p className="text-muted-foreground">Informacion del cliente no disponible</p>
                                )}

                                {order.service_type === 'delivery' && order.delivery_address && (() => {
                                    const addressInfo = getDeliveryAddressDisplay(order.delivery_address);
                                    if (!addressInfo) return null;
                                    return (
                                        <div>
                                            <span className="text-xs text-muted-foreground">Direccion de Entrega</span>
                                            <div className="flex items-start gap-2 mt-1">
                                                <MapPin className="h-4 w-4 text-muted-foreground mt-0.5" />
                                                <div>
                                                    <p className="font-medium">{addressInfo.main}</p>
                                                    {addressInfo.notes && (
                                                        <p className="text-sm text-muted-foreground mt-1">Notas: {addressInfo.notes}</p>
                                                    )}
                                                </div>
                                            </div>
                                        </div>
                                    );
                                })()}
                            </CardContent>
                        </Card>

                        {/* Items del Pedido */}
                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2">
                                    <ShoppingBag className="h-5 w-5" />
                                    Items del Pedido ({order.items?.length || 0})
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                {order.items && order.items.length > 0 ? (
                                    <div className="space-y-4">
                                        {order.items.map((item) => {
                                            // Agrupar opciones por sección
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
                                                <div key={item.id} className="rounded-lg border bg-muted/50 p-4">
                                                    <div className="flex items-center gap-2">
                                                        <Badge className="bg-yellow-500 hover:bg-yellow-500 text-black text-sm font-semibold">
                                                            x{item.quantity}
                                                        </Badge>
                                                        <p className="font-semibold text-base">{item.name}</p>
                                                    </div>
                                                    {item.variant && (
                                                        <p className="text-sm text-muted-foreground mt-1">{item.variant}</p>
                                                    )}
                                                    {Object.keys(groupedOptions).length > 0 && (
                                                        <div className="mt-3 space-y-1">
                                                            {Object.entries(groupedOptions).map(([sectionName, options]) => (
                                                                <p key={sectionName} className="text-sm text-muted-foreground">
                                                                    <span className="font-medium text-foreground">{sectionName}:</span>{' '}
                                                                    {options.join(', ')}
                                                                </p>
                                                            ))}
                                                        </div>
                                                    )}
                                                    {item.notes && (
                                                        <p className="mt-3 text-sm italic text-orange-600 dark:text-orange-400">
                                                            Nota: {item.notes}
                                                        </p>
                                                    )}
                                                    <div className="mt-3 pt-3 border-t border-dashed flex justify-between text-sm">
                                                        <span className="text-muted-foreground">
                                                            {item.name}: {CURRENCY.symbol}{formatCurrency(item.unit_price, false)}
                                                        </span>
                                                        <span className="font-medium">
                                                            {CURRENCY.symbol}{formatCurrency(item.total_price, false)}
                                                        </span>
                                                    </div>
                                                </div>
                                            );
                                        })}

                                        <Separator />

                                        {/* Totales */}
                                        <div className="space-y-2">
                                            <div className="flex justify-between text-sm">
                                                <span className="text-muted-foreground">Subtotal</span>
                                                <span>
                                                    {CURRENCY.symbol}
                                                    {formatCurrency(order.subtotal, false)}
                                                </span>
                                            </div>
                                            {order.delivery_fee > 0 && (
                                                <div className="flex justify-between text-sm">
                                                    <span className="text-muted-foreground">Envio</span>
                                                    <span>
                                                        {CURRENCY.symbol}
                                                        {formatCurrency(order.delivery_fee, false)}
                                                    </span>
                                                </div>
                                            )}
                                            {order.discount > 0 && (
                                                <div className="flex justify-between text-sm text-green-600">
                                                    <span>Descuento</span>
                                                    <span>
                                                        -{CURRENCY.symbol}
                                                        {formatCurrency(order.discount, false)}
                                                    </span>
                                                </div>
                                            )}
                                            <Separator />
                                            <div className="flex justify-between text-lg font-bold">
                                                <span>Total</span>
                                                <span>
                                                    {CURRENCY.symbol}
                                                    {formatCurrency(order.total, false)}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                ) : (
                                    <p className="text-center text-sm text-muted-foreground py-4">No hay items en esta orden</p>
                                )}
                            </CardContent>
                        </Card>

                        {/* Notas */}
                        {order.notes && (
                            <Card>
                                <CardHeader>
                                    <CardTitle>Notas del Pedido</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <p className="text-sm">{order.notes}</p>
                                </CardContent>
                            </Card>
                        )}
                    </div>

                    {/* Columna lateral */}
                    <div className="space-y-6">
                        {/* Restaurante */}
                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2">
                                    <Building2 className="h-5 w-5" />
                                    Restaurante
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                {order.restaurant ? (
                                    <div className="space-y-2">
                                        <p className="font-medium">{order.restaurant.name}</p>
                                        <p className="text-sm text-muted-foreground">{order.restaurant.address}</p>
                                        {order.restaurant.phone && (
                                            <div className="flex items-center gap-2">
                                                <Phone className="h-3 w-3 text-muted-foreground" />
                                                <span className="text-sm">{order.restaurant.phone}</span>
                                            </div>
                                        )}
                                        {can_change_restaurant && restaurants.length > 0 && (
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                className="mt-3 w-full"
                                                onClick={() => setChangeRestaurantModalOpen(true)}
                                            >
                                                Cambiar Restaurante
                                            </Button>
                                        )}
                                    </div>
                                ) : (
                                    <p className="text-sm text-muted-foreground">Informacion no disponible</p>
                                )}
                            </CardContent>
                        </Card>

                        {/* Motorista */}
                        {order.service_type === 'delivery' && (
                            <Card>
                                <CardHeader>
                                    <CardTitle className="flex items-center gap-2">
                                        <Truck className="h-5 w-5" />
                                        Motorista
                                    </CardTitle>
                                </CardHeader>
                                <CardContent>
                                    {order.driver ? (
                                        <div className="space-y-2">
                                            <div className="flex items-center gap-2">
                                                <Users className="h-4 w-4 text-muted-foreground" />
                                                <p className="font-medium">{order.driver.name}</p>
                                            </div>
                                            {order.driver.phone && (
                                                <div className="flex items-center gap-2">
                                                    <Phone className="h-3 w-3 text-muted-foreground" />
                                                    <span className="text-sm">{order.driver.phone}</span>
                                                </div>
                                            )}
                                            {order.assigned_to_driver_at && (
                                                <p className="text-xs text-muted-foreground">
                                                    Asignado: {formatDate(order.assigned_to_driver_at)}
                                                </p>
                                            )}
                                            {order.picked_up_at && (
                                                <p className="text-xs text-muted-foreground">
                                                    Recogido: {formatDate(order.picked_up_at)}
                                                </p>
                                            )}
                                        </div>
                                    ) : (
                                        <p className="text-sm text-muted-foreground">Sin motorista asignado</p>
                                    )}
                                </CardContent>
                            </Card>
                        )}

                        {/* Pago */}
                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2">
                                    <CreditCard className="h-5 w-5" />
                                    Pago
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-2">
                                <div className="flex justify-between">
                                    <span className="text-sm text-muted-foreground">Metodo</span>
                                    <span className="text-sm">{PAYMENT_METHOD_LABELS[order.payment_method] || order.payment_method}</span>
                                </div>
                                <div className="flex justify-between">
                                    <span className="text-sm text-muted-foreground">Estado</span>
                                    <StatusBadge status={order.payment_status} configs={PAYMENT_STATUS_CONFIGS} className="text-xs" />
                                </div>
                            </CardContent>
                        </Card>

                        {/* Timeline de Estados */}
                        {order.status_history && order.status_history.length > 0 && (
                            <Card>
                                <CardHeader>
                                    <CardTitle className="flex items-center gap-2">
                                        <Calendar className="h-5 w-5" />
                                        Historial de Estados
                                    </CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <OrderTimeline statusHistory={order.status_history} />
                                </CardContent>
                            </Card>
                        )}

                        {/* Informacion del Sistema */}
                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2">
                                    <Clock className="h-5 w-5" />
                                    Informacion del Sistema
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-2">
                                <div className="flex justify-between text-sm">
                                    <span className="text-muted-foreground">ID</span>
                                    <span className="font-mono">#{order.id}</span>
                                </div>
                                <div className="flex justify-between text-sm">
                                    <span className="text-muted-foreground">Creado</span>
                                    <span>{formatDate(order.created_at)}</span>
                                </div>
                                <div className="flex justify-between text-sm">
                                    <span className="text-muted-foreground">Actualizado</span>
                                    <span>{formatDate(order.updated_at)}</span>
                                </div>
                                {order.completed_at && (
                                    <div className="flex justify-between text-sm">
                                        <span className="text-muted-foreground">Completado</span>
                                        <span>{formatDate(order.completed_at)}</span>
                                    </div>
                                )}
                                {order.cancelled_at && (
                                    <>
                                        <div className="flex justify-between text-sm">
                                            <span className="text-muted-foreground">Cancelado</span>
                                            <span>{formatDate(order.cancelled_at)}</span>
                                        </div>
                                        {order.cancellation_reason && (
                                            <div className="mt-2">
                                                <span className="text-xs text-muted-foreground">Razon de cancelacion:</span>
                                                <p className="text-sm">{order.cancellation_reason}</p>
                                            </div>
                                        )}
                                    </>
                                )}
                            </CardContent>
                        </Card>
                    </div>
                </div>

                {/* Modal de cancelar orden */}
                <Dialog open={cancelModalOpen} onOpenChange={setCancelModalOpen}>
                    <DialogContent className="sm:max-w-md">
                        <DialogHeader>
                            <DialogTitle className="flex items-center gap-2 text-red-600">
                                <Ban className="h-5 w-5" />
                                Cancelar Orden
                            </DialogTitle>
                            <DialogDescription>
                                ¿Estás seguro de que deseas cancelar la orden #{order.order_number}?
                                Esta acción no se puede deshacer.
                            </DialogDescription>
                        </DialogHeader>

                        <div className="space-y-4">
                            <div className="space-y-2">
                                <Label htmlFor="cancellation_reason">Razón de cancelación *</Label>
                                <Textarea
                                    id="cancellation_reason"
                                    placeholder="Escribe la razón por la que se cancela esta orden..."
                                    value={cancellationReason}
                                    onChange={(e) => setCancellationReason(e.target.value)}
                                    rows={3}
                                    maxLength={500}
                                />
                                <p className="text-xs text-muted-foreground text-right">
                                    {cancellationReason.length}/500
                                </p>
                            </div>
                        </div>

                        <DialogFooter>
                            <Button variant="outline" onClick={() => setCancelModalOpen(false)} disabled={isSubmitting}>
                                Volver
                            </Button>
                            <Button
                                variant="destructive"
                                onClick={handleCancelOrder}
                                disabled={!cancellationReason.trim() || isSubmitting}
                            >
                                {isSubmitting ? 'Cancelando...' : 'Confirmar Cancelación'}
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>

                {/* Modal para cambiar restaurante */}
                <Dialog
                    open={changeRestaurantModalOpen}
                    onOpenChange={(open) => {
                        setChangeRestaurantModalOpen(open);
                        if (!open) {
                            setRestaurantSearch('');
                            setSelectedRestaurantId('');
                            setRestaurantComboboxOpen(false);
                        }
                    }}
                >
                    <DialogContent className="sm:max-w-md">
                        <DialogHeader>
                            <DialogTitle>Cambiar Restaurante</DialogTitle>
                            <DialogDescription>
                                Selecciona el nuevo restaurante para la orden #{order.order_number}
                            </DialogDescription>
                        </DialogHeader>

                        <div className="space-y-4">
                            <Popover open={restaurantComboboxOpen} onOpenChange={setRestaurantComboboxOpen}>
                                <PopoverTrigger asChild>
                                    <Button
                                        variant="outline"
                                        role="combobox"
                                        aria-expanded={restaurantComboboxOpen}
                                        className="w-full justify-between"
                                    >
                                        <div className="flex items-center gap-2">
                                            <Building2 className="h-4 w-4 text-muted-foreground" />
                                            {selectedRestaurant ? (
                                                <span>{selectedRestaurant.name}</span>
                                            ) : (
                                                <span className="text-muted-foreground">Buscar restaurante...</span>
                                            )}
                                        </div>
                                        <ChevronsUpDown className="ml-2 h-4 w-4 shrink-0 opacity-50" />
                                    </Button>
                                </PopoverTrigger>
                                <PopoverContent className="w-full p-0" align="start">
                                    <Command shouldFilter={false}>
                                        <CommandInput
                                            placeholder="Buscar restaurante..."
                                            value={restaurantSearch}
                                            onValueChange={setRestaurantSearch}
                                        />
                                        <CommandList>
                                            <CommandEmpty>No se encontraron restaurantes</CommandEmpty>
                                            <CommandGroup>
                                                {filteredRestaurants.map((restaurant) => (
                                                    <CommandItem
                                                        key={restaurant.id}
                                                        value={restaurant.id.toString()}
                                                        onSelect={(value) => {
                                                            setSelectedRestaurantId(value);
                                                            setRestaurantComboboxOpen(false);
                                                            setRestaurantSearch('');
                                                        }}
                                                    >
                                                        <Check
                                                            className={`mr-2 h-4 w-4 ${
                                                                selectedRestaurantId === restaurant.id.toString()
                                                                    ? 'opacity-100'
                                                                    : 'opacity-0'
                                                            }`}
                                                        />
                                                        <Building2 className="mr-2 h-4 w-4 text-muted-foreground" />
                                                        {restaurant.name}
                                                    </CommandItem>
                                                ))}
                                            </CommandGroup>
                                        </CommandList>
                                    </Command>
                                </PopoverContent>
                            </Popover>

                            <p className="text-sm text-muted-foreground">
                                Nota: Al cambiar el restaurante, se eliminara el motorista asignado (si existe).
                            </p>

                            <div className="flex justify-end gap-2">
                                <Button variant="outline" onClick={() => setChangeRestaurantModalOpen(false)}>
                                    Cancelar
                                </Button>
                                <Button onClick={handleChangeRestaurant} disabled={!selectedRestaurantId}>
                                    Cambiar
                                </Button>
                            </div>
                        </div>
                    </DialogContent>
                </Dialog>

            </div>
        </AppLayout>
    );
}
