import { Link } from '@inertiajs/react';
import { Plus, RefreshCw, Search, X } from 'lucide-react';
import React, { useState } from 'react';

import { ErrorBoundary } from '@/components/ErrorBoundary';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';

interface SimpleGroupedTableColumn<T> {
    key: string;
    title: string;
    width?: string;
    textAlign?: 'left' | 'center' | 'right';
    render?: (item: T) => React.ReactNode;
    className?: string;
}

interface SimpleGroupedTableStat {
    title: string;
    value: number | string;
    icon: React.ReactNode;
}

interface CategoryGroup<T> {
    category: {
        id: number | null;
        name: string;
    };
    products: T[];
}

interface SimpleGroupedTableProps<T extends { id: number | string }> {
    title: string;
    description?: string;
    groupedData: CategoryGroup<T>[];
    columns: SimpleGroupedTableColumn<T>[];
    stats?: SimpleGroupedTableStat[];
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
 * Componente de tabla agrupada sin drag & drop
 */
function SimpleGroupedTableComponent<T extends { id: number | string }>({
    title,
    description,
    groupedData,
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
}: SimpleGroupedTableProps<T>) {
    const [search, setSearch] = useState('');

    const breakpointClass = breakpoint + ':';

    const filteredGroups =
        searchable && search
            ? groupedData
                  .map((group) => ({
                      ...group,
                      products: group.products.filter((item: T) => (item as { name?: string }).name?.toLowerCase().includes(search.toLowerCase())),
                  }))
                  .filter((group) => group.products.length > 0)
            : groupedData;

    return (
        <ErrorBoundary>
            <div className="mx-auto flex h-full max-w-7xl flex-1 flex-col gap-6 p-6">
                {/* Page Header */}
                <div className="flex items-center justify-between">
                    <div className="space-y-1">
                        <h1 className="text-3xl font-bold tracking-tight">{title}</h1>
                        {description && <p className="text-muted-foreground">{description}</p>}
                    </div>
                    <div className="flex items-center gap-2">
                        {onRefresh && (
                            <Button variant="outline" size="sm" onClick={onRefresh} disabled={isRefreshing}>
                                <RefreshCw className={`mr-2 h-4 w-4 ${isRefreshing ? 'animate-spin' : ''}`} />
                                Sincronizar
                            </Button>
                        )}
                        {createUrl && (
                            <Link href={createUrl}>
                                <Button>
                                    <Plus className="mr-2 h-4 w-4" />
                                    {createLabel}
                                </Button>
                            </Link>
                        )}
                    </div>
                </div>

                {/* Data Table Card */}
                <Card>
                    <CardHeader className="pb-6">
                        <div className="flex flex-col space-y-4">
                            {/* Stats */}
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

                            {/* Search */}
                            {searchable && (
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
                            )}
                        </div>
                    </CardHeader>

                    <CardContent className="p-0">
                        {isLoading && LoadingSkeleton ? (
                            <LoadingSkeleton rows={10} />
                        ) : (
                            <>
                                {/* Desktop view */}
                                <div className={`hidden ${breakpointClass}block`}>
                                    {filteredGroups.map((group) => (
                                        <div key={group.category.id ?? 'no-category'} className="border-b last:border-b-0">
                                            {/* Category Header */}
                                            <div className="border-b bg-muted/50 px-6 py-3">
                                                <h3 className="text-sm font-semibold text-foreground">{group.category.name}</h3>
                                            </div>

                                            {/* Products in this category */}
                                            <Table>
                                                <TableHeader>
                                                    <TableRow>
                                                        {columns.map((column) => (
                                                            <TableHead
                                                                key={column.key}
                                                                className={`${column.width || ''} ${column.textAlign ? `text-${column.textAlign}` : 'text-left'}`}
                                                            >
                                                                {column.title}
                                                            </TableHead>
                                                        ))}
                                                    </TableRow>
                                                </TableHeader>
                                                <TableBody>
                                                    {group.products.map((product) => (
                                                        <TableRow key={product.id}>
                                                            {columns.map((column) => (
                                                                <TableCell
                                                                    key={column.key}
                                                                    className={`${column.width || ''} ${column.textAlign ? `text-${column.textAlign}` : 'text-left'} ${column.className || ''}`}
                                                                >
                                                                    {column.render
                                                                        ? column.render(product)
                                                                        : String((product as Record<string, unknown>)[column.key] ?? '')}
                                                                </TableCell>
                                                            ))}
                                                        </TableRow>
                                                    ))}
                                                </TableBody>
                                            </Table>
                                        </div>
                                    ))}

                                    {filteredGroups.length === 0 && (
                                        <div className="flex flex-col items-center justify-center space-y-2 py-12">
                                            <p className="text-sm text-muted-foreground">No se encontraron resultados</p>
                                            {search && (
                                                <p className="text-xs text-muted-foreground">
                                                    Intenta con términos de búsqueda diferentes
                                                </p>
                                            )}
                                        </div>
                                    )}
                                </div>

                                {/* Mobile view */}
                                {renderMobileCard && (
                                    <div className={`${breakpointClass}hidden p-4`}>
                                        <div className="space-y-6">
                                            {filteredGroups.map((group) => (
                                                <div key={group.category.id ?? 'no-category'} className="space-y-4">
                                                    <div className="rounded-md bg-muted/50 px-4 py-2">
                                                        <h3 className="text-sm font-semibold text-foreground">{group.category.name}</h3>
                                                    </div>
                                                    <div className="grid gap-4">
                                                        {group.products.map((product) => (
                                                            <div key={product.id}>{renderMobileCard(product)}</div>
                                                        ))}
                                                    </div>
                                                </div>
                                            ))}

                                            {filteredGroups.length === 0 && (
                                                <div className="flex flex-col items-center justify-center space-y-2 py-12">
                                                    <p className="text-sm text-muted-foreground">No se encontraron resultados</p>
                                                    {search && (
                                                        <p className="text-xs text-muted-foreground">
                                                            Intenta con términos de búsqueda diferentes
                                                        </p>
                                                    )}
                                                </div>
                                            )}
                                        </div>
                                    </div>
                                )}
                            </>
                        )}
                    </CardContent>
                </Card>
            </div>
        </ErrorBoundary>
    );
}

export function SimpleGroupedTable<T extends { id: number | string }>(props: SimpleGroupedTableProps<T>) {
    return <SimpleGroupedTableComponent {...props} />;
}
