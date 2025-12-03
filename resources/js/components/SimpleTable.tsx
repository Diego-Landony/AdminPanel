import { Link } from '@inertiajs/react';
import { Plus, RefreshCw, Search, X } from 'lucide-react';
import React, { useState } from 'react';

import { ErrorBoundary } from '@/components/ErrorBoundary';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';

interface SimpleTableColumn<T> {
    key: string;
    title: string;
    width?: string;
    textAlign?: 'left' | 'center' | 'right';
    render?: (item: T) => React.ReactNode;
    className?: string;
}

interface SimpleTableStat {
    title: string;
    value: number | string;
    icon: React.ReactNode;
}

interface SimpleTableProps<T extends { id: number | string }> {
    title: string;
    description?: string;
    data: T[];
    columns: SimpleTableColumn<T>[];
    stats?: SimpleTableStat[];
    createUrl?: string;
    createLabel?: string;
    searchable?: boolean;
    searchPlaceholder?: string;
    onRefresh?: () => void;
    isRefreshing?: boolean;
    renderMobileCard?: (item: T) => React.ReactNode;
    breakpoint?: 'sm' | 'md' | 'lg' | 'xl';
    loadingSkeleton?: React.ComponentType<{ rows?: number }>;
    isLoading?: boolean;
}

/**
 * Componente de tabla simple sin drag & drop
 *
 * Características:
 * - Búsqueda simple (sin filtros complejos)
 * - Sin paginación (muestra todos los items)
 * - Estadísticas opcionales
 * - Botón de creación opcional
 * - Vista responsive con cards en móvil
 */
function SimpleTableComponent<T extends { id: number | string }>({
    title,
    description,
    data,
    columns,
    stats,
    createUrl,
    createLabel = 'Crear',
    searchable = true,
    searchPlaceholder = 'Buscar...',
    onRefresh,
    isRefreshing = false,
    renderMobileCard,
    breakpoint = 'lg',
    loadingSkeleton: LoadingSkeleton,
    isLoading = false,
}: SimpleTableProps<T>) {
    const [search, setSearch] = useState('');

    const breakpointClass = breakpoint + ':';

    // Filtrar items por búsqueda
    const filteredItems =
        searchable && search.trim()
            ? data.filter((item) => {
                  const searchLower = search.toLowerCase();
                  return Object.values(item).some((value) => String(value).toLowerCase().includes(searchLower));
              })
            : data;

    return (
        <ErrorBoundary context="tabla simple" showRetry={true}>
            <div className="mx-auto flex h-full max-w-7xl flex-1 flex-col gap-6 p-6">
                {/* Page Header */}
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

                {/* Data Table Card */}
                <Card>
                    <CardHeader className="pb-6">
                        <div className="flex flex-col space-y-4">
                            {/* Stats and Actions Row */}
                            <div className="flex flex-wrap items-start justify-between gap-4">
                                {/* Statistics */}
                                {stats && stats.length > 0 && (
                                    <div className="flex min-w-0 flex-wrap items-center gap-x-4 gap-y-2 text-sm text-muted-foreground">
                                        {stats.map((stat, index) => (
                                            <div key={index} className="flex items-center gap-2">
                                                {stat.icon}
                                                <span className="lowercase">{stat.title}</span>
                                                <span className="font-medium text-foreground">{stat.value}</span>
                                            </div>
                                        ))}
                                    </div>
                                )}

                                {/* Refresh Button */}
                                {onRefresh && (
                                    <Button variant="outline" size="sm" onClick={onRefresh} disabled={isRefreshing}>
                                        <RefreshCw className={`mr-2 h-4 w-4 ${isRefreshing ? 'animate-spin' : ''}`} />
                                        Sincronizar
                                    </Button>
                                )}
                            </div>
                        </div>
                    </CardHeader>

                    <CardContent>
                        {/* Search */}
                        {searchable && (
                            <div className="mb-6">
                                <div className="relative">
                                    <Label htmlFor="search" className="sr-only">Buscar</Label>
                                    <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                                    <Input
                                        id="search"
                                        placeholder={searchPlaceholder}
                                        value={search}
                                        onChange={(e) => setSearch(e.target.value)}
                                        className="pl-10 pr-10"
                                    />
                                    {search && (
                                        <button
                                            type="button"
                                            onClick={() => setSearch('')}
                                            className="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground"
                                        >
                                            <X className="h-4 w-4" />
                                        </button>
                                    )}
                                </div>
                            </div>
                        )}

                        {/* Desktop Table View */}
                        <div className={`hidden ${breakpointClass}block`}>
                            {isLoading && LoadingSkeleton ? (
                                <LoadingSkeleton rows={10} />
                            ) : (
                                <div className="rounded-md border">
                                    <Table>
                                        <TableHeader>
                                            <TableRow>
                                                {columns.map((column) => (
                                                    <TableHead
                                                        key={column.key}
                                                        className={`${column.width || ''} ${column.textAlign ? `text-${column.textAlign}` : 'text-left'} ${column.className || ''}`}
                                                    >
                                                        {column.title}
                                                    </TableHead>
                                                ))}
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            {filteredItems.length === 0 ? (
                                                <TableRow>
                                                    <TableCell colSpan={columns.length} className="h-32 text-center">
                                                        <div className="flex flex-col items-center justify-center space-y-2">
                                                            <p className="text-sm text-muted-foreground">No se encontraron resultados</p>
                                                            {search && (
                                                                <p className="text-xs text-muted-foreground">
                                                                    Intenta con términos de búsqueda diferentes
                                                                </p>
                                                            )}
                                                        </div>
                                                    </TableCell>
                                                </TableRow>
                                            ) : (
                                                filteredItems.map((item) => (
                                                    <TableRow key={item.id}>
                                                        {columns.map((column) => (
                                                            <TableCell
                                                                key={column.key}
                                                                className={`${column.width || ''} ${column.textAlign ? `text-${column.textAlign}` : 'text-left'} ${column.className || ''}`}
                                                            >
                                                                {column.render
                                                                    ? column.render(item)
                                                                    : String((item as Record<string, unknown>)[column.key] ?? '')}
                                                            </TableCell>
                                                        ))}
                                                    </TableRow>
                                                ))
                                            )}
                                        </TableBody>
                                    </Table>
                                </div>
                            )}
                        </div>

                        {/* Mobile Card View */}
                        {renderMobileCard && (
                            <div className={`${breakpointClass}hidden`}>
                                <div className="grid gap-4">
                                    {filteredItems.length === 0 ? (
                                        <div className="flex flex-col items-center justify-center space-y-2 py-12">
                                            <p className="text-sm text-muted-foreground">No se encontraron resultados</p>
                                            {search && (
                                                <p className="text-xs text-muted-foreground">
                                                    Intenta con términos de búsqueda diferentes
                                                </p>
                                            )}
                                        </div>
                                    ) : (
                                        filteredItems.map((item) => <div key={item.id}>{renderMobileCard(item)}</div>)
                                    )}
                                </div>
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </ErrorBoundary>
    );
}

// Export con tipado genérico
export const SimpleTable = SimpleTableComponent as <T extends { id: number | string }>(props: SimpleTableProps<T>) => React.ReactElement;
