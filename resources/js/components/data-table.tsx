import React, { useState, useCallback, useEffect } from 'react';
import { router, Link } from '@inertiajs/react';
import { Plus, Search, RefreshCw, ArrowUp, ArrowDown, ArrowUpDown } from 'lucide-react';

import { Card, CardContent, CardHeader } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import {
    Pagination,
    PaginationContent,
    PaginationEllipsis,
    PaginationItem,
    PaginationLink,
    PaginationNext,
    PaginationPrevious,
} from "@/components/ui/pagination";

interface Column<T> {
    key: string;
    title: string;
    sortable?: boolean;
    render?: (item: T) => React.ReactNode;
    className?: string;
}

interface Stat {
    title: string;
    value: number | string;
    icon: React.ReactNode;
    description?: string;
}

interface DataTableProps<T> {
    title: string;
    description: string;
    data: {
        data: T[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        from: number;
        to: number;
    };
    columns: Column<T>[];
    stats?: Stat[];
    filters: {
        search?: string | null;
        per_page: number;
        sort_field?: string;
        sort_direction?: 'asc' | 'desc';
    };
    createUrl?: string;
    createLabel?: string;
    searchPlaceholder?: string;
    loadingSkeleton?: React.ComponentType<{ rows: number }>;
    renderMobileCard?: (item: T) => React.ReactNode;
    onRefresh?: () => void;
    route: string;
}

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

export function DataTable<T extends { id: number | string }>({
    title,
    description,
    data,
    columns,
    stats,
    filters,
    createUrl,
    createLabel = "Nuevo",
    searchPlaceholder = "Buscar...",
    loadingSkeleton: LoadingSkeleton,
    renderMobileCard,
    onRefresh,
    route: routeName,
}: DataTableProps<T>) {
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
        router.get(route(routeName), newFilters, {
            preserveState: true,
            replace: true,
            onFinish: () => setIsLoading(false),
        });
    }, [routeName]);

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
        router.get(route(routeName), {
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
     * Función para refrescar los datos
     */
    const refreshData = () => {
        if (onRefresh) {
            onRefresh();
        } else {
            setIsRefreshing(true);
            router.get(route(routeName), {
                search: search,
                per_page: perPage,
                sort_field: sortField,
                sort_direction: sortDirection
            }, {
                preserveState: true,
                replace: true,
                onFinish: () => setIsRefreshing(false),
            });
        }
    };

    return (
        <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-6">
            {/* Encabezado */}
            <div className="flex items-center justify-between">
                <div>
                    <h1 className="text-3xl font-bold tracking-tight">{title}</h1>
                    <p className="text-muted-foreground">{description}</p>
                </div>
                {createUrl && (
                    <Link
                        href={createUrl}
                        className="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-primary text-primary-foreground hover:bg-primary/90 h-10 px-4 py-2"
                    >
                        <Plus className="mr-2 h-4 w-4" />
                        {createLabel}
                    </Link>
                )}
            </div>


            {/* Tabla */}
            <Card className="border border-muted/50 shadow-sm">
                <CardHeader className="pb-6">
                    <div className="flex flex-col space-y-4">
                        <div className="flex items-start justify-between">
                            <div className="flex items-center gap-4 text-sm text-muted-foreground">
                                {stats && stats.length > 0 && (
                                    <div className="flex items-center gap-4">
                                        {stats.map((stat, index) => (
                                            <React.Fragment key={index}>
                                                {index > 0 && <span className="text-muted-foreground/50">•</span>}
                                                <span className="flex items-center gap-1">
                                                    {React.cloneElement(stat.icon as React.ReactElement, { 
                                                        className: (stat.icon as React.ReactElement).props.className || "h-3 w-3"
                                                    })}
                                                    <span>{stat.title.toLowerCase()} <span className="font-medium text-foreground">{stat.value}</span></span>
                                                </span>
                                            </React.Fragment>
                                        ))}
                                    </div>
                                )}
                            </div>

                            {/* Indicador de sincronización */}
                            <div className="flex items-center gap-2 text-sm text-muted-foreground">
                                <button
                                    onClick={refreshData}
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
                    </div>
                </CardHeader>

                <CardContent>
                    {/* Barra de búsqueda y filtros */}
                    <div className="flex flex-col sm:flex-row gap-4 mb-6">
                        <div className="flex-1">
                            <Label htmlFor="search" className="sr-only">
                                Buscar
                            </Label>
                            <div className="relative">
                                <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                                <Input
                                    id="search"
                                    placeholder={searchPlaceholder}
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

                    {/* Contenido de la tabla */}
                    {isLoading && LoadingSkeleton ? (
                        <LoadingSkeleton rows={perPage} />
                    ) : (
                        <>
                            {/* Vista de tabla para desktop */}
                            <div className="hidden lg:block">
                                <div className="rounded-md border">
                                    <div className="overflow-x-auto">
                                        <table className="w-full">
                                            <thead>
                                                <tr className="border-b bg-muted/50">
                                                    {columns.map((column) => (
                                                        <th key={column.key} className={`h-12 px-4 text-left align-middle font-medium text-muted-foreground ${column.className || ''}`}>
                                                            {column.sortable ? (
                                                                <button
                                                                    className="flex items-center gap-2 hover:text-foreground"
                                                                    onClick={() => handleSort(column.key)}
                                                                >
                                                                    {column.title}
                                                                    {getSortIcon(column.key)}
                                                                </button>
                                                            ) : (
                                                                column.title
                                                            )}
                                                        </th>
                                                    ))}
                                                </tr>
                                            </thead>
                                            <tbody>
                                                {data.data.length === 0 ? (
                                                    <tr>
                                                        <td colSpan={columns.length} className="h-32 text-center">
                                                            <div className="flex flex-col items-center justify-center">
                                                                <div className="h-8 w-8 text-muted-foreground mb-2" />
                                                                <p className="text-sm text-muted-foreground">
                                                                    No se encontraron resultados
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
                                                    data.data.map((item) => (
                                                        <tr key={item.id} className="border-b hover:bg-muted/50">
                                                            {columns.map((column) => (
                                                                <td key={column.key} className={`p-4 ${column.className || ''}`}>
                                                                    {column.render ? column.render(item) : (item as Record<string, unknown>)[column.key]}
                                                                </td>
                                                            ))}
                                                        </tr>
                                                    ))
                                                )}
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            {/* Vista de cards para mobile/tablet */}
                            {renderMobileCard && (
                                <div className="lg:hidden">
                                    <div className="grid gap-4 sm:gap-4 md:gap-5">
                                        {data.data.map((item) => (
                                            <div key={item.id}>
                                                {renderMobileCard(item)}
                                            </div>
                                        ))}
                                    </div>
                                </div>
                            )}

                            {/* Paginación */}
                            {data.last_page > 1 && (
                                <div className="mt-6">
                                    <Pagination>
                                        <PaginationContent>
                                            <PaginationItem>
                                                <PaginationPrevious
                                                    href="#"
                                                    onClick={(e) => {
                                                        e.preventDefault();
                                                        goToPage(data.current_page - 1);
                                                    }}
                                                    className={data.current_page <= 1 ? 'pointer-events-none opacity-50' : ''}
                                                />
                                            </PaginationItem>

                                            {/* Primera página */}
                                            {data.current_page > 3 && (
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
                                                    {data.current_page > 4 && (
                                                        <PaginationItem>
                                                            <PaginationEllipsis />
                                                        </PaginationItem>
                                                    )}
                                                </>
                                            )}

                                            {/* Páginas alrededor de la actual */}
                                            {Array.from({ length: Math.min(3, data.last_page) }, (_, i) => {
                                                const page = data.current_page - 1 + i;
                                                if (page < 1 || page > data.last_page) return null;

                                                return (
                                                    <PaginationItem key={page}>
                                                        <PaginationLink
                                                            href="#"
                                                            onClick={(e) => {
                                                                e.preventDefault();
                                                                goToPage(page);
                                                            }}
                                                            isActive={page === data.current_page}
                                                        >
                                                            {page}
                                                        </PaginationLink>
                                                    </PaginationItem>
                                                );
                                            })}

                                            {/* Última página */}
                                            {data.current_page < data.last_page - 2 && (
                                                <>
                                                    {data.current_page < data.last_page - 3 && (
                                                        <PaginationItem>
                                                            <PaginationEllipsis />
                                                        </PaginationItem>
                                                    )}
                                                    <PaginationItem>
                                                        <PaginationLink
                                                            href="#"
                                                            onClick={(e) => {
                                                                e.preventDefault();
                                                                goToPage(data.last_page);
                                                            }}
                                                        >
                                                            {data.last_page}
                                                        </PaginationLink>
                                                    </PaginationItem>
                                                </>
                                            )}

                                            <PaginationItem>
                                                <PaginationNext
                                                    href="#"
                                                    onClick={(e) => {
                                                        e.preventDefault();
                                                        goToPage(data.current_page + 1);
                                                    }}
                                                    className={data.current_page >= data.last_page ? 'pointer-events-none opacity-50' : ''}
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
    );
}