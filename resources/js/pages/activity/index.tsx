import { Head } from '@inertiajs/react';
import { useState } from 'react';
import { type DateRange } from 'react-day-picker';

import { DataTable } from '@/components/DataTable';
import { ActivitySkeleton } from '@/components/skeletons';
import { Badge } from '@/components/ui/badge';
import AppLayout from '@/layouts/app-layout';

import { EntityInfoCell } from '@/components/EntityInfoCell';
import { DateRangeFilterDialog, FilterDialog } from '@/components/FilterDialog';
import { StandardMobileCard } from '@/components/StandardMobileCard';
import { format } from 'date-fns';
import { es } from 'date-fns/locale';
import { Calendar as CalendarIcon, Filter, Users } from 'lucide-react';

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

const getActivityTypeColor = (type: string): string => {
    switch (type) {
        case 'login':
            return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300';
        case 'logout':
            return 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300';
        case 'page_view':
        case 'heartbeat':
            return 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-300';
        case 'user_created':
        case 'role_created':
        case 'customer_created':
        case 'customer_type_created':
            return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300';
        case 'user_updated':
        case 'role_updated':
        case 'role_users_updated':
        case 'customer_updated':
        case 'customer_type_updated':
        case 'theme_changed':
            return 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300';
        case 'user_deleted':
        case 'user_force_deleted':
        case 'role_deleted':
        case 'role_force_deleted':
        case 'customer_deleted':
        case 'customer_force_deleted':
        case 'customer_type_deleted':
        case 'customer_type_force_deleted':
            return 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300';
        case 'user_restored':
        case 'role_restored':
        case 'customer_restored':
        case 'customer_type_restored':
            return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300';
        case 'action':
            return 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300';
        default:
            return 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-300';
    }
};

const getActivityTypeText = (type: string): string => {
    switch (type) {
        case 'login':
            return 'Inicio de sesión';
        case 'logout':
            return 'Cierre de sesión';
        case 'page_view':
            return 'Vista de página';
        case 'heartbeat':
            return 'Actividad';
        case 'action':
            return 'Acción';
        case 'user_created':
            return 'Usuario creado';
        case 'user_updated':
            return 'Usuario actualizado';
        case 'user_deleted':
            return 'Usuario eliminado';
        case 'user_restored':
            return 'Usuario restaurado';
        case 'user_force_deleted':
            return 'Usuario eliminado permanentemente';
        case 'role_created':
            return 'Rol creado';
        case 'role_updated':
            return 'Rol actualizado';
        case 'role_deleted':
            return 'Rol eliminado';
        case 'role_restored':
            return 'Rol restaurado';
        case 'role_force_deleted':
            return 'Rol eliminado permanentemente';
        case 'role_users_updated':
            return 'Usuarios de rol actualizados';
        case 'theme_changed':
            return 'Tema cambiado';
        case 'customer_created':
            return 'Cliente creado';
        case 'customer_updated':
            return 'Cliente actualizado';
        case 'customer_deleted':
            return 'Cliente eliminado';
        case 'customer_restored':
            return 'Cliente restaurado';
        case 'customer_force_deleted':
            return 'Cliente eliminado permanentemente';
        case 'customer_type_created':
            return 'Tipo de cliente creado';
        case 'customer_type_updated':
            return 'Tipo de cliente actualizado';
        case 'customer_type_deleted':
            return 'Tipo de cliente eliminado';
        case 'customer_type_restored':
            return 'Tipo de cliente restaurado';
        case 'customer_type_force_deleted':
            return 'Tipo de cliente eliminado permanentemente';
        default:
            return type.charAt(0).toUpperCase() + type.slice(1).replace('_', ' ');
    }
};

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
        dateRange: undefined as DateRange | undefined,
    });

    const [eventTypesOpen, setEventTypesOpen] = useState(false);
    const [usersOpen, setUsersOpen] = useState(false);
    const [dateRangeOpen, setDateRangeOpen] = useState(false);
    const [userSearchTerm, setUserSearchTerm] = useState('');

    const columns = [
        {
            key: 'user',
            title: 'Usuario',
            width: 'lg' as const,
            sortable: true,
            render: (activity: ActivityData) => <UserInfoCell activity={activity} />,
        },
        {
            key: 'event_type',
            title: 'Actividad',
            width: 'md' as const,
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
            width: 'xl' as const,
            truncate: 60,
            render: (activity: ActivityData) => <div className="text-sm text-muted-foreground">{getEnhancedDescription(activity)}</div>,
        },
        {
            key: 'created_at',
            title: 'Fecha',
            width: 'md' as const,
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

    const FiltersDialog = () => (
        <>
            <FilterDialog
                placeholder="Seleccionar tipos..."
                icon={Filter}
                title="Seleccionar Tipos de Evento"
                description="Marca los tipos de evento que deseas filtrar"
                options={Object.entries(options.event_types).map(([key, label]) => ({
                    id: key,
                    label: label as string,
                }))}
                selectedIds={localFilters.event_types}
                onSelectionChange={(ids) => setLocalFilters((prev) => ({ ...prev, event_types: ids as string[] }))}
                isOpen={eventTypesOpen}
                onOpenChange={setEventTypesOpen}
            />

            <FilterDialog
                placeholder="Seleccionar usuarios..."
                icon={Users}
                title="Seleccionar Usuarios"
                description="Marca los usuarios que deseas filtrar"
                options={options.users.map((user) => ({
                    id: user.id.toString(),
                    label: user.name,
                    subtitle: user.email,
                }))}
                selectedIds={localFilters.user_ids}
                onSelectionChange={(ids) => setLocalFilters((prev) => ({ ...prev, user_ids: ids as string[] }))}
                isOpen={usersOpen}
                onOpenChange={setUsersOpen}
                searchEnabled={true}
                searchPlaceholder="Buscar usuarios..."
                searchTerm={userSearchTerm}
                onSearchChange={setUserSearchTerm}
            />

            <DateRangeFilterDialog
                isOpen={dateRangeOpen}
                onOpenChange={setDateRangeOpen}
                dateRange={localFilters.dateRange}
                onDateRangeChange={(range) => setLocalFilters((prev) => ({ ...prev, dateRange: range }))}
                formatButtonText={(range) => {
                    if (range?.from && range?.to) {
                        return `${format(range.from, 'dd/MM/yyyy', { locale: es })} - ${format(range.to, 'dd/MM/yyyy', { locale: es })}`;
                    }
                    return 'Seleccionar fechas...';
                }}
                icon={CalendarIcon}
                title="Seleccionar Rango de Fechas"
                description="Selecciona el período de fechas para filtrar los eventos"
            />
        </>
    );

    return (
        <AppLayout>
            <Head title="Actividad" />

            <DataTable
                title="Actividad del Sistema"
                description="Registro completo de todas las actividades y eventos del sistema."
                data={activities}
                columns={columns}
                stats={activityStats}
                filters={filters}
                searchPlaceholder="Buscar por nombre, descripción, tipo de evento..."
                loadingSkeleton={ActivitySkeleton}
                renderMobileCard={(activity) => <ActivityMobileCard activity={activity} />}
                routeName="/activity"
                breakpoint="lg"
            />

            <FiltersDialog />
        </AppLayout>
    );
}
