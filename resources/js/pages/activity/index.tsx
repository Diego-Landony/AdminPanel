import { Head, router } from '@inertiajs/react';
import { useState, useCallback } from 'react';

import { ActivitySkeleton } from '@/components/skeletons';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { PLACEHOLDERS } from '@/constants/ui-constants';
import AppLayout from '@/layouts/app-layout';

import { EntityInfoCell } from '@/components/EntityInfoCell';
import { DateRangeFilterDialog, FilterDialog, type DateRange } from '@/components/FilterDialog';
import { PaginationWrapper } from '@/components/PaginationWrapper';
import { StandardMobileCard } from '@/components/StandardMobileCard';
import { ACTIVITY_CONFIG } from '@/config/activity-config';
import { format } from 'date-fns';
import { es } from 'date-fns/locale';
import { Calendar as CalendarIcon, Filter, RefreshCw, Search, Users, X } from 'lucide-react';

interface ActivityData {
    id: string;
    type: 'activity' | 'activity_log';
    user: {
        name: string;
        email: string;
        initials: string;
    };
    event_type: string;
    description: string;
    created_at: string;
    metadata?: Record<string, unknown> | null;
    old_values?: Record<string, unknown> | null;
    new_values?: Record<string, unknown> | null;
}

interface ActivityFilters {
    search: string;
    event_type: string;
    user_id: string;
    start_date?: string;
    end_date?: string;
    per_page: number;
    sort_field?: string;
    sort_direction?: 'asc' | 'desc';
}

interface ActivityOptions {
    event_types: Record<string, string>;
    users: Array<{ id: number; name: string; email: string }>;
    date_ranges: Record<string, string>;
    per_page_options: number[];
}

interface ActivityPageProps {
    activities: {
        data: ActivityData[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        from: number;
        to: number;
    };
    filters: ActivityFilters;
    stats: {
        total_events: number;
        unique_users: number;
        today_events: number;
    };
    options: ActivityOptions;
}

interface ColumnDefinition {
    key: string;
    title: string;
    width: 'sm' | 'md' | 'lg' | 'xl';
    sortable?: boolean;
    truncate?: number;
    render: (activity: ActivityData) => React.ReactNode;
}

// Funciones simplificadas usando configuración centralizada
const getActivityTypeColor = (type: string): string => ACTIVITY_CONFIG.getColor(type);
const getActivityTypeText = (type: string): string => ACTIVITY_CONFIG.getLabel(type);

const formatDate = (dateString: string): string => {
    try {
        const date = new Date(dateString);
        return date.toLocaleDateString('es-GT', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
            timeZone: 'America/Guatemala',
        });
    } catch {
        return 'Fecha inválida';
    }
};

const HighlightedText: React.FC<{ text: string; type: 'old' | 'new' }> = ({ text, type }) => {
    const colorClasses =
        type === 'old'
            ? 'px-1 bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300 rounded text-xs'
            : 'px-1 bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300 rounded text-xs';

    return <span className={colorClasses}>{text}</span>;
};

const getEnhancedDescription = (activity: ActivityData): React.ReactElement => {
    const description = activity.description;

    if (!activity.old_values && !activity.new_values) {
        return <span>{description}</span>;
    }

    const oldValues = activity.old_values || {};
    const newValues = activity.new_values || {};

    if (activity.event_type === 'user_updated') {
        const oldName = (oldValues.name as string) || '';
        const newName = (newValues.name as string) || '';

        if (oldName && newName) {
            return (
                <span>
                    {"Usuario '"}
                    <HighlightedText text={oldName} type="old" />
                    {"' fue actualizado a '"}
                    <HighlightedText text={newName} type="new" />
                    {"'"}
                </span>
            );
        }
    }

    if (activity.event_type === 'user_created') {
        const newName = (newValues.name as string) || '';
        const newEmail = (newValues.email as string) || '';

        if (newName) {
            return (
                <span>
                    {"Usuario '"}
                    <HighlightedText text={newName} type="new" />
                    {"'"}
                    {newEmail && (
                        <span>
                            {' ('}
                            <HighlightedText text={newEmail} type="new" />
                            {')'}
                        </span>
                    )}
                    {' fue creado'}
                </span>
            );
        }
    }

    return <span>{description}</span>;
};

const UserInfoCell: React.FC<{ activity: ActivityData }> = ({ activity }) => (
    <EntityInfoCell icon={Users} primaryText={activity.user.name} secondaryText={activity.user.email} />
);

const ActivityMobileCard: React.FC<{ activity: ActivityData }> = ({ activity }) => (
    <StandardMobileCard
        icon={Users}
        title={activity.user.name}
        subtitle={activity.user.email}
        badge={{
            children: getActivityTypeText(activity.event_type),
            className: getActivityTypeColor(activity.event_type),
        }}
        dataFields={[
            {
                label: 'Descripción',
                value: <div className="text-sm">{getEnhancedDescription(activity)}</div>,
            },
            {
                label: 'Fecha',
                value: formatDate(activity.created_at),
            },
        ]}
    />
);

