import { Link, router } from '@inertiajs/react';
import { ArrowDown, ArrowUp, ArrowUpDown, Plus, RefreshCw, Search, X } from 'lucide-react';
import React, { memo, useCallback, useEffect, useRef, useState } from 'react';

import { ErrorBoundary } from '@/components/ErrorBoundary';
import { PaginationWrapper } from '@/components/PaginationWrapper';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import { Column, DataTableFilters, DataTableStat, PaginatedData } from '@/types';

const columnWidthConfig = {
    xs: 'w-[5rem]',
    sm: 'w-[7rem]',
    md: 'w-[9rem]',
    lg: 'w-[12rem]',
    xl: 'w-[16rem]',
    auto: '',
    full: 'w-full',
} as const;

const textAlignmentConfig = {
    left: 'text-left',
    center: 'text-center',
    right: 'text-right',
} as const;

interface DataTableProps<T> {
    title: string;
    description?: string;
    data: PaginatedData<T>;
    columns: Column<T>[];
    stats?: DataTableStat[];
    filters: DataTableFilters;
    createUrl?: string;
    createLabel?: string;
    searchPlaceholder?: string;
    loadingSkeleton?: React.ComponentType<{ rows: number }>;
    renderMobileCard?: (item: T) => React.ReactNode;
    routeName: string;
    breakpoint?: 'sm' | 'md' | 'lg' | 'xl';
}

interface TruncatedTextProps {
    children: React.ReactNode;
    maxLength?: number;
    className?: string;
}

const TruncatedText: React.FC<TruncatedTextProps> = ({ children, maxLength, className = '' }) => {
    const text = typeof children === 'string' ? children : String(children);
    const shouldTruncate = maxLength && text.length > maxLength;

    if (!shouldTruncate) {
        return <span className={className}>{children}</span>;
    }

    return (
        <TooltipProvider>
            <Tooltip>
                <TooltipTrigger asChild>
                    <span className={`cursor-help truncate ${className}`} style={{ maxWidth: `${maxLength}ch` }}>
                        {children}
                    </span>
                </TooltipTrigger>
                <TooltipContent side="top" className="max-w-xs">
                    <p>{text}</p>
                </TooltipContent>
            </Tooltip>
        </TooltipProvider>
    );
};

/**
 * DataTable - Componente de tabla con paginación server-side
 *
 * Arquitectura:
 * - Los props del backend (filters) son la fuente de verdad
 * - Solo el input de búsqueda tiene estado local (para debounce)
 * - Soporta ordenamiento múltiple con sort_criteria
 */
