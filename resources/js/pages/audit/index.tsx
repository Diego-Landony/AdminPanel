import { type BreadcrumbItem } from '@/types';
import { Head, router, useForm } from '@inertiajs/react';
import { useState } from 'react';

import AppLayout from '@/layouts/app-layout';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';

/**
 * Breadcrumbs para la navegación de auditoría
 */
const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Usuarios',
        href: '/users',
    },
    {
        title: 'Actividad',
        href: '/audit',
    },
];

/**
 * Interfaz para los datos de actividad y auditoría
 */
interface Activity {
    id: string;
    type: 'activity' | 'audit';
    user: {
        name: string;
        email: string;
        initials: string;
    };
    event_type: string;
    description: string;
    created_at: string;
    ip_address: string | null;
    metadata: any;
    old_values: any;
    new_values: any;
}

/**
 * Interfaz para las estadísticas
 */
interface Stats {
    total_events: number;
    unique_users: number;
    today_events: number;
}

/**
 * Interfaz para las opciones de filtros
 */
interface Options {
    event_types: Record<string, string>;
    users: Array<{ id: number; name: string; email: string }>;
    date_ranges: Record<string, string>;
    per_page_options: number[];
}

/**
 * Interfaz para los filtros actuales
 */
interface Filters {
    search: string;
    event_type: string;
    user_id: string;
    date_range: string;
    per_page: number;
}

/**
 * Interfaz para las props de la página
 */
interface AuditPageProps {
    activities: {
        data: Activity[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        from: number;
        to: number;
    };
    filters: Filters;
    stats: Stats;
    options: Options;
}

/**
 * Obtiene el color del badge según el tipo de actividad
 */
const getActivityTypeColor = (type: string): string => {
    switch (type) {
        case 'login':
            return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300';
        case 'logout':
            return 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300';
        case 'page_view':
            return 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300';
        case 'heartbeat':
            return 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-300';
        case 'action':
            return 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300';
        default:
            return 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-300';
    }
};

/**
 * Obtiene el texto legible del tipo de actividad
 */
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
        default:
            return type.charAt(0).toUpperCase() + type.slice(1).replace('_', ' ');
    }
};

/**
 * Formatea la fecha de manera legible en hora de Guatemala
 */
const formatDate = (dateString: string): string => {
    try {
        const date = new Date(dateString);
        
        return date.toLocaleDateString('es-GT', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
            timeZone: 'America/Guatemala'
        });
    } catch {
        return 'Fecha inválida';
    }
};

/**
 * Página de auditoría de actividades
 * Muestra todos los eventos y actividades del sistema con filtros y búsqueda
 */
