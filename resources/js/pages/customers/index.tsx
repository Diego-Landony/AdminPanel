import { type BreadcrumbItem } from '@/types';
import { Head, router, Link } from '@inertiajs/react';
import React, { useState, useEffect, useCallback } from 'react';

import AppLayout from '@/layouts/app-layout';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';

import { Plus, Search, Users, Clock, Circle, ArrowUp, ArrowDown, ArrowUpDown, CreditCard, RefreshCw, Star, Crown, Medal, Award, MapPin } from 'lucide-react';
import {
    Pagination,
    PaginationContent,
    PaginationEllipsis,
    PaginationItem,
    PaginationLink,
    PaginationNext,
    PaginationPrevious,
} from "@/components/ui/pagination";
import { UsersSkeleton } from '@/components/skeletons';

/**
 * Breadcrumbs para la navegación de clientes
 */
const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Clientes',
        href: '/customers',
    },
    {
        title: 'Gestión de clientes',
        href: '/customers',
    },
];

/**
 * Interfaz para los datos del cliente
 */
interface Customer {
    id: number;
    full_name: string;
    email: string;
    subway_card: string;
    birth_date: string;
    gender: string | null;
    client_type: string | null;
    customer_type: {
        id: number;
        name: string;
        display_name: string;
        color: string | null;
        multiplier: number;
    } | null;
    phone: string | null;
    location: string | null;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
    last_activity: string | null;
    last_purchase: string | null;
    puntos: number;
    puntos_updated_at: string | null;
    is_online: boolean;
    status: string;
}

/**
 * Interfaz para las estadísticas de tipos de clientes
 */
interface CustomerTypeStat {
    id: number;
    name: string;
    display_name: string;
    color: string;
    count: number;
}

/**
 * Interfaz para las props de la página
 */
interface CustomersPageProps {
    customers: {
        data: Customer[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        from: number;
        to: number;
    };
    total_customers: number;
    verified_customers: number;
    online_customers: number;
    premium_customers: number;
    vip_customers: number;
    customer_type_stats: CustomerTypeStat[];
    filters: {
        search: string | null;
        per_page: number;
        sort_field?: string;
        sort_direction?: 'asc' | 'desc';
    };
}

/**
 * Obtiene el color del badge según el estado del cliente
 */
const getStatusColor = (status: string): string => {
    switch (status) {
        case 'online':
            return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300 border border-green-200 dark:border-green-700';
        case 'recent':
            return 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300 border border-blue-200 dark:border-blue-700';
        case 'offline':
            return 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300 border border-gray-200 dark:border-gray-600';
        case 'never':
            return 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300 border border-red-200 dark:border-red-700';
        default:
            return 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300 border border-gray-200 dark:border-gray-600';
    }
};

/**
 * Obtiene el texto del estado del cliente
 */
const getStatusText = (status: string): string => {
    switch (status) {
        case 'online':
            return 'En línea';
        case 'recent':
            return 'Reciente';
        case 'offline':
            return 'Desconectado';
        case 'never':
            return 'Nunca';
        default:
            return 'Nunca';
    }
};

/**
 * Obtiene el icono del estado del cliente
 */
const getStatusIcon = (status: string): React.ReactElement => {
    switch (status) {
        case 'online':
            return <Circle className="h-2 w-2 text-green-600" />;
        case 'recent':
            return <Circle className="h-2 w-2 text-blue-600" />;
        case 'offline':
            return <Circle className="h-2 w-2 text-gray-400" />;
        case 'never':
            return <Circle className="h-2 w-2 text-red-600" />;
        default:
            return <Circle className="h-2 w-2 text-gray-400" />;
    }
};

/**
 * Obtiene el color del tipo de cliente basado en el nuevo sistema
 */
const getClientTypeColor = (customerType: Customer['customer_type'], fallbackType?: string | null): string => {
    if (customerType && customerType.color) {
        switch (customerType.color) {
            case 'gray':
                return 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-300 border border-gray-200 dark:border-gray-700';
            case 'orange':
                return 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-300 border border-orange-200 dark:border-orange-700';
            case 'slate':
                return 'bg-slate-100 text-slate-800 dark:bg-slate-900 dark:text-slate-300 border border-slate-200 dark:border-slate-700';
            case 'yellow':
                return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300 border border-yellow-200 dark:border-yellow-700';
            case 'purple':
                return 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300 border border-purple-200 dark:border-purple-700';
            case 'green':
                return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300 border border-green-200 dark:border-green-700';
            case 'blue':
                return 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300 border border-blue-200 dark:border-blue-700';
            case 'red':
                return 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300 border border-red-200 dark:border-red-700';
            default:
                return 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300 border border-blue-200 dark:border-blue-700';
        }
    }

    // Fallback al sistema anterior si no hay customer_type
    switch (fallbackType) {
        case 'premium':
            return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300 border border-yellow-200 dark:border-yellow-700';
        case 'vip':
            return 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300 border border-purple-200 dark:border-purple-700';
        case 'regular':
        default:
            return 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300 border border-gray-200 dark:border-gray-600';
    }
};

/**
 * Formatea la fecha de manera legible en hora de Guatemala
 */
const formatDate = (dateString: string | null): string => {
    if (!dateString) return 'N/A';

    return new Date(dateString).toLocaleDateString('es-GT', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        timeZone: 'America/Guatemala'
    });
};


