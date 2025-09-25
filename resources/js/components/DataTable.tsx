import React, { useState, useCallback, useEffect } from 'react';
import { router, Link } from '@inertiajs/react';
import { Plus, Search, RefreshCw, ArrowUp, ArrowDown, ArrowUpDown } from 'lucide-react';

import { Card, CardContent, CardHeader } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Button } from '@/components/ui/button';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import { PaginationWrapper } from '@/components/PaginationWrapper';

// Professional column width system based on CSS Grid and Flexbox standards
const columnWidthConfig = {
  'xs': 'w-16 min-w-16 max-w-16',         // 64px - Actions, icons
  'sm': 'w-24 min-w-24 max-w-24',         // 96px - Status, dates
  'md': 'w-32 min-w-32 max-w-48',         // 128-192px - Short text
  'lg': 'w-48 min-w-48 max-w-64',         // 192-256px - Names, emails
  'xl': 'w-64 min-w-64 max-w-80',         // 256-320px - Long content
  'auto': 'w-auto min-w-0',               // Content-based
  'full': 'w-full min-w-0'                // Full width
} as const;

const textAlignmentConfig = {
  'left': 'text-left',
  'center': 'text-center',
  'right': 'text-right'
} as const;

interface DataTableColumn<T> {
  key: string;
  title: string;
  width?: keyof typeof columnWidthConfig;
  align?: keyof typeof textAlignmentConfig;
  truncate?: boolean | number;
  sortable?: boolean;
  render?: (item: T, value: unknown) => React.ReactNode;
  className?: string;
}

interface DataTableStat {
  title: string;
  value: number | string;
  icon: React.ReactNode;
  description?: string;
}

interface PaginatedData<T> {
  data: T[];
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
  from: number;
  to: number;
}

interface DataTableFilters {
  search?: string | null;
  per_page: number;
  sort_field?: string;
  sort_direction?: 'asc' | 'desc';
}

interface DataTableProps<T> {
  title: string;
  description: string;
  data: PaginatedData<T>;
  columns: DataTableColumn<T>[];
  stats?: DataTableStat[];
  filters: DataTableFilters;
  createUrl?: string;
  createLabel?: string;
  searchPlaceholder?: string;
  loadingSkeleton?: React.ComponentType<{ rows: number }>;
  renderMobileCard?: (item: T) => React.ReactNode;
  onRefresh?: () => void;
  routeName: string;
  breakpoint?: 'sm' | 'md' | 'lg' | 'xl';
}

/**
 * Custom hook for debouncing input values
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
 * Truncated text component with tooltip for overflow content
 */
interface TruncatedTextProps {
  children: React.ReactNode;
  maxLength?: number;
  className?: string;
}

