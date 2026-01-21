import { Head, Link, router } from '@inertiajs/react';
import {
    AlertCircle,
    Ban,
    Banknote,
    Calendar,
    Check,
    CheckCircle,
    ChefHat,
    ChevronsUpDown,
    Clock,
    CreditCard,
    Eye,
    Filter,
    ListFilter,
    Package,
    ShoppingBag,
    Store,
    Truck,
    Users,
    X,
    XCircle,
} from 'lucide-react';
import { useState } from 'react';

import { DataTable } from '@/components/DataTable';
import { EntityInfoCell } from '@/components/EntityInfoCell';
import { FormSection } from '@/components/form-section';
import { StandardMobileCard } from '@/components/StandardMobileCard';
import { StatusBadge, StatusConfig } from '@/components/status-badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Command, CommandEmpty, CommandGroup, CommandInput, CommandItem, CommandList } from '@/components/ui/command';
import { DatePicker } from '@/components/ui/date-picker';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Sheet, SheetContent, SheetHeader, SheetTitle, SheetTrigger } from '@/components/ui/sheet';
import { Textarea } from '@/components/ui/textarea';
import { cn } from '@/lib/utils';
import { CURRENCY } from '@/constants/ui-constants';
import { useAdminOrderPolling } from '@/hooks/useAdminOrderPolling';
import AppLayout from '@/layouts/app-layout';
import { Filters, Order, PaginatedData, Restaurant } from '@/types';
import { formatCurrency, formatDate } from '@/utils/format';

interface Statistics {
    total_today: number;
    pending: number;
    preparing: number;
    ready: number;
    out_for_delivery: number;
    completed_today: number;
    cancelled_today: number;
    total_sales_today: number;
    cash_sales_today: number;
    card_sales_today: number;
}

interface PollingConfig {
    polling_interval: number;
    enabled: boolean;
}

interface OrdersPageProps {
    orders: PaginatedData<Order>;
    restaurants: Restaurant[];
    statistics: Statistics;
    polling_config: PollingConfig;
    filters: Filters & {
        restaurant_id?: number | null;
        status?: string | null;
        service_type?: string | null;
        date_from?: string | null;
        date_to?: string | null;
    };
}

/**
 * Configuraciones de estado de ordenes
 */