/**
 * Obtiene el icono uniforme para todos los tipos de clientes con el color apropiado
 */
const getCustomerTypeIcon = (color: string, size: string = "h-3 w-3"): React.ReactElement => {
    const colorClass = getCustomerTypeIconColor(color);
    return <Award className={`${size} ${colorClass}`} />;
};

/**
 * Obtiene el color del icono basado en el color del tipo de cliente
 */
const getCustomerTypeIconColor = (color: string): string => {
    switch (color) {
        case 'green':
            return 'text-green-600';
        case 'orange':
            return 'text-orange-600';
        case 'gray':
            return 'text-gray-600';
        case 'yellow':
            return 'text-yellow-600';
        case 'purple':
            return 'text-purple-600';
        case 'blue':
            return 'text-blue-600';
        case 'red':
            return 'text-red-600';
        default:
            return 'text-primary';
    }
};

/**
 * Hook personalizado para debounce
 */
const useDebounce = (value: string, delay: number): string => {
    const [debouncedValue, setDebouncedValue] = useState(value);

    useEffect(() => {
        const handler = setTimeout(() => {
            setDebouncedValue(value);
        }, delay);

        return () => {
            clearTimeout(handler);
        };
    }, [value, delay]);

    return debouncedValue;
};

/**
 * Componente principal de la página de clientes
 */