const TruncatedText: React.FC<TruncatedTextProps> = ({
  children,
  maxLength,
  className = ""
}) => {
  const text = typeof children === 'string' ? children : String(children);
  const shouldTruncate = maxLength && text.length > maxLength;

  if (!shouldTruncate) {
    return <span className={className}>{children}</span>;
  }

  return (
    <TooltipProvider>
      <Tooltip>
        <TooltipTrigger asChild>
          <span className={`truncate cursor-help ${className}`} style={{ maxWidth: `${maxLength}ch` }}>
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

export function DataTable<T extends { id: number | string }>({
  title,
  description,
  data,
  columns,
  stats,
  filters,
  createUrl,
  createLabel = "Create New",
  searchPlaceholder = "Search...",
  loadingSkeleton: LoadingSkeleton,
  renderMobileCard,
  onRefresh,
  routeName,
  breakpoint = 'md'
}: DataTableProps<T>) {
  const [search, setSearch] = useState<string>(filters.search || '');
  const [perPage, setPerPage] = useState<number>(filters.per_page);
  const [sortField, setSortField] = useState<string>(filters.sort_field || 'created_at');
  const [sortDirection, setSortDirection] = useState<'asc' | 'desc'>(filters.sort_direction || 'desc');
  const [isLoading, setIsLoading] = useState<boolean>(false);
  const [isRefreshing, setIsRefreshing] = useState<boolean>(false);

  const debouncedSearch = useDebounce(search, 500);
  const breakpointClass = breakpoint === 'md' ? 'md:' : `${breakpoint}:`;

  /**
   * Updates filters in URL with state preservation
   */
  const updateFilters = useCallback((newFilters: Record<string, string | number | undefined>) => {
    setIsLoading(true);
    router.get(routeName, newFilters, {
      preserveState: true,
      replace: true,
      onFinish: () => setIsLoading(false),
    });
  }, [routeName]);

  /**
   * Effect for handling debounced search and filter updates
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
   * Handles column sorting with direction toggle
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
   * Returns appropriate sort icon based on current sort state
   */
  const getSortIcon = (field: string) => {
    if (field !== sortField) {
      return <ArrowUpDown className="h-4 w-4 text-muted-foreground/50" />;
    }
    return sortDirection === 'asc'
      ? <ArrowUp className="h-4 w-4 text-primary" />
      : <ArrowDown className="h-4 w-4 text-primary" />;
  };

  /**
   * Refreshes data with current filters
   */
  const refreshData = () => {
    if (onRefresh) {
      onRefresh();
    } else {
      setIsRefreshing(true);
      router.get(routeName, {
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

  /**
   * Renders cell content with intelligent truncation
   */
  const renderCellContent = (column: DataTableColumn<T>, item: T) => {
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
    <div className="flex h-full flex-1 flex-col gap-6 p-6">
      {/* Page Header */}
      <div className="flex items-center justify-between">
        <div className="space-y-1">
          <h1 className="text-3xl font-bold tracking-tight">{title}</h1>
          <p className="text-muted-foreground">{description}</p>
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
            <div className="flex items-start justify-between flex-wrap gap-4">
              {/* Statistics */}
              {stats && stats.length > 0 && (
                <div className="flex flex-wrap items-center gap-x-6 gap-y-2 text-sm text-muted-foreground min-w-0">
                  {stats.map((stat, index) => (
                    <div key={index} className="flex items-center gap-2 flex-shrink-0">
                      {React.cloneElement(stat.icon as React.ReactElement<{ className?: string }>, {
                        className: "h-4 w-4 flex-shrink-0"
                      })}
                      <span className="whitespace-nowrap">
                        <span className="lowercase">{stat.title}</span>{' '}
                        <span className="font-medium text-foreground tabular-nums">{stat.value}</span>
                      </span>
                    </div>
                  ))}
                </div>
              )}

              {/* Refresh Button with Last Sync */}
              <div className="flex flex-col items-end gap-1 flex-shrink-0">
                <Button
                  variant="ghost"
                  size="sm"
                  onClick={refreshData}
                  disabled={isRefreshing}
                  className="h-8 px-2"
                >
                  <RefreshCw className={`h-4 w-4 mr-1 ${isRefreshing ? 'animate-spin' : ''}`} />
                  {isRefreshing ? 'Sincronizando...' : 'Sincronizar'}
                </Button>
                <span className="text-xs text-muted-foreground">
                  Última: {new Date().toLocaleString('es-GT', {
                    hour: '2-digit',
                    minute: '2-digit',
                    second: '2-digit',
                    day: '2-digit',
                    month: '2-digit'
                  })}
                </span>
              </div>
            </div>
          </div>
        </CardHeader>

        <CardContent>
          {/* Search and Filters */}
          <div className="flex flex-col sm:flex-row gap-4 mb-6">
            <div className="flex-1">
              <Label htmlFor="search" className="sr-only">
                Search
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
              <Select
                value={perPage.toString()}
                onValueChange={(value) => setPerPage(parseInt(value))}
              >
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

          {/* Table Content */}
          {isLoading && LoadingSkeleton ? (
            <LoadingSkeleton rows={perPage} />
          ) : (
            <>
              {/* Desktop Table View */}
              <div className={`hidden ${breakpointClass}block`}>
                <div className="rounded-md border">
                  <Table>
                    <TableHeader>
                      <TableRow>
                        {columns.map((column) => (
                          <TableHead
                            key={column.key}
                            className={`
                              ${column.width ? columnWidthConfig[column.width] : columnWidthConfig.auto}
                              ${column.align ? textAlignmentConfig[column.align] : textAlignmentConfig.left}
                              ${column.className || ''}
                              break-words whitespace-normal
                            `}
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
                            <div className="flex flex-col items-center justify-center space-y-2">
                              <p className="text-sm text-muted-foreground">
                                No se encontraron resultados
                              </p>
                              {search && (
                                <p className="text-xs text-muted-foreground">
                                  Intenta con términos de búsqueda diferentes
                                </p>
                              )}
                            </div>
                          </TableCell>
                        </TableRow>
                      ) : (
                        data.data.map((item) => (
                          <TableRow key={item.id}>
                            {columns.map((column) => (
                              <TableCell
                                key={column.key}
                                className={`
                                  ${column.align ? textAlignmentConfig[column.align] : textAlignmentConfig.left}
                                  ${column.className || ''}
                                  break-words whitespace-normal leading-relaxed py-4
                                `}
                                style={{
                                  wordWrap: 'break-word',
                                  overflowWrap: 'break-word',
                                  hyphens: 'auto'
                                }}
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

              {/* Mobile/Tablet Card View */}
              {renderMobileCard && (
                <div className={`${breakpointClass}hidden`}>
                  <div className="grid gap-4">
                    {data.data.length === 0 ? (
                      <div className="flex flex-col items-center justify-center py-12 space-y-3">
                        <p className="text-base text-muted-foreground">
                          No se encontraron resultados
                        </p>
                        {search && (
                          <p className="text-sm text-muted-foreground text-center">
                            Intenta con términos de búsqueda diferentes
                          </p>
                        )}
                      </div>
                    ) : (
                      data.data.map((item) => (
                        <div key={item.id}>
                          {renderMobileCard(item)}
                        </div>
                      ))
                    )}
                  </div>
                </div>
              )}

              {/* Pagination */}
              <PaginationWrapper
                data={data}
                routeName={routeName}
                filters={{
                  search,
                  per_page: perPage,
                  sort_field: sortField,
                  sort_direction: sortDirection
                }}
                className="mt-8"
              />
            </>
          )}
        </CardContent>
      </Card>
    </div>
  );
}