export default function ActivityIndex({ activities, filters, options, stats }: ActivityPageProps) {
    const [localFilters, setLocalFilters] = useState({
        event_types: filters.event_type ? filters.event_type.split(',').filter(Boolean) : ([] as string[]),
        user_ids: filters.user_id ? filters.user_id.split(',').filter(Boolean) : ([] as string[]),
        dateRange: (() => {
            if (filters.start_date && filters.end_date) {
                return {
                    from: new Date(filters.start_date),
                    to: new Date(filters.end_date),
                } as DateRange;
            }
            return undefined;
        })(),
    });

    const [eventTypesOpen, setEventTypesOpen] = useState(false);
    const [usersOpen, setUsersOpen] = useState(false);
    const [dateRangeOpen, setDateRangeOpen] = useState(false);
    const [userSearchTerm, setUserSearchTerm] = useState('');

    // Estado para la búsqueda y filtros
    const [search, setSearch] = useState<string>(filters.search || '');
    const [perPage, setPerPage] = useState<number>(filters.per_page);
    const [isLoading, setIsLoading] = useState<boolean>(false);
    const [isRefreshing, setIsRefreshing] = useState<boolean>(false);

    // Apply filters function - only called when search button is clicked
    const applyFilters = useCallback(() => {
        setIsLoading(true);
        router.post('/activity', {
            search: search || undefined,
            per_page: perPage,
            event_type: localFilters.event_types.join(',') || undefined,
            user_id: localFilters.user_ids.join(',') || undefined,
            start_date: localFilters.dateRange?.from ? format(localFilters.dateRange.from, 'yyyy-MM-dd') : undefined,
            end_date: localFilters.dateRange?.to ? format(localFilters.dateRange.to, 'yyyy-MM-dd') : undefined,
        }, {
            preserveState: true,
            replace: true,
            onFinish: () => setIsLoading(false),
        });
    }, [search, perPage, localFilters.event_types, localFilters.user_ids, localFilters.dateRange]);

    // Clear all filters function
    const clearFilters = useCallback(() => {
        setSearch('');
        setPerPage(10);
        setLocalFilters({
            event_types: [],
            user_ids: [],
            dateRange: undefined,
        });

        // Apply cleared filters immediately
        setIsLoading(true);
        router.post('/activity', {
            per_page: 10,
        }, {
            preserveState: true,
            replace: true,
            onFinish: () => setIsLoading(false),
        });
    }, []);

    // Check if any filters are active
    const hasActiveFilters = search || localFilters.event_types.length > 0 || localFilters.user_ids.length > 0 || localFilters.dateRange || perPage !== 10;

    const refreshData = () => {
        setIsRefreshing(true);
        router.post('/activity', {
            search: search || undefined,
            per_page: perPage,
            event_type: localFilters.event_types.join(',') || undefined,
            user_id: localFilters.user_ids.join(',') || undefined,
            start_date: localFilters.dateRange?.from ? format(localFilters.dateRange.from, 'yyyy-MM-dd') : undefined,
            end_date: localFilters.dateRange?.to ? format(localFilters.dateRange.to, 'yyyy-MM-dd') : undefined,
        }, {
            preserveState: true,
            replace: true,
            onFinish: () => setIsRefreshing(false),
        });
    };

    const columns: ColumnDefinition[] = [
        {
            key: 'user',
            title: 'Usuario',
            width: 'lg',
            sortable: true,
            render: (activity: ActivityData) => <UserInfoCell activity={activity} />,
        },
        {
            key: 'event_type',
            title: 'Actividad',
            width: 'md',
            sortable: true,
            render: (activity: ActivityData) => (
                <Badge className={`${getActivityTypeColor(activity.event_type)} px-3 py-1 text-xs font-medium`}>
                    {getActivityTypeText(activity.event_type)}
                </Badge>
            ),
        },
        {
            key: 'description',
            title: 'Descripción',
            width: 'xl',
            truncate: 60,
            render: (activity: ActivityData) => <div className="text-sm text-muted-foreground">{getEnhancedDescription(activity)}</div>,
        },
        {
            key: 'created_at',
            title: 'Fecha',
            width: 'md',
            sortable: true,
            render: (activity: ActivityData) => <span className="text-sm text-muted-foreground">{formatDate(activity.created_at)}</span>,
        },
    ];

    const activityStats = [
        {
            title: 'actividades',
            value: stats.total_events,
            icon: <Users className="h-3 w-3 text-primary" />,
        },
        {
            title: 'usuarios únicos',
            value: stats.unique_users,
            icon: <Users className="h-3 w-3 text-blue-600" />,
        },
        {
            title: 'eventos hoy',
            value: stats.today_events,
            icon: <CalendarIcon className="h-3 w-3 text-green-600" />,
        },
    ];

    return (
        <AppLayout>
            <Head title="Actividad" />

            <div className="flex h-full flex-1 flex-col gap-6 p-6">
                {/* Page Header */}
                <div className="flex items-center justify-between">
                    <div className="space-y-1">
                        <h1 className="text-3xl font-bold tracking-tight">Actividad</h1>
                        <p className="text-muted-foreground">Registro de eventos del sistema.</p>
                    </div>
                </div>

                {/* Data Table Card with Integrated Filters */}
                <Card>
                    <CardHeader className="pb-6">
                        <div className="flex flex-col space-y-4">
                            {/* Stats Row */}
                            <div className="flex flex-wrap items-start justify-between gap-4">
                                {/* Statistics */}
                                <div className="flex min-w-0 flex-wrap items-center gap-x-4 gap-y-2 text-sm text-muted-foreground">
                                    {activityStats.map((stat, index) => (
                                        <div key={index} className="flex max-w-[200px] min-w-0 flex-shrink-0 items-center gap-2">
                                            {stat.icon}
                                            <span className="flex min-w-0 items-center gap-1 overflow-hidden">
                                                <span className="truncate overflow-hidden text-ellipsis lowercase" title={stat.title}>
                                                    {stat.title}
                                                </span>
                                                <span className="font-medium whitespace-nowrap text-foreground tabular-nums" title={String(stat.value)}>
                                                    {stat.value}
                                                </span>
                                            </span>
                                        </div>
                                    ))}
                                </div>

                                {/* Refresh Button */}
                                <div className="flex flex-shrink-0 flex-col items-end gap-1">
                                    <Button variant="ghost" size="sm" onClick={refreshData} disabled={isRefreshing} className="h-8 px-2">
                                        <RefreshCw className={`mr-1 h-4 w-4 ${isRefreshing ? 'animate-spin' : ''}`} />
                                        {isRefreshing ? 'Sincronizando...' : 'Sincronizar'}
                                    </Button>
                                    <span className="text-xs text-muted-foreground">
                                        Última:{' '}
                                        {new Date().toLocaleString('es-GT', {
                                            hour: '2-digit',
                                            minute: '2-digit',
                                            second: '2-digit',
                                            hour12: true,
                                        })}
                                    </span>
                                </div>
                            </div>

                            {/* Integrated Filters Section */}
                            <div className="space-y-4">
                                {/* Search Bar */}
                                <div className="flex gap-2">
                                    <div className="flex-1">
                                        <Label htmlFor="search" className="sr-only">
                                            Buscar
                                        </Label>
                                        <div className="relative">
                                            <Search className="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                                            <Input
                                                id="search"
                                                placeholder={PLACEHOLDERS.searchUserEventDescription}
                                                value={search}
                                                onChange={(e) => setSearch(e.target.value)}
                                                className="pl-10 pr-10"
                                                disabled={isLoading}
                                                onKeyDown={(e) => {
                                                    if (e.key === 'Enter') {
                                                        applyFilters();
                                                    }
                                                }}
                                            />
                                            {search && (
                                                <button
                                                    type="button"
                                                    onClick={() => setSearch('')}
                                                    className="absolute top-1/2 right-3 -translate-y-1/2 text-muted-foreground hover:text-foreground transition-colors"
                                                    disabled={isLoading}
                                                >
                                                    <X className="h-4 w-4" />
                                                </button>
                                            )}
                                        </div>
                                    </div>
                                    <Button
                                        onClick={applyFilters}
                                        disabled={isLoading}
                                        className="flex-shrink-0"
                                    >
                                        <Search className="mr-2 h-4 w-4" />
                                        Buscar
                                    </Button>
                                    {hasActiveFilters && (
                                        <Button
                                            variant="outline"
                                            onClick={clearFilters}
                                            disabled={isLoading}
                                            className="flex-shrink-0"
                                        >
                                            <X className="mr-2 h-4 w-4" />
                                            Limpiar
                                        </Button>
                                    )}
                                </div>

                                {/* Advanced Filters Row */}
                                <div className="flex flex-col gap-2 sm:flex-row sm:flex-wrap">
                                    <FilterDialog
                                        placeholder={PLACEHOLDERS.searchEventTypes}
                                        icon={Filter}
                                        title="Seleccionar Tipos de Evento"
                                        description="Marca los tipos de evento que deseas filtrar"
                                        options={Object.entries(options.event_types).map(([key, label]) => ({
                                            id: key,
                                            label: label as string,
                                        }))}
                                        selectedIds={localFilters.event_types}
                                        onSelectionChange={(ids) => {
                                            setLocalFilters((prev) => ({ ...prev, event_types: ids as string[] }));
                                        }}
                                        isOpen={eventTypesOpen}
                                        onOpenChange={setEventTypesOpen}
                                        buttonVariant="outline"
                                    />

                                    <FilterDialog
                                        placeholder={PLACEHOLDERS.searchUsers}
                                        icon={Users}
                                        title="Seleccionar Usuarios"
                                        description="Marca los usuarios que deseas filtrar"
                                        options={options.users.map((user) => ({
                                            id: user.id.toString(),
                                            label: user.name,
                                            subtitle: user.email,
                                        }))}
                                        selectedIds={localFilters.user_ids}
                                        onSelectionChange={(ids) => {
                                            setLocalFilters((prev) => ({ ...prev, user_ids: ids as string[] }));
                                        }}
                                        isOpen={usersOpen}
                                        onOpenChange={setUsersOpen}
                                        searchEnabled={true}
                                        searchPlaceholder="Buscar usuarios..."
                                        searchTerm={userSearchTerm}
                                        onSearchChange={setUserSearchTerm}
                                        buttonVariant="outline"
                                    />

                                    <DateRangeFilterDialog
                                        isOpen={dateRangeOpen}
                                        onOpenChange={setDateRangeOpen}
                                        dateRange={localFilters.dateRange}
                                        onDateRangeChange={(range) => {
                                            setLocalFilters((prev) => ({ ...prev, dateRange: range }));
                                        }}
                                        formatButtonText={(range) => {
                                            if (range?.from && range?.to) {
                                                return `${format(range.from, 'dd/MM/yyyy', { locale: es })} - ${format(range.to, 'dd/MM/yyyy', { locale: es })}`;
                                            }
                                            return 'Fechas...';
                                        }}
                                        icon={CalendarIcon}
                                        title="Seleccionar Rango de Fechas"
                                        description="Selecciona el período de fechas para filtrar los eventos"
                                    />

                                    <Select value={perPage.toString()} onValueChange={(value) => setPerPage(parseInt(value))}>
                                        <SelectTrigger className="w-[120px]">
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
                        </div>
                    </CardHeader>

                    <CardContent>
                        {/* Table Content */}
                        {isLoading ? (
                            <ActivitySkeleton rows={perPage} />
                        ) : (
                            <>
                                {/* Desktop Table View */}
                                <div className="hidden lg:block">
                                    <div className="rounded-md border">
                                        <Table>
                                            <TableHeader>
                                                <TableRow>
                                                    {columns.map((column) => (
                                                        <TableHead key={column.key} className="break-words whitespace-normal">
                                                            {column.title}
                                                        </TableHead>
                                                    ))}
                                                </TableRow>
                                            </TableHeader>

                                            <TableBody>
                                                {activities.data.length === 0 ? (
                                                    <TableRow>
                                                        <TableCell colSpan={columns.length} className="h-40 md:h-32 text-center">
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
                                                    activities.data.map((activity) => (
                                                        <TableRow key={activity.id}>
                                                            {columns.map((column) => (
                                                                <TableCell
                                                                    key={column.key}
                                                                    className="py-5 md:py-4 leading-relaxed break-words whitespace-normal"
                                                                >
                                                                    {column.render(activity)}
                                                                </TableCell>
                                                            ))}
                                                        </TableRow>
                                                    ))
                                                )}
                                            </TableBody>
                                        </Table>
                                    </div>
                                </div>

                                {/* Mobile Card View */}
                                <div className="lg:hidden">
                                    <div className="grid gap-4">
                                        {activities.data.length === 0 ? (
                                            <div className="flex flex-col items-center justify-center space-y-3 py-16 md:py-12">
                                                <p className="text-base text-muted-foreground">No se encontraron resultados</p>
                                                {search && (
                                                    <p className="text-center text-sm text-muted-foreground">
                                                        Intenta con términos de búsqueda diferentes
                                                    </p>
                                                )}
                                            </div>
                                        ) : (
                                            activities.data.map((activity) => (
                                                <div key={activity.id}>
                                                    <ActivityMobileCard activity={activity} />
                                                </div>
                                            ))
                                        )}
                                    </div>
                                </div>

                                {/* Pagination */}
                                <PaginationWrapper
                                    data={activities}
                                    routeName="/activity"
                                    filters={{
                                        search,
                                        per_page: perPage,
                                        event_type: localFilters.event_types.join(',') || undefined,
                                        user_id: localFilters.user_ids.join(',') || undefined,
                                        start_date: localFilters.dateRange?.from ? format(localFilters.dateRange.from, 'yyyy-MM-dd') : undefined,
                                        end_date: localFilters.dateRange?.to ? format(localFilters.dateRange.to, 'yyyy-MM-dd') : undefined,
                                    }}
                                    className="mt-8"
                                />
                            </>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