export default function CustomersIndex({
    customers,
    total_customers,
    verified_customers,
    online_customers,
    premium_customers,
    vip_customers,
    customer_type_stats,
    filters
}: CustomersPageProps) {
    const [search, setSearch] = useState<string>(filters.search || '');
    const [perPage, setPerPage] = useState<number>(filters.per_page);
    const [sortField, setSortField] = useState<string>(filters.sort_field || 'created_at');
    const [sortDirection, setSortDirection] = useState<'asc' | 'desc'>(filters.sort_direction || 'desc');
    const [isLoading, setIsLoading] = useState<boolean>(false);
    const [isRefreshing, setIsRefreshing] = useState<boolean>(false);

    const debouncedSearch = useDebounce(search, 500);

    /**
     * Maneja la actualización de filtros en la URL
     */
    const updateFilters = useCallback((newFilters: Record<string, string | number | undefined>) => {
        setIsLoading(true);
        router.get('/customers', newFilters, {
            preserveState: true,
            replace: true,
            onFinish: () => setIsLoading(false),
        });
    }, []);

    /**
     * Efecto para manejar la búsqueda con debounce
     */
    useEffect(() => {
        updateFilters({
            search: debouncedSearch || undefined,
            per_page: perPage,
            sort_field: sortField,
            sort_direction: sortDirection,
        });
    }, [debouncedSearch, perPage, sortField, sortDirection, updateFilters]);

    /**
     * Maneja el cambio de ordenamiento
     */
    const handleSort = (field: string) => {
        if (field === sortField) {
            setSortDirection(sortDirection === 'asc' ? 'desc' : 'asc');
        } else {
            setSortField(field);
            setSortDirection('asc');
        }
    };

    /**
     * Obtiene el icono de ordenamiento
     */
    const getSortIcon = (field: string) => {
        if (field !== sortField) {
            return <ArrowUpDown className="h-4 w-4 text-gray-400" />;
        }
        return sortDirection === 'asc'
            ? <ArrowUp className="h-4 w-4 text-blue-600" />
            : <ArrowDown className="h-4 w-4 text-blue-600" />;
    };

    /**
     * Función helper para paginación
     */
    const goToPage = (page: number) => {
        router.get('/customers', {
            page: page,
            search: search,
            per_page: perPage,
            sort_field: sortField,
            sort_direction: sortDirection
        }, {
            preserveState: true,
            preserveScroll: true
        });
    };

    /**
     * Función para refrescar los datos de clientes
     */
    const refreshCustomerData = () => {
        setIsRefreshing(true);
        router.get('/customers', {
            search: search,
            per_page: perPage,
            sort_field: sortField,
            sort_direction: sortDirection
        }, {
            preserveState: true,
            replace: true,
            onFinish: () => setIsRefreshing(false),
        });
    };



    return (
        <AppLayout
            breadcrumbs={breadcrumbs}
        >
            <Head title="Clientes" />

            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-6">
                {/* Encabezado */}
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-3xl font-bold tracking-tight">Gestión de Clientes</h1>
                        <p className="text-muted-foreground">
                            Administra los clientes del sistema.
                        </p>
                    </div>
                    <Link
                        href="/customers/create"
                        className="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-primary text-primary-foreground hover:bg-primary/90 h-10 px-4 py-2"
                    >
                        <Plus className="mr-2 h-4 w-4" />
                        Nuevo Cliente
                    </Link>
                </div>

                {/* Tabla de clientes */}
                <Card className="border border-muted/50 shadow-sm">
                    <CardHeader className="pb-6">
                        <div className="flex flex-col space-y-4">
                            <div className="flex items-start justify-between">
                                {/* Estadísticas compactas integradas */}
                                <div className="flex items-center gap-4 text-sm text-muted-foreground">
                                    <span className="flex items-center gap-1">
                                        <Users className="h-3 w-3 text-primary" />
                                        <span>clientes <span className="font-medium text-foreground">{total_customers}</span></span>
                                    </span>
                                    <span className="text-muted-foreground/50">•</span>
                                    <span className="flex items-center gap-1">
                                        <Clock className="h-3 w-3 text-green-600" />
                                        <span>en línea <span className="font-medium text-foreground">{online_customers}</span></span>
                                    </span>
                                    {customer_type_stats && customer_type_stats.length > 0 && (
                                        <>
                                            {customer_type_stats.map((typeStat, index) => (
                                                <React.Fragment key={typeStat.id}>
                                                    <span className="text-muted-foreground/50">•</span>
                                                    <span className="flex items-center gap-1">
                                                        {getCustomerTypeIcon(typeStat.color)}
                                                        <span>{typeStat.display_name.toLowerCase()} <span className="font-medium text-foreground">{typeStat.count}</span></span>
                                                    </span>
                                                </React.Fragment>
                                            ))}
                                        </>
                                    )}
                                </div>
                                
                                {/* Indicador de sincronización */}
                                <div className="flex items-center gap-2 text-sm text-muted-foreground">
                                    <button
                                        onClick={refreshCustomerData}
                                        disabled={isRefreshing}
                                        className="flex items-center gap-1 px-2 py-1 rounded hover:bg-muted transition-colors disabled:opacity-50"
                                        title="Refrescar datos"
                                    >
                                        {isRefreshing ? (
                                            <RefreshCw className="h-3 w-3 animate-spin" />
                                        ) : (
                                            <RefreshCw className="h-3 w-3" />
                                        )}
                                        <span className="text-xs">Refrescar</span>
                                    </button>
                                </div>
                            </div>
                            
                            <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                                <div>
                                    <h2 className="text-xl font-semibold">Gestión de Clientes</h2>
                                    <p className="text-sm text-muted-foreground">
                                        Administra los clientes del sistema
                                    </p>
                                </div>
                            </div>
                        </div>
                    </CardHeader>

                    <CardContent>
                        {/* Barra de búsqueda y filtros */}
                        <div className="flex flex-col sm:flex-row gap-4 mb-6">
                            <div className="flex-1">
                                <Label htmlFor="search" className="sr-only">
                                    Buscar clientes
                                </Label>
                                <div className="relative">
                                    <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                                    <Input
                                        id="search"
                                        placeholder="Buscar por nombre, email, tarjeta subway o teléfono..."
                                        value={search}
                                        onChange={(e) => setSearch(e.target.value)}
                                        className="pl-10"
                                        disabled={isLoading}
                                    />
                                </div>
                            </div>

                            <div className="flex gap-2">
                                <Select value={perPage.toString()} onValueChange={(value) => setPerPage(parseInt(value))}>
                                    <SelectTrigger className="w-[100px]">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="10">10</SelectItem>
                                        <SelectItem value="25">25</SelectItem>
                                        <SelectItem value="50">50</SelectItem>
                                        <SelectItem value="100">100</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>

                        {/* Tabla de clientes */}
                        {isLoading ? (
                            <>
                                {/* Skeleton para desktop */}
                                <div className="hidden lg:block">
                                    <UsersSkeleton />
                                </div>
                                
                                {/* Skeleton para mobile/tablet */}
                                <div className="lg:hidden">
                                    <div className="grid gap-3 md:gap-4">
                                        {[...Array(5)].map((_, i) => (
                                            <div key={i} className="space-y-3 rounded-lg border border-border bg-card p-4">
                                                <div className="flex items-start justify-between">
                                                    <div className="flex-1 space-y-2">
                                                        <div className="h-4 bg-muted rounded w-3/4 animate-pulse"></div>
                                                        <div className="h-3 bg-muted rounded w-1/2 animate-pulse"></div>
                                                    </div>
                                                    <div className="h-6 w-16 bg-muted rounded animate-pulse"></div>
                                                </div>
                                                <div className="grid grid-cols-2 gap-3">
                                                    <div className="space-y-2">
                                                        <div className="h-3 bg-muted rounded w-16 animate-pulse"></div>
                                                        <div className="h-6 bg-muted rounded animate-pulse"></div>
                                                    </div>
                                                    <div className="space-y-2">
                                                        <div className="h-3 bg-muted rounded w-12 animate-pulse"></div>
                                                        <div className="h-4 bg-muted rounded w-20 animate-pulse"></div>
                                                    </div>
                                                </div>
                                                <div className="flex items-center gap-2">
                                                    <div className="h-4 w-4 bg-muted rounded animate-pulse"></div>
                                                    <div className="h-6 w-20 bg-muted rounded animate-pulse"></div>
                                                </div>
                                                <div className="space-y-2">
                                                    <div className="h-3 bg-muted rounded w-32 animate-pulse"></div>
                                                    <div className="h-3 bg-muted rounded w-40 animate-pulse"></div>
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                </div>
                            </>
                        ) : (
                            <>
                                {/* Vista de tabla para desktop */}
                                <div className="hidden lg:block">
                                    <div className="rounded-md border">
                                        <div className="overflow-x-auto">
                                            <table className="w-full">
                                            <thead>
                                                <tr className="border-b bg-muted/50">
                                                    <th className="h-12 px-4 text-left align-middle font-medium text-muted-foreground">
                                                        <button
                                                            className="flex items-center gap-2 hover:text-foreground"
                                                            onClick={() => handleSort('full_name')}
                                                        >
                                                            Cliente
                                                            {getSortIcon('full_name')}
                                                        </button>
                                                    </th>
                                                    <th className="h-12 px-4 text-left align-middle font-medium text-muted-foreground">
                                                        Tarjeta Subway
                                                    </th>
                                                    <th className="h-12 px-4 text-left align-middle font-medium text-muted-foreground">
                                                        <button
                                                            className="flex items-center gap-2 hover:text-foreground"
                                                            onClick={() => handleSort('status')}
                                                        >
                                                            Estatus
                                                            {getSortIcon('status')}
                                                        </button>
                                                    </th>
                                                    <th className="h-12 px-4 text-left align-middle font-medium text-muted-foreground">
                                                        Teléfono
                                                    </th>
                                                    <th className="h-12 px-4 text-left align-middle font-medium text-muted-foreground">
                                                        Última Compra
                                                    </th>
                                                    <th className="h-12 px-4 text-left align-middle font-medium text-muted-foreground">
                                                        Puntos
                                                    </th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                {customers.data.length === 0 ? (
                                                    <tr>
                                                        <td colSpan={7} className="h-32 text-center">
                                                            <div className="flex flex-col items-center justify-center">
                                                                <Users className="h-8 w-8 text-muted-foreground mb-2" />
                                                                <p className="text-sm text-muted-foreground">
                                                                    No se encontraron clientes
                                                                </p>
                                                                {search && (
                                                                    <p className="text-xs text-muted-foreground">
                                                                        Intenta con otros términos de búsqueda
                                                                    </p>
                                                                )}
                                                            </div>
                                                        </td>
                                                    </tr>
                                                ) : (
                                                    customers.data.map((customer) => (
                                                        <tr key={customer.id} className="border-b hover:bg-muted/50">
                                                            <td className="p-4">
                                                                <div className="flex items-center gap-3">
                                                                    <div className="w-10 h-10 rounded-full bg-primary/10 flex items-center justify-center flex-shrink-0">
                                                                        <Users className="w-5 h-5 text-primary" />
                                                                    </div>
                                                                    <div className="min-w-0">
                                                                        <div className="font-medium text-sm text-foreground truncate">
                                                                            {customer.full_name}
                                                                        </div>
                                                                        <div className="text-sm text-muted-foreground truncate">
                                                                            {customer.email}
                                                                        </div>
                                                                        <div className="flex flex-wrap items-center gap-2 mt-1">
                                                                            {customer.gender && (
                                                                                <Badge variant="outline" className="text-xs px-2 py-0.5 bg-indigo-50 text-indigo-700 border-indigo-200">
                                                                                    {customer.gender}
                                                                                </Badge>
                                                                            )}
                                                                            {customer.birth_date && (
                                                                                <Badge variant="outline" className="text-xs px-2 py-0.5 bg-sky-50 text-sky-700 border-sky-200">
                                                                                    {Math.floor((new Date().getTime() - new Date(customer.birth_date).getTime()) / (365.25 * 24 * 60 * 60 * 1000))} años
                                                                                </Badge>
                                                                            )}
                                                                            {customer.email_verified_at ? (
                                                                                <Badge variant="outline" className="text-xs px-2 py-0.5 bg-green-50 text-green-700 border-green-200">
                                                                                    ✓ Verificado
                                                                                </Badge>
                                                                            ) : (
                                                                                <Badge variant="outline" className="text-xs px-2 py-0.5 bg-red-50 text-red-700 border-red-200">
                                                                                    ✗ No verificado
                                                                                </Badge>
                                                                            )}
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </td>
                                                            <td className="p-4">
                                                                <div className="flex items-center gap-2">
                                                                    <CreditCard className="h-4 w-4 text-muted-foreground" />
                                                                    <code className="text-sm">{customer.subway_card}</code>
                                                                </div>
                                                                <div className="mt-1">
                                                                    <div className="flex items-center gap-2">
                                                                        {customer.customer_type && (
                                                                            <span className="flex items-center">
                                                                                {getCustomerTypeIcon(customer.customer_type.color, "h-3 w-3")}
                                                                            </span>
                                                                        )}
                                                                        <Badge className={getClientTypeColor(customer.customer_type, customer.client_type)}>
                                                                            {customer.customer_type?.display_name || customer.client_type || 'Regular'}
                                                                        </Badge>
                                                                        {customer.customer_type && (
                                                                            <span className="text-xs text-muted-foreground">
                                                                                {customer.customer_type.multiplier}x
                                                                            </span>
                                                                        )}
                                                                    </div>
                                                                </div>
                                                            </td>
                                                            <td className="p-4">
                                                                <div className="flex items-center gap-2">
                                                                    {getStatusIcon(customer.status)}
                                                                    <Badge className={getStatusColor(customer.status)}>
                                                                        {getStatusText(customer.status)}
                                                                    </Badge>
                                                                </div>
                                                                {customer.last_activity && (
                                                                    <div className="text-xs text-muted-foreground mt-1">
                                                                        <Clock className="h-3 w-3 inline mr-1" />
                                                                        {formatDate(customer.last_activity)}
                                                                    </div>
                                                                )}
                                                            </td>
                                                            <td className="p-4">
                                                                <div className="text-sm">
                                                                    {customer.phone || 'N/A'}
                                                                </div>
                                                                {customer.location && (
                                                                    <div className="text-xs text-muted-foreground flex items-center">
                                                                        <MapPin className="h-3 w-3 inline mr-1" />
                                                                        {customer.location}
                                                                    </div>
                                                                )}
                                                            </td>
                                                            <td className="p-4">
                                                                <div className="text-sm">
                                                                    {customer.last_purchase ? formatDate(customer.last_purchase) : 'Sin compras'}
                                                                </div>
                                                                {customer.last_purchase && (
                                                                    <div className="text-xs text-muted-foreground">
                                                                        {Math.floor((new Date().getTime() - new Date(customer.last_purchase).getTime()) / (24 * 60 * 60 * 1000))} días
                                                                    </div>
                                                                )}
                                                            </td>
                                                            <td className="p-4">
                                                                <div className="text-sm font-medium text-blue-600">
                                                                    {(customer.puntos || 0).toLocaleString()} pts
                                                                </div>
                                                                {customer.puntos_updated_at && (
                                                                    <div className="text-xs text-muted-foreground">
                                                                        Actualizado: {formatDate(customer.puntos_updated_at)}
                                                                    </div>
                                                                )}
                                                            </td>
                                                        </tr>
                                                    ))
                                                )}
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                </div>

                                {/* Vista de cards para mobile/tablet */}
                                <div className="lg:hidden">
                                    <div className="grid gap-4 sm:gap-4 md:gap-5">
                                        {customers.data.map((customer) => (
                                            <div
                                                key={customer.id}
                                                className="space-y-3 rounded-lg border border-border bg-card p-4 sm:p-5 transition-colors hover:bg-muted/50 hover:shadow-sm"
                                            >
                                                {/* Header del card - Nombre y estado */}
                                                <div className="flex flex-col space-y-2 sm:flex-row sm:items-start sm:justify-between sm:space-y-0">
                                                    <div className="flex-1 min-w-0">
                                                        <h3 className="font-medium text-foreground truncate mb-1">
                                                            {customer.full_name}
                                                        </h3>
                                                        <p className="text-sm text-muted-foreground truncate">
                                                            {customer.email}
                                                        </p>
                                                    </div>
                                                    <div className="flex items-center gap-1 flex-shrink-0">
                                                        {getStatusIcon(customer.status)}
                                                        <Badge className={`${getStatusColor(customer.status)} text-xs`}>
                                                            {getStatusText(customer.status)}
                                                        </Badge>
                                                    </div>
                                                </div>

                                                {/* Información básica */}
                                                <div className="grid grid-cols-2 gap-3 text-sm">
                                                    {/* Tarjeta Subway */}
                                                    <div className="space-y-1">
                                                        <div className="flex items-center gap-1 text-muted-foreground">
                                                            <CreditCard className="h-3 w-3" />
                                                            <span className="text-xs">Tarjeta</span>
                                                        </div>
                                                        <code className="text-xs font-mono bg-muted px-2 py-1 rounded">
                                                            {customer.subway_card}
                                                        </code>
                                                    </div>

                                                    {/* Puntos */}
                                                    <div className="space-y-1">
                                                        <div className="text-xs text-muted-foreground">Puntos</div>
                                                        <div className="font-medium text-blue-600">
                                                            {(customer.puntos || 0).toLocaleString()} pts
                                                        </div>
                                                    </div>
                                                </div>

                                                {/* Tipo de cliente */}
                                                {customer.customer_type && (
                                                    <div className="flex items-center gap-2">
                                                        {getCustomerTypeIcon(customer.customer_type.color, "h-4 w-4")}
                                                        <Badge className={getClientTypeColor(customer.customer_type, customer.client_type)}>
                                                            {customer.customer_type.display_name}
                                                        </Badge>
                                                        <span className="text-xs text-muted-foreground">
                                                            {customer.customer_type.multiplier}x
                                                        </span>
                                                    </div>
                                                )}

                                                {/* Información de contacto */}
                                                <div className="grid grid-cols-1 gap-2 text-sm">
                                                    {customer.phone && (
                                                        <div className="flex items-center gap-2 text-muted-foreground">
                                                            <span className="text-xs">📞</span>
                                                            <span>{customer.phone}</span>
                                                        </div>
                                                    )}
                                                    {customer.location && (
                                                        <div className="flex items-center gap-2 text-muted-foreground">
                                                            <MapPin className="h-3 w-3" />
                                                            <span>{customer.location}</span>
                                                        </div>
                                                    )}
                                                </div>

                                                {/* Verificación de email */}
                                                <div className="flex items-center justify-between">
                                                    <div className="flex items-center gap-2">
                                                        {customer.email_verified_at ? (
                                                            <Badge variant="outline" className="text-xs px-2 py-0.5 bg-green-50 text-green-700 border-green-200">
                                                                ✓ Verificado
                                                            </Badge>
                                                        ) : (
                                                            <Badge variant="outline" className="text-xs px-2 py-0.5 bg-red-50 text-red-700 border-red-200">
                                                                ✗ No verificado
                                                            </Badge>
                                                        )}
                                                    </div>
                                                    
                                                    {/* Última actividad */}
                                                    {customer.last_activity && (
                                                        <div className="text-xs text-muted-foreground flex items-center gap-1">
                                                            <Clock className="h-3 w-3" />
                                                            <span>{formatDate(customer.last_activity)}</span>
                                                        </div>
                                                    )}
                                                </div>

                                                {/* Última compra */}
                                                {customer.last_purchase && (
                                                    <div className="text-xs text-muted-foreground">
                                                        <span className="font-medium">Última compra:</span> {formatDate(customer.last_purchase)}
                                                        <span className="ml-2">
                                                            ({Math.floor((new Date().getTime() - new Date(customer.last_purchase).getTime()) / (24 * 60 * 60 * 1000))} días)
                                                        </span>
                                                    </div>
                                                )}
                                            </div>
                                        ))}
                                    </div>
                                </div>

                                {/* Paginación */}
                                {customers.last_page > 1 && (
                                    <div className="mt-6">
                                        <Pagination>
                                            <PaginationContent>
                                                <PaginationItem>
                                                    <PaginationPrevious
                                                        href="#"
                                                        onClick={(e) => {
                                                            e.preventDefault();
                                                            goToPage(customers.current_page - 1);
                                                        }}
                                                        className={customers.current_page <= 1 ? 'pointer-events-none opacity-50' : ''}
                                                    />
                                                </PaginationItem>

                                                {/* Primera página */}
                                                {customers.current_page > 3 && (
                                                    <>
                                                        <PaginationItem>
                                                            <PaginationLink
                                                                href="#"
                                                                onClick={(e) => {
                                                                    e.preventDefault();
                                                                    goToPage(1);
                                                                }}
                                                            >
                                                                1
                                                            </PaginationLink>
                                                        </PaginationItem>
                                                        {customers.current_page > 4 && (
                                                            <PaginationItem>
                                                                <PaginationEllipsis />
                                                            </PaginationItem>
                                                        )}
                                                    </>
                                                )}

                                                {/* Páginas alrededor de la actual */}
                                                {Array.from({ length: Math.min(3, customers.last_page) }, (_, i) => {
                                                    const page = customers.current_page - 1 + i;
                                                    if (page < 1 || page > customers.last_page) return null;

                                                    return (
                                                        <PaginationItem key={page}>
                                                            <PaginationLink
                                                                href="#"
                                                                onClick={(e) => {
                                                                    e.preventDefault();
                                                                    goToPage(page);
                                                                }}
                                                                isActive={page === customers.current_page}
                                                            >
                                                                {page}
                                                            </PaginationLink>
                                                        </PaginationItem>
                                                    );
                                                })}

                                                {/* Última página */}
                                                {customers.current_page < customers.last_page - 2 && (
                                                    <>
                                                        {customers.current_page < customers.last_page - 3 && (
                                                            <PaginationItem>
                                                                <PaginationEllipsis />
                                                            </PaginationItem>
                                                        )}
                                                        <PaginationItem>
                                                            <PaginationLink
                                                                href="#"
                                                                onClick={(e) => {
                                                                    e.preventDefault();
                                                                    goToPage(customers.last_page);
                                                                }}
                                                            >
                                                                {customers.last_page}
                                                            </PaginationLink>
                                                        </PaginationItem>
                                                    </>
                                                )}

                                                <PaginationItem>
                                                    <PaginationNext
                                                        href="#"
                                                        onClick={(e) => {
                                                            e.preventDefault();
                                                            goToPage(customers.current_page + 1);
                                                        }}
                                                        className={customers.current_page >= customers.last_page ? 'pointer-events-none opacity-50' : ''}
                                                    />
                                                </PaginationItem>
                                            </PaginationContent>
                                        </Pagination>
                                    </div>
                                )}
                            </>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