const DataTableComponent = function DataTable<T extends { id: number | string }>({
    title,
    description,
    data,
    columns,
    stats,
    filters,
    createUrl,
    createLabel = 'Crear',
    searchPlaceholder = 'Buscar...',
    loadingSkeleton: LoadingSkeleton,
    renderMobileCard,
    routeName,
    breakpoint = 'md',
}: DataTableProps<T>) {
    const [searchInput, setSearchInput] = useState(filters.search || '');
    const [isLoading, setIsLoading] = useState(false);
    const debounceRef = useRef<NodeJS.Timeout | null>(null);

    const breakpointClass = breakpoint === 'md' ? 'md:' : `${breakpoint}:`;

    // Current sort state (single column)
    const currentSortField = filters.sort_field || '';
    const currentSortDirection = filters.sort_direction || 'asc';

    // Sync search input cuando cambian los filtros del backend
    useEffect(() => {
        setSearchInput(filters.search || '');
    }, [filters.search]);

    // Navegación centralizada
    const navigate = useCallback(
        (params: Record<string, string | number | undefined>, resetPage = false) => {
            setIsLoading(true);
            const payload: Record<string, string | number | undefined> = { ...params };
            if (!resetPage && data.current_page > 1) {
                payload.page = data.current_page;
            }
            router.get(routeName, payload, {
                preserveState: true,
                preserveScroll: true,
                replace: true,
                onFinish: () => setIsLoading(false),
            });
        },
        [routeName, data.current_page],
    );

    // Construir payload base desde filtros actuales
    const buildPayload = useCallback(
        (overrides: Record<string, string | number | undefined> = {}) => {
            const payload: Record<string, string | number | undefined> = {
                per_page: filters.per_page,
            };
            if (filters.search) payload.search = filters.search;
            if (currentSortField) {
                payload.sort_field = currentSortField;
                payload.sort_direction = currentSortDirection;
            }
            return { ...payload, ...overrides };
        },
        [filters.per_page, filters.search, currentSortField, currentSortDirection],
    );

    // Búsqueda con debounce
    const handleSearchChange = (value: string) => {
        setSearchInput(value);
        if (debounceRef.current) clearTimeout(debounceRef.current);

        debounceRef.current = setTimeout(() => {
            const payload = buildPayload();
            if (value.trim()) {
                payload.search = value.trim();
            } else {
                delete payload.search;
            }
            navigate(payload, true);
        }, 500);
    };

    // Búsqueda inmediata
    const handleSearchSubmit = () => {
        if (debounceRef.current) clearTimeout(debounceRef.current);
        const payload = buildPayload();
        if (searchInput.trim()) {
            payload.search = searchInput.trim();
        } else {
            delete payload.search;
        }
        navigate(payload, true);
    };

    // Limpiar búsqueda
    const handleClearSearch = () => {
        if (debounceRef.current) clearTimeout(debounceRef.current);
        setSearchInput('');
        const payload = buildPayload();
        delete payload.search;
        navigate(payload, true);
    };

    // Cambiar items por página
    const handlePerPageChange = (value: string) => {
        navigate(buildPayload({ per_page: parseInt(value) }), true);
    };

    // Ordenar por columna (single column - click reemplaza, no acumula)
    const handleSort = (field: string) => {
        let newDirection: 'asc' | 'desc' = 'asc';

        // Si ya está ordenado por este campo, toggle direction
        if (currentSortField === field) {
            newDirection = currentSortDirection === 'asc' ? 'desc' : 'asc';
        }

        const payload = buildPayload({
            sort_field: field,
            sort_direction: newDirection,
        });
        navigate(payload);
    };

    // Icono de ordenamiento
    const getSortIcon = (field: string) => {
        if (currentSortField !== field) {
            return <ArrowUpDown className="h-4 w-4 text-muted-foreground/50" />;
        }

        return currentSortDirection === 'asc'
            ? <ArrowUp className="h-4 w-4 text-primary" />
            : <ArrowDown className="h-4 w-4 text-primary" />;
    };

    // Refresh
    const handleRefresh = () => {
        setIsLoading(true);
        router.get(routeName, buildPayload({ page: data.current_page }), {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            onFinish: () => setIsLoading(false),
        });
    };

    // Render cell content
    const renderCellContent = (column: Column<T>, item: T) => {
        const value = column.render
            ? column.render(item, (item as Record<string, unknown>)[column.key])
            : (item as Record<string, unknown>)[column.key];

        if (column.truncate && typeof value === 'string') {
            const maxLength = typeof column.truncate === 'number' ? column.truncate : 30;
            return <TruncatedText maxLength={maxLength}>{value}</TruncatedText>;
        }
        return value as React.ReactNode;
    };

    return (
        <ErrorBoundary context="tabla de datos" showRetry={true}>
            <div className="flex h-full flex-1 flex-col gap-6 p-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div className="space-y-1">
                        <h1 className="text-3xl font-bold tracking-tight">{title}</h1>
                        {description && <p className="text-muted-foreground">{description}</p>}
                    </div>
                    {createUrl && (
                        <Link href={createUrl}>
                            <Button>
                                <Plus className="mr-2 h-4 w-4" />
                                {createLabel}
                            </Button>
                        </Link>
                    )}
                </div>

                <Card>
                    <CardHeader className="pb-6">
                        <div className="flex flex-wrap items-start justify-between gap-4">
                            {/* Stats */}
                            {stats && stats.length > 0 && (
                                <div className="flex flex-wrap items-center gap-x-4 gap-y-2 text-sm text-muted-foreground">
                                    {stats.map((stat, index) => (
                                        <div key={index} className="flex items-center gap-2">
                                            {stat.icon}
                                            <span className="lowercase">{stat.title}</span>
                                            <span className="font-medium text-foreground tabular-nums">{stat.value}</span>
                                        </div>
                                    ))}
                                </div>
                            )}

                            {/* Refresh */}
                            <Button variant="ghost" size="sm" onClick={handleRefresh} disabled={isLoading}>
                                <RefreshCw className={`mr-1 h-4 w-4 ${isLoading ? 'animate-spin' : ''}`} />
                                Sincronizar
                            </Button>
                        </div>
                    </CardHeader>

                    <CardContent>
                        {/* Search and Filters */}
                        <div className="mb-6 flex flex-col gap-4 sm:flex-row">
                            <div className="flex flex-1 gap-2">
                                <div className="flex-1">
                                    <Label htmlFor="search" className="sr-only">Buscar</Label>
                                    <div className="relative">
                                        <Search className="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                                        <Input
                                            id="search"
                                            placeholder={searchPlaceholder}
                                            value={searchInput}
                                            onChange={(e) => handleSearchChange(e.target.value)}
                                            onKeyDown={(e) => e.key === 'Enter' && handleSearchSubmit()}
                                            className="pr-10 pl-10"
                                            disabled={isLoading}
                                        />
                                        {searchInput && (
                                            <button
                                                type="button"
                                                onClick={handleClearSearch}
                                                className="absolute top-1/2 right-3 -translate-y-1/2 text-muted-foreground hover:text-foreground"
                                                disabled={isLoading}
                                            >
                                                <X className="h-4 w-4" />
                                            </button>
                                        )}
                                    </div>
                                </div>
                                <Button onClick={handleSearchSubmit} disabled={isLoading}>
                                    <Search className="mr-2 h-4 w-4" />
                                    Buscar
                                </Button>
                            </div>

                            <Select value={filters.per_page.toString()} onValueChange={handlePerPageChange}>
                                <SelectTrigger className="w-[100px]">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="15">15</SelectItem>
                                    <SelectItem value="25">25</SelectItem>
                                    <SelectItem value="50">50</SelectItem>
                                    <SelectItem value="100">100</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        {/* Content */}
                        {isLoading && LoadingSkeleton ? (
                            <LoadingSkeleton rows={filters.per_page} />
                        ) : (
                            <>
                                {/* Desktop Table */}
                                <div className={`hidden ${breakpointClass}block`}>
                                    <div className="overflow-x-auto rounded-md border">
                                        <Table>
                                            <TableHeader>
                                                <TableRow>
                                                    {columns.map((column) => (
                                                        <TableHead
                                                            key={column.key}
                                                            className={`${column.width ? columnWidthConfig[column.width] : ''} ${column.align ? textAlignmentConfig[column.align] : textAlignmentConfig.left} ${column.className || ''}`}
                                                        >
                                                            {column.sortable ? (
                                                                <Button
                                                                    variant="ghost"
                                                                    onClick={() => handleSort(column.key)}
                                                                    className="flex items-center gap-2 px-0 hover:bg-transparent"
                                                                >
                                                                    {column.title}
                                                                    {getSortIcon(column.key)}
                                                                </Button>
                                                            ) : (
                                                                column.title
                                                            )}
                                                        </TableHead>
                                                    ))}
                                                </TableRow>
                                            </TableHeader>
                                            <TableBody>
                                                {data.data.length === 0 ? (
                                                    <TableRow>
                                                        <TableCell colSpan={columns.length} className="h-32 text-center">
                                                            <p className="text-muted-foreground">No se encontraron resultados</p>
                                                        </TableCell>
                                                    </TableRow>
                                                ) : (
                                                    data.data.map((item) => (
                                                        <TableRow key={item.id}>
                                                            {columns.map((column) => (
                                                                <TableCell
                                                                    key={column.key}
                                                                    className={`${column.width ? columnWidthConfig[column.width] : ''} ${column.align ? textAlignmentConfig[column.align] : textAlignmentConfig.left} ${column.className || ''} py-4`}
                                                                >
                                                                    {renderCellContent(column, item)}
                                                                </TableCell>
                                                            ))}
                                                        </TableRow>
                                                    ))
                                                )}
                                            </TableBody>
                                        </Table>
                                    </div>
                                </div>

                                {/* Mobile Cards */}
                                {renderMobileCard && (
                                    <div className={`${breakpointClass}hidden`}>
                                        <div className="grid gap-4">
                                            {data.data.length === 0 ? (
                                                <div className="py-12 text-center">
                                                    <p className="text-muted-foreground">No se encontraron resultados</p>
                                                </div>
                                            ) : (
                                                data.data.map((item) => <div key={item.id}>{renderMobileCard(item)}</div>)
                                            )}
                                        </div>
                                    </div>
                                )}

                                {/* Pagination */}
                                <PaginationWrapper
                                    data={data}
                                    routeName={routeName}
                                    filters={{
                                        per_page: filters.per_page,
                                        search: filters.search,
                                        sort_field: currentSortField || undefined,
                                        sort_direction: currentSortDirection,
                                    }}
                                    className="mt-8"
                                />
                            </>
                        )}
                    </CardContent>
                </Card>
            </div>
        </ErrorBoundary>
    );
};

export const DataTable = memo(DataTableComponent) as <T extends { id: number | string }>(
    props: DataTableProps<T>,
) => React.ReactElement;
