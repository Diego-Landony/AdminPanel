import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/react';

import AppLayout from '@/layouts/app-layout';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
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
        case 'user_created':
        case 'user_updated':
        case 'user_deleted':
        case 'user_restored':
        case 'user_force_deleted':
            return 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-300';
        case 'role_created':
        case 'role_updated':
        case 'role_deleted':
        case 'role_restored':
        case 'role_force_deleted':
        case 'role_users_updated':
            return 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300';
        case 'theme_changed':
            return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300';
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


    /**
     * Función para limpiar filtros
     */
    const clearFilters = () => {
        router.get('/audit', {
            search: '',
            event_type: '',
            user_id: '',
            date_range: 'last_14_days',
            per_page: 10,
        }, {
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
                        {/* Primera fila: Buscador centrado */}
                        <div className="flex justify-center">
                            <div className="w-full max-w-md">
                                <label className="text-sm font-medium text-center block mb-2">Buscar</label>
                                <Input
                                    placeholder="Buscar por nombre, descripción..."
                                    value={filters.search || ''}
                                    onChange={(e) => {
                                        const filterParams = {
                                            search: e.target.value,
                                            event_type: filters.event_type || '',
                                            user_id: filters.user_id || '',
                                            date_range: filters.date_range || 'last_14_days',
                                            per_page: filters.per_page || 10,
                                        };

                                        router.get('/audit', filterParams, {
                                            preserveState: true,
                                            preserveScroll: true,
                                        });
                                    }}
                                    className="mt-1 text-center"
                                />
                            </div>
                        </div>

                        {/* Segunda fila: Filtros organizados */}
                        <div className="grid gap-4 md:grid-cols-4">
                            <div>
                                <label className="text-sm font-medium">Tipo de Evento</label>
                                <Select
                                    value={filters.event_type || "all"}
                                    onValueChange={(value) => {
                                        router.get('/audit', {
                                            search: filters.search || '',
                                            event_type: value === "all" ? "" : value,
                                            user_id: filters.user_id || '',
                                            date_range: filters.date_range || 'last_14_days',
                                            per_page: filters.per_page || 10,
                                        }, {
                                            preserveState: true,
                                            preserveScroll: true,
                                        });
                                    }}
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
                                    value={filters.user_id || "all"}
                                    onValueChange={(value) => {
                                        router.get('/audit', {
                                            search: filters.search || '',
                                            event_type: filters.event_type || '',
                                            user_id: value === "all" ? "" : value,
                                            date_range: filters.date_range || 'last_14_days',
                                            per_page: filters.per_page || 10,
                                        }, {
                                            preserveState: true,
                                            preserveScroll: true,
                                        });
                                    }}
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
                                    value={filters.date_range || "last_14_days"}
                                    onValueChange={(value) => {
                                        router.get('/audit', {
                                            search: filters.search || '',
                                            event_type: filters.event_type || '',
                                            user_id: filters.user_id || '',
                                            date_range: value,
                                            per_page: filters.per_page || 10,
                                        }, {
                                            preserveState: true,
                                            preserveScroll: true,
                                        });
                                    }}
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

                            <div>
                                <label className="text-sm font-medium">Por página</label>
                                <Select
                                    value={filters.per_page?.toString() || "10"}
                                    onValueChange={(value) => {
                                        router.get('/audit', {
                                            search: filters.search || '',
                                            event_type: filters.event_type || '',
                                            user_id: filters.user_id || '',
                                            date_range: filters.date_range || 'last_14_days',
                                            per_page: parseInt(value),
                                        }, {
                                            preserveState: true,
                                            preserveScroll: true,
                                        });
                                    }}
                                >
                                    <SelectTrigger className="mt-1">
                                        <SelectValue placeholder="10" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {options.per_page_options.map((option) => (
                                            <SelectItem key={option} value={option.toString()}>
                                                {option}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>

                        <div className="flex gap-3 mt-4">
                            <Button variant="outline" onClick={clearFilters}>
                                <i className="fas fa-times mr-2"></i>
                                Limpiar Filtros
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
                                {activities.data && Array.isArray(activities.data) && activities.data.length > 0 ? (
                                    activities.data.map((activity) => (
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
                                    ))
                                ) : (
                                    <TableRow>
                                        <TableCell colSpan={4} className="text-center text-muted-foreground py-8">
                                            No hay actividades para mostrar
                                        </TableCell>
                                    </TableRow>
                                )}
                            </TableBody>
                        </Table>

                        {/* Paginación */}
                        {activities.last_page > 1 && (
                            <div className="mt-6">
                                <Pagination>
                                    <PaginationContent>
                                        <PaginationItem>
                                            <PaginationPrevious 
                                                href="#" 
                                                onClick={(e) => {
                                                    e.preventDefault();
                                                                                                const pageParams = {
                                                page: activities.current_page - 1,
                                                search: filters.search || '',
                                                event_type: filters.event_type || '',
                                                user_id: filters.user_id || '',
                                                date_range: filters.date_range || 'last_14_days',
                                                per_page: filters.per_page || 10,
                                            };

                                                    router.get('/audit', pageParams, {
                                                        preserveState: true,
                                                        preserveScroll: true,
                                                    });
                                                }}
                                                className={activities.current_page <= 1 ? 'pointer-events-none opacity-50' : ''}
                                            />
                                        </PaginationItem>
                                        
                                        {/* Primera página */}
                                        {activities.current_page > 3 && (
                                            <>
                                                <PaginationItem>
                                                    <PaginationLink 
                                                        href="#" 
                                                        onClick={(e) => {
                                                            e.preventDefault();
                                                            router.get('/audit', {
                                                                page: 1,
                                                                search: filters.search,
                                                                event_type: filters.event_type,
                                                                user_id: filters.user_id,
                                                                date_range: filters.date_range,
                                                                per_page: filters.per_page,
                                                            }, {
                                                                preserveState: true,
                                                                preserveScroll: true,
                                                            });
                                                        }}
                                                    >
                                                        1
                                                    </PaginationLink>
                                                </PaginationItem>
                                                {activities.current_page > 4 && (
                                                    <PaginationItem>
                                                        <PaginationEllipsis />
                                                    </PaginationItem>
                                                )}
                                            </>
                                        )}
                                        
                                        {/* Páginas alrededor de la actual */}
                                        {Array.from({ length: Math.min(3, activities.last_page) }, (_, i) => {
                                            const page = activities.current_page - 1 + i;
                                            if (page < 1 || page > activities.last_page) return null;
                                            
                                            return (
                                                <PaginationItem key={page}>
                                                    <PaginationLink 
                                                        href="#" 
                                                        onClick={(e) => {
                                                            e.preventDefault();
                                                            const pageParams = {
                                                                page: page,
                                                                search: filters.search || '',
                                                                event_type: filters.event_type || '',
                                                                user_id: filters.user_id || '',
                                                                date_range: filters.date_range || 'last_14_days',
                                                                per_page: filters.per_page || 10,
                                                            };

                                                            router.get('/audit', pageParams, {
                                                                preserveState: true,
                                                                preserveScroll: true,
                                                            });
                                                        }}
                                                        isActive={page === activities.current_page}
                                                    >
                                                        {page}
                                                    </PaginationLink>
                                                </PaginationItem>
                                            );
                                        })}
                                        
                                        {/* Última página */}
                                        {activities.current_page < activities.last_page - 2 && (
                                            <>
                                                {activities.current_page < activities.last_page - 3 && (
                                                    <PaginationItem>
                                                        <PaginationEllipsis />
                                                    </PaginationItem>
                                                )}
                                                <PaginationItem>
                                                    <PaginationLink 
                                                        href="#" 
                                                        onClick={(e) => {
                                                            e.preventDefault();
                                                            router.get('/audit', {
                                                                page: activities.last_page,
                                                                search: filters.search,
                                                                event_type: filters.event_type,
                                                                user_id: filters.user_id,
                                                                date_range: filters.date_range,
                                                                per_page: filters.per_page,
                                                            }, {
                                                                preserveState: true,
                                                                preserveScroll: true,
                                                            });
                                                        }}
                                                    >
                                                        {activities.last_page}
                                                    </PaginationLink>
                                                </PaginationItem>
                                            </>
                                        )}
                                        
                                        <PaginationItem>
                                            <PaginationNext 
                                                href="#" 
                                                onClick={(e) => {
                                                    e.preventDefault();
                                                    router.get('/audit', {
                                                        page: activities.current_page + 1,
                                                        search: filters.search,
                                                        event_type: filters.event_type,
                                                        user_id: filters.user_id,
                                                        date_range: filters.date_range,
                                                        per_page: filters.per_page,
                                                    }, {
                                                        preserveState: true,
                                                        preserveScroll: true,
                                                    });
                                                }}
                                                className={activities.current_page >= activities.last_page ? 'pointer-events-none opacity-50' : ''}
                                            />
                                        </PaginationItem>
                                    </PaginationContent>
                                </Pagination>
                                
                                <div className="text-center text-sm text-muted-foreground mt-4">
                                    Página {activities.current_page} de {activities.last_page} - 
                                    Mostrando {activities.from} a {activities.to} de {activities.total} eventos
                                </div>
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}