export default function AuditIndex({ activities, filters, stats, options }: AuditPageProps) {
    const [localFilters, setLocalFilters] = useState(filters);

    /**
     * Función para aplicar filtros
     */
    const applyFilters = () => {
        const filtersToSend = {
            ...localFilters,
            event_type: localFilters.event_type === 'all' ? '' : localFilters.event_type,
            user_id: localFilters.user_id === 'all' ? '' : localFilters.user_id,
        };
        
        router.get('/audit', filtersToSend, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    /**
     * Función para limpiar filtros
     */
    const clearFilters = () => {
        const clearedFilters = {
            search: '',
            event_type: 'all',
            user_id: 'all',
            date_range: 'last_14_days',
            per_page: 10,
        };
        setLocalFilters(clearedFilters);
        router.get('/audit', clearedFilters);
    };

    /**
     * Función para cambiar página
     */
    const changePage = (page: number) => {
        router.get('/audit', { ...localFilters, page }, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Actividad" />
            
            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 overflow-x-auto">
                {/* Estadísticas */}
                <div className="grid gap-4 md:grid-cols-3">
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Total de Eventos</CardTitle>
                            <i className="fas fa-chart-line text-muted-foreground"></i>
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{stats.total_events}</div>
                            <p className="text-xs text-muted-foreground">
                                Eventos registrados en el sistema
                            </p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Usuarios Únicos</CardTitle>
                            <i className="fas fa-users text-muted-foreground"></i>
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{stats.unique_users}</div>
                            <p className="text-xs text-muted-foreground">
                                Usuarios con actividad registrada
                            </p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Eventos de Hoy</CardTitle>
                            <i className="fas fa-calendar-day text-muted-foreground"></i>
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{stats.today_events}</div>
                            <p className="text-xs text-muted-foreground">
                                Actividad del día actual
                            </p>
                        </CardContent>
                    </Card>
                </div>

                {/* Filtros */}
                <Card>
                    <CardHeader>
                        <CardTitle>Filtros de Búsqueda</CardTitle>
                        <CardDescription>
                            Filtra los eventos por usuario, tipo, fecha o busca por términos específicos
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                            <div>
                                <label className="text-sm font-medium">Buscar</label>
                                <Input
                                    placeholder="Buscar por nombre, descripción..."
                                    value={localFilters.search}
                                    onChange={(e) => setLocalFilters(prev => ({ ...prev, search: e.target.value }))}
                                    className="mt-1"
                                />
                            </div>

                            <div>
                                <label className="text-sm font-medium">Tipo de Evento</label>
                                <Select
                                    value={localFilters.event_type || "all"}
                                    onValueChange={(value) => setLocalFilters(prev => ({ ...prev, event_type: value === "all" ? "" : value }))}
                                >
                                    <SelectTrigger className="mt-1">
                                        <SelectValue placeholder="Todos los tipos" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">Todos los tipos</SelectItem>
                                        {Object.entries(options.event_types).map(([key, label]) => (
                                            <SelectItem key={key} value={key}>{label}</SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>

                            <div>
                                <label className="text-sm font-medium">Usuario</label>
                                <Select
                                    value={localFilters.user_id || "all"}
                                    onValueChange={(value) => setLocalFilters(prev => ({ ...prev, user_id: value === "all" ? "" : value }))}
                                >
                                    <SelectTrigger className="mt-1">
                                        <SelectValue placeholder="Todos los usuarios" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">Todos los usuarios</SelectItem>
                                        {options.users.map((user) => (
                                            <SelectItem key={user.id} value={user.id.toString()}>
                                                {user.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>

                            <div>
                                <label className="text-sm font-medium">Período</label>
                                <Select
                                    value={localFilters.date_range}
                                    onValueChange={(value) => setLocalFilters(prev => ({ ...prev, date_range: value }))}
                                >
                                    <SelectTrigger className="mt-1">
                                        <SelectValue placeholder="Últimos 14 días" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {Object.entries(options.date_ranges).map(([key, label]) => (
                                            <SelectItem key={key} value={key}>{label}</SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>

                        <div className="flex gap-2 mt-4">
                            <Button onClick={applyFilters}>
                                <i className="fas fa-search mr-2"></i>
                                Aplicar Filtros
                            </Button>
                            <Button variant="outline" onClick={clearFilters}>
                                <i className="fas fa-times mr-2"></i>
                                Limpiar
                            </Button>
                        </div>
                    </CardContent>
                </Card>

                {/* Tabla de actividades */}
                <Card>
                    <CardHeader>
                        <CardTitle>Registro de Actividades</CardTitle>
                        <CardDescription>
                            {activities.total} eventos registrados - Mostrando {activities.from} a {activities.to}
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Usuario</TableHead>
                                    <TableHead>Evento</TableHead>
                                    <TableHead>Descripción</TableHead>
                                    <TableHead>Fecha</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {activities.data.map((activity) => (
                                    <TableRow key={activity.id}>
                                        <TableCell>
                                            <div>
                                                <div className="font-medium text-sm">{activity.user.name}</div>
                                                <div className="text-xs text-muted-foreground">
                                                    {activity.user.email}
                                                </div>
                                                <div className="text-xs text-muted-foreground mt-1">
                                                    {activity.ip_address || 'N/A'}
                                                </div>
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            <Badge className={`${getActivityTypeColor(activity.event_type)} px-2 py-1 text-xs`}>
                                                {getActivityTypeText(activity.event_type)}
                                            </Badge>
                                        </TableCell>
                                        <TableCell className="text-sm">
                                            <div>
                                                <div>{activity.description}</div>
                                                {activity.type === 'audit' && activity.old_values && activity.new_values && (
                                                    <div className="mt-2 space-y-1">
                                                        {Object.keys(activity.new_values).map(key => {
                                                            if (['name', 'email', 'password', 'timezone'].includes(key) && 
                                                                activity.old_values[key] !== undefined && 
                                                                activity.new_values[key] !== undefined && 
                                                                activity.old_values[key] !== activity.new_values[key]) {
                                                                return (
                                                                    <div key={key} className="text-xs text-muted-foreground">
                                                                        <span className="font-medium">{key}:</span>
                                                                        <span className="text-red-600 line-through ml-1">
                                                                            {key === 'password' ? '••••••••' : activity.old_values[key]}
                                                                        </span>
                                                                        <span className="text-green-600 ml-1">
                                                                            {key === 'password' ? '••••••••' : activity.new_values[key]}
                                                                        </span>
                                                                    </div>
                                                                );
                                                            }
                                                            return null;
                                                        })}
                                                    </div>
                                                )}
                                            </div>
                                        </TableCell>
                                        <TableCell className="text-sm text-muted-foreground">
                                            {formatDate(activity.created_at)}
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>

                        {/* Paginación */}
                        {activities.last_page > 1 && (
                            <div className="flex items-center justify-between mt-4">
                                <div className="text-sm text-muted-foreground">
                                    Página {activities.current_page} de {activities.last_page}
                                </div>
                                <div className="flex gap-2">
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        onClick={() => changePage(activities.current_page - 1)}
                                        disabled={activities.current_page <= 1}
                                    >
                                        <i className="fas fa-chevron-left mr-2"></i>
                                        Anterior
                                    </Button>
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        onClick={() => changePage(activities.current_page + 1)}
                                        disabled={activities.current_page >= activities.last_page}
                                    >
                                        Siguiente
                                        <i className="fas fa-chevron-right ml-2"></i>
                                    </Button>
                                </div>
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