export const ORDER_STATUS_CONFIGS: Record<string, StatusConfig> = {
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
 * Pagina principal de gestion de ordenes (Admin)
 */
export default function OrdersIndex({
    orders,
    restaurants,
    statistics,
    polling_config,
    filters,
}: OrdersPageProps) {
    const [cancelModalOpen, setCancelModalOpen] = useState(false);
    const [selectedOrder, setSelectedOrder] = useState<Order | null>(null);
    const [cancellationReason, setCancellationReason] = useState('');
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [filterSheetOpen, setFilterSheetOpen] = useState(false);
    const [restaurantPopoverOpen, setRestaurantPopoverOpen] = useState(false);

    // Obtener el nombre del restaurante seleccionado
    const selectedRestaurant = filters.restaurant_id
        ? restaurants.find((r) => r.id === Number(filters.restaurant_id))
        : null;

    // Polling para actualizaciones en tiempo real
    useAdminOrderPolling({
        intervalSeconds: polling_config?.polling_interval || 30,
        enabled: polling_config?.enabled ?? true,
        reloadProps: ['orders', 'statistics'],
        restaurantId: filters.restaurant_id ? Number(filters.restaurant_id) : null,
    });

    const handleFilterChange = (key: string, value: string | null) => {
        const params: Record<string, string | number | undefined> = {
            per_page: filters.per_page,
        };
        if (key === 'search' && value) params.search = value;
        else if (filters.search) params.search = filters.search;
        if (key === 'restaurant_id' && value) params.restaurant_id = value;
        else if (filters.restaurant_id) params.restaurant_id = String(filters.restaurant_id);
        if (key === 'status' && value) params.status = value;
        else if (filters.status) params.status = filters.status;
        if (key === 'service_type' && value) params.service_type = value;
        else if (filters.service_type) params.service_type = filters.service_type;
        if (key === 'date_from' && value) params.date_from = value;
        else if (filters.date_from) params.date_from = filters.date_from;
        if (key === 'date_to' && value) params.date_to = value;
        else if (filters.date_to) params.date_to = filters.date_to;

        router.get('/orders', params, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    };

    const handleClearFilters = () => {
        router.get('/orders', { per_page: filters.per_page }, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
        setFilterSheetOpen(false);
    };

    const today = new Date().toISOString().split('T')[0];
    const activeFiltersCount = [
        filters.restaurant_id,
        filters.status,
        filters.service_type,
        filters.date_from !== today ? filters.date_from : null,
        filters.date_to !== today ? filters.date_to : null,
    ].filter(Boolean).length;

    const openCancelModal = (order: Order) => {
        setSelectedOrder(order);
        setCancellationReason('');
        setCancelModalOpen(true);
    };

    const handleCancelOrder = () => {
        if (!selectedOrder || !cancellationReason.trim()) return;

        setIsSubmitting(true);
        router.post(
            `/orders/${selectedOrder.id}/cancel`,
            { cancellation_reason: cancellationReason },
            {
                preserveState: true,
                preserveScroll: true,
                onSuccess: () => {
                    setCancelModalOpen(false);
                    setSelectedOrder(null);
                    setCancellationReason('');
                },
                onFinish: () => setIsSubmitting(false),
            },
        );
    };

    const columns = [
        {
            key: 'order',
            title: 'Orden / Cliente',
            width: 'lg' as const,
            sortable: true,
            render: (order: Order) => (
                <div className="flex items-start gap-3">
                    <div className="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-muted">
                        <Package className="h-4 w-4 text-muted-foreground" />
                    </div>
                    <div className="min-w-0">
                        <p className="font-medium">#{order.order_number}</p>
                        <p className="text-sm text-muted-foreground truncate">{order.customer?.full_name || 'N/A'}</p>
                        <p className="text-xs text-muted-foreground">{formatDate(order.created_at)}</p>
                    </div>
                </div>
            ),
        },
        {
            key: 'restaurant',
            title: 'Restaurante',
            width: 'md' as const,
            render: (order: Order) => <div className="text-sm">{order.restaurant?.name || 'N/A'}</div>,
        },
        {
            key: 'status',
            title: 'Estado / Servicio',
            width: 'md' as const,
            sortable: true,
            render: (order: Order) => (
                <div className="flex flex-col gap-1">
                    <StatusBadge status={order.status} configs={ORDER_STATUS_CONFIGS} className="text-xs" />
                    <div className="flex items-center gap-1.5">
                        <StatusBadge status={order.service_type} configs={SERVICE_TYPE_CONFIGS} className="text-xs" />
                        {order.service_type === 'delivery' && (
                            <span className="text-xs text-muted-foreground">
                                {order.driver ? order.driver.name : 'Sin motorista'}
                            </span>
                        )}
                    </div>
                </div>
            ),
        },
        {
            key: 'total',
            title: 'Total',
            width: 'sm' as const,
            sortable: true,
            render: (order: Order) => (
                <div className="text-sm font-medium">
                    {CURRENCY.symbol}
                    {formatCurrency(order.total, false)}
                </div>
            ),
        },
        {
            key: 'actions',
            title: 'Acciones',
            width: 'sm' as const,
            align: 'right' as const,
            render: (order: Order) => (
                <div className="flex items-center gap-1">
                    <Link href={`/orders/${order.id}`}>
                        <Button variant="ghost" size="sm" title="Ver detalle">
                            <Eye className="h-4 w-4" />
                        </Button>
                    </Link>
                    {order.can_be_cancelled && (
                        <Button
                            variant="ghost"
                            size="sm"
                            className="text-red-600 hover:text-red-700 hover:bg-red-50"
                            title="Cancelar orden"
                            onClick={() => openCancelModal(order)}
                        >
                            <Ban className="h-4 w-4" />
                        </Button>
                    )}
                </div>
            ),
        },
    ];

    const OrderMobileCard = ({ order }: { order: Order }) => (
        <StandardMobileCard
            icon={Package}
            title={`#${order.order_number}`}
            subtitle={order.customer?.full_name || 'N/A'}
            badge={{
                children: <StatusBadge status={order.status} configs={ORDER_STATUS_CONFIGS} showIcon={false} className="text-xs" />,
            }}
            actions={{
                viewHref: `/orders/${order.id}`,
                viewTooltip: 'Ver detalle',
            }}
            dataFields={[
                {
                    label: 'Restaurante',
                    value: order.restaurant?.name || 'N/A',
                },
                {
                    label: 'Tipo',
                    value: <StatusBadge status={order.service_type} configs={SERVICE_TYPE_CONFIGS} className="text-xs" />,
                },
                {
                    label: 'Total',
                    value: `${CURRENCY.symbol}${formatCurrency(order.total, false)}`,
                },
                {
                    label: 'Motorista',
                    value: order.driver?.name || 'Sin asignar',
                    condition: order.service_type === 'delivery',
                },
                {
                    label: 'Fecha',
                    value: formatDate(order.created_at),
                },
            ]}
        />
    );

    return (
        <AppLayout>
            <Head title="Gestion de Ordenes" />

            <div className="flex h-full flex-1 flex-col gap-6 p-6">
                {/* Cards de Estadísticas de HOY */}
                <div className="grid gap-4 lg:grid-cols-2">
                    {/* Estado de Ordenes Activas */}
                    <Card>
                        <CardHeader className="pb-3">
                            <div className="flex items-center justify-between">
                                <CardTitle className="text-base font-medium">
                                    Estado de Ordenes - {new Date().toLocaleDateString('es-GT', { day: 'numeric', month: 'short' })}
                                </CardTitle>
                                <div className="flex items-center gap-1.5 text-xs text-green-600 dark:text-green-400">
                                    <span className="relative flex h-2 w-2">
                                        <span className="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                                        <span className="relative inline-flex rounded-full h-2 w-2 bg-green-500"></span>
                                    </span>
                                    Online
                                </div>
                            </div>
                        </CardHeader>
                        <CardContent>
                            <div className="grid grid-cols-4 gap-4">
                                <div className="text-center">
                                    <div className="mx-auto mb-2 flex h-10 w-10 items-center justify-center rounded-full bg-yellow-100 dark:bg-yellow-900/50">
                                        <Clock className="h-5 w-5 text-yellow-600 dark:text-yellow-400" />
                                    </div>
                                    <div className="text-2xl font-bold">{statistics.pending}</div>
                                    <div className="text-xs text-muted-foreground">Pendientes</div>
                                </div>
                                <div className="text-center">
                                    <div className="mx-auto mb-2 flex h-10 w-10 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-900/50">
                                        <ChefHat className="h-5 w-5 text-blue-600 dark:text-blue-400" />
                                    </div>
                                    <div className="text-2xl font-bold">{statistics.preparing}</div>
                                    <div className="text-xs text-muted-foreground">Preparando</div>
                                </div>
                                <div className="text-center">
                                    <div className="mx-auto mb-2 flex h-10 w-10 items-center justify-center rounded-full bg-orange-100 dark:bg-orange-900/50">
                                        <Truck className="h-5 w-5 text-orange-600 dark:text-orange-400" />
                                    </div>
                                    <div className="text-2xl font-bold">{statistics.out_for_delivery}</div>
                                    <div className="text-xs text-muted-foreground">En Camino</div>
                                </div>
                                <div className="text-center">
                                    <div className="mx-auto mb-2 flex h-10 w-10 items-center justify-center rounded-full bg-green-100 dark:bg-green-900/50">
                                        <CheckCircle className="h-5 w-5 text-green-600 dark:text-green-400" />
                                    </div>
                                    <div className="text-2xl font-bold">{statistics.completed_today}</div>
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
                                    {CURRENCY.symbol}{formatCurrency(statistics.total_sales_today, false)}
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
                                            {CURRENCY.symbol}{formatCurrency(statistics.cash_sales_today, false)}
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
                                            {CURRENCY.symbol}{formatCurrency(statistics.card_sales_today, false)}
                                        </div>
                                        <div className="text-xs text-muted-foreground">Tarjeta</div>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <DataTable
                    title="Ordenes"
                    data={orders}
                    columns={columns}
                    filters={filters}
                    searchPlaceholder="Buscar por # orden, cliente..."
                    renderMobileCard={(order) => <OrderMobileCard order={order} />}
                    routeName="/orders"
                    breakpoint="lg"
                    headerActions={
                        <Sheet open={filterSheetOpen} onOpenChange={setFilterSheetOpen}>
                            <SheetTrigger asChild>
                                <Button variant="outline" size="sm" className="gap-2">
                                    <Filter className="h-4 w-4" />
                                    Filtros
                                    {activeFiltersCount > 0 && (
                                        <span className="ml-1 flex h-5 w-5 items-center justify-center rounded-full bg-primary text-xs text-primary-foreground">
                                            {activeFiltersCount}
                                        </span>
                                    )}
                                </Button>
                            </SheetTrigger>
                            <SheetContent className="w-full sm:max-w-md overflow-y-auto">
                                <SheetHeader>
                                    <SheetTitle className="flex items-center gap-2">
                                        <Filter className="h-5 w-5" />
                                        Filtros de Órdenes
                                    </SheetTitle>
                                </SheetHeader>

                                <div className="mt-6 space-y-6">
                                    {/* Sección: Restaurante */}
                                    <FormSection icon={Store} title="Restaurante">
                                        <Popover open={restaurantPopoverOpen} onOpenChange={setRestaurantPopoverOpen}>
                                            <PopoverTrigger asChild>
                                                <Button
                                                    variant="outline"
                                                    role="combobox"
                                                    aria-expanded={restaurantPopoverOpen}
                                                    className="w-full justify-between font-normal"
                                                >
                                                    <span className="truncate">
                                                        {selectedRestaurant?.name || 'Todos los restaurantes'}
                                                    </span>
                                                    <ChevronsUpDown className="ml-2 h-4 w-4 shrink-0 opacity-50" />
                                                </Button>
                                            </PopoverTrigger>
                                            <PopoverContent className="w-[350px] p-0" align="start">
                                                <Command>
                                                    <CommandInput placeholder="Buscar restaurante..." />
                                                    <CommandList>
                                                        <CommandEmpty>No se encontró ningún restaurante.</CommandEmpty>
                                                        <CommandGroup>
                                                            <CommandItem
                                                                value="all"
                                                                onSelect={() => {
                                                                    handleFilterChange('restaurant_id', null);
                                                                    setRestaurantPopoverOpen(false);
                                                                }}
                                                            >
                                                                <Check
                                                                    className={cn(
                                                                        'mr-2 h-4 w-4',
                                                                        !filters.restaurant_id ? 'opacity-100' : 'opacity-0',
                                                                    )}
                                                                />
                                                                Todos los restaurantes
                                                            </CommandItem>
                                                            {restaurants.map((restaurant) => (
                                                                <CommandItem
                                                                    key={restaurant.id}
                                                                    value={restaurant.name}
                                                                    onSelect={() => {
                                                                        handleFilterChange('restaurant_id', restaurant.id.toString());
                                                                        setRestaurantPopoverOpen(false);
                                                                    }}
                                                                >
                                                                    <Check
                                                                        className={cn(
                                                                            'mr-2 h-4 w-4',
                                                                            filters.restaurant_id === restaurant.id
                                                                                ? 'opacity-100'
                                                                                : 'opacity-0',
                                                                        )}
                                                                    />
                                                                    {restaurant.name}
                                                                </CommandItem>
                                                            ))}
                                                        </CommandGroup>
                                                    </CommandList>
                                                </Command>
                                            </PopoverContent>
                                        </Popover>
                                    </FormSection>

                                    {/* Sección: Filtros de Estado */}
                                    <FormSection icon={ListFilter} title="Estado y Tipo">
                                        <div className="space-y-2">
                                            <Label className="text-sm font-medium">Estado de Orden</Label>
                                            <Select
                                                value={filters.status || 'all'}
                                                onValueChange={(value) => handleFilterChange('status', value === 'all' ? null : value)}
                                            >
                                                <SelectTrigger className="w-full">
                                                    <SelectValue placeholder="Todos los estados" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem value="all">Todos los estados</SelectItem>
                                                    <SelectItem value="pending">Pendiente</SelectItem>
                                                    <SelectItem value="confirmed">Confirmado</SelectItem>
                                                    <SelectItem value="preparing">Preparando</SelectItem>
                                                    <SelectItem value="ready">Listo</SelectItem>
                                                    <SelectItem value="out_for_delivery">En Camino</SelectItem>
                                                    <SelectItem value="delivered">Entregado</SelectItem>
                                                    <SelectItem value="completed">Completado</SelectItem>
                                                    <SelectItem value="cancelled">Cancelado</SelectItem>
                                                </SelectContent>
                                            </Select>
                                        </div>

                                        <div className="space-y-2">
                                            <Label className="text-sm font-medium">Tipo de Servicio</Label>
                                            <Select
                                                value={filters.service_type || 'all'}
                                                onValueChange={(value) => handleFilterChange('service_type', value === 'all' ? null : value)}
                                            >
                                                <SelectTrigger className="w-full">
                                                    <SelectValue placeholder="Todos los tipos" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem value="all">Todos los tipos</SelectItem>
                                                    <SelectItem value="delivery">Delivery</SelectItem>
                                                    <SelectItem value="pickup">Pickup</SelectItem>
                                                </SelectContent>
                                            </Select>
                                        </div>
                                    </FormSection>

                                    {/* Sección: Rango de Fechas */}
                                    <FormSection icon={Calendar} title="Rango de Fechas">
                                        <div className="grid grid-cols-2 gap-4">
                                            <div className="space-y-2">
                                                <Label className="text-sm font-medium">Desde</Label>
                                                <DatePicker
                                                    value={filters.date_from || undefined}
                                                    onChange={(date) => handleFilterChange('date_from', date ? date.toISOString().split('T')[0] : null)}
                                                />
                                            </div>
                                            <div className="space-y-2">
                                                <Label className="text-sm font-medium">Hasta</Label>
                                                <DatePicker
                                                    value={filters.date_to || undefined}
                                                    onChange={(date) => handleFilterChange('date_to', date ? date.toISOString().split('T')[0] : null)}
                                                />
                                            </div>
                                        </div>
                                    </FormSection>

                                    {/* Botón limpiar filtros */}
                                    <div className="pt-2">
                                        <Button
                                            variant="outline"
                                            className="w-full gap-2"
                                            onClick={handleClearFilters}
                                        >
                                            <X className="h-4 w-4" />
                                            Limpiar todos los filtros
                                        </Button>
                                    </div>
                                </div>
                            </SheetContent>
                        </Sheet>
                    }
                />

                {/* Modal de cancelar orden */}
                <Dialog open={cancelModalOpen} onOpenChange={setCancelModalOpen}>
                    <DialogContent className="sm:max-w-md">
                        <DialogHeader>
                            <DialogTitle className="flex items-center gap-2 text-red-600">
                                <Ban className="h-5 w-5" />
                                Cancelar Orden
                            </DialogTitle>
                            <DialogDescription>
                                ¿Estás seguro de que deseas cancelar la orden #{selectedOrder?.order_number}?
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
            </div>
        </AppLayout>
    );
}
