
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/react';
import { useState, useEffect } from 'react';
import { type DateRange } from 'react-day-picker';
import { toast } from "sonner";

import AppLayout from '@/layouts/app-layout';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { ScrollArea } from '@/components/ui/scroll-area';
import { ActivitySkeleton } from '@/components/skeletons';

import { format } from 'date-fns';
import { es } from 'date-fns/locale';
import { Search, X, Filter, Calendar as CalendarIcon, Users, Inbox } from 'lucide-react';
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
 * Breadcrumbs para la navegación de actividad
 */
const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Usuarios',
        href: '/users',
    },
    {
        title: 'Actividad',
        href: '/activity',
    },
];

/**
 * Interfaz para los datos de actividad del sistema
 */
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

/**
 * Interfaz para los filtros de actividad
 */
interface ActivityFilters {
    search: string;
    event_type: string;
    user_id: string;
    start_date?: string;
    end_date?: string;
    per_page: number;
}

/**
 * Interfaz para las opciones de filtros
 */
interface ActivityOptions {
    event_types: Record<string, string>;
    users: Array<{ id: number; name: string; email: string }>;
    date_ranges: Record<string, string>;
    per_page_options: number[];
}

/**
 * Interfaz para las props de la página de actividad
 */
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

/**
 * Obtiene el color del badge según el tipo de actividad - colores congruentes por categoria
 */
const getActivityTypeColor = (type: string): string => {
    switch (type) {
        // Autenticación - Verde para éxito
        case 'login':
            return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300';
        
        // Cierre de sesión - Azul para información
        case 'logout':
            return 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300';
        
        // Navegación - Gris para actividad normal
        case 'page_view':
        case 'heartbeat':
            return 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-300';
        
        // Creaciones - Verde para éxito/creación
        case 'user_created':
        case 'role_created':
            return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300';
        
        // Actualizaciones - Azul para información/cambios
        case 'user_updated':
        case 'role_updated':
        case 'role_users_updated':
        case 'theme_changed':
            return 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300';
        
        // Eliminaciones - Rojo para peligro/eliminación
        case 'user_deleted':
        case 'user_force_deleted':
        case 'role_deleted':
        case 'role_force_deleted':
            return 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300';
        
        // Restauraciones - Amarillo para advertencia/restauración
        case 'user_restored':
        case 'role_restored':
            return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300';
        
        // Acciones generales - Púrpura para acciones especiales
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
 * Componente para resaltar texto con color de fondo
 */
const HighlightedText: React.FC<{ text: string; type: 'old' | 'new' }> = ({ text, type }) => {
    const colorClasses = type === 'old' 
        ? 'px-1 bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300 rounded text-xs'
        : 'px-1 bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300 rounded text-xs';
    
    return <span className={colorClasses}>{text}</span>;
};

/**
 * Genera descripción con colores para mostrar el cambio principal
 */
const getEnhancedDescription = (activity: ActivityData): React.ReactElement => {
    const description = activity.description;
    
    // Si no hay valores anteriores o nuevos, mostrar descripción normal
    if (!activity.old_values && !activity.new_values) {
        return <span>{description}</span>;
    }

    const oldValues = activity.old_values || {};
    const newValues = activity.new_values || {};
    
    // Para usuarios actualizados
    if (activity.event_type === 'user_updated') {
        const oldName = oldValues.name as string || '';
        const newName = newValues.name as string || '';
        const oldEmail = oldValues.email as string || '';
        const newEmail = newValues.email as string || '';
        
        if (oldName || newName || oldEmail || newEmail) {
            const nameSection = oldName && newName ? (
                <span>
                    {"Usuario '"}<HighlightedText text={oldName} type="old" />{"' fue actualizado a '"}<HighlightedText text={newName} type="new" />{"'"}
                </span>
            ) : oldName ? (
                <span>
                    {"Usuario '"}<HighlightedText text={oldName} type="old" />{"' fue actualizado"}
                </span>
            ) : newName ? (
                <span>
                    {"Nuevo usuario '"}<HighlightedText text={newName} type="new" />{"' fue creado"}
                </span>
            ) : null;

            const emailSection = oldEmail && newEmail ? (
                <span>
                    {" Email '"}<HighlightedText text={oldEmail} type="old" />{"' fue actualizado a '"}<HighlightedText text={newEmail} type="new" />{"'"}
                </span>
            ) : oldEmail && !newEmail ? (
                <span>
                    {" Email '"}<HighlightedText text={oldEmail} type="old" />{"' fue eliminado"}
                </span>
            ) : !oldEmail && newEmail ? (
                <span>
                    {" Email '"}<HighlightedText text={newEmail} type="new" />{"' fue agregado"}
                </span>
            ) : null;

            return (
                <span>
                    {nameSection}
                    {emailSection}
                </span>
            );
        }
    }
    
    // Para roles actualizados
    if (activity.event_type === 'role_updated') {
        const oldName = oldValues.name as string || '';
        const newName = newValues.name as string || '';
        
        if (oldName && newName) {
            return (
                <span>
                    {"Rol '"}<HighlightedText text={oldName} type="old" />{"' fue actualizado a '"}<HighlightedText text={newName} type="new" />{"'"}
                </span>
            );
        } else if (newName) {
            return (
                <span>
                    {"Nuevo rol '"}<HighlightedText text={newName} type="new" />{"' fue creado"}
                </span>
            );
        }
    }
    
    // Para roles creados
    if (activity.event_type === 'role_created') {
        const newName = newValues.name as string || '';
        
        if (newName) {
            return (
                <span>
                    {"Rol '"}<HighlightedText text={newName} type="new" />{"' fue creado"}
                </span>
            );
        }
    }
    
    // Para usuarios creados
    if (activity.event_type === 'user_created') {
        const newName = newValues.name as string || '';
        const newEmail = newValues.email as string || '';
        
        if (newName || newEmail) {
            return (
                <span>
                    {"Usuario '"}<HighlightedText text={newName} type="new" />{"'"}
                    {newEmail && (
                        <span>
                            {" ("}<HighlightedText text={newEmail} type="new" />{")"}
                        </span>
                    )}
                    {" fue creado"}
                </span>
            );
        }
    }
    
    // Para usuarios eliminados
    if (activity.event_type === 'user_deleted' || activity.event_type === 'user_force_deleted') {
        const oldName = oldValues.name as string || '';
        const oldEmail = oldValues.email as string || '';
        
        if (oldName || oldEmail) {
            return (
                <span>
                    {"Usuario '"}<HighlightedText text={oldName} type="old" />{"'"}
                    {oldEmail && (
                        <span>
                            {" ("}<HighlightedText text={oldEmail} type="old" />{")"}
                        </span>
                    )}
                    {" fue eliminado"}
                    {activity.event_type === 'user_force_deleted' && " permanentemente"}
                </span>
            );
        }
    }
    
    // Para roles eliminados
    if (activity.event_type === 'role_deleted' || activity.event_type === 'role_force_deleted') {
        const oldName = oldValues.name as string || '';
        
        if (oldName) {
            return (
                <span>
                    {"Rol '"}<HighlightedText text={oldName} type="old" />{"' fue eliminado"}
                    {activity.event_type === 'role_force_deleted' && " permanentemente"}
                </span>
            );
        }
    }
    
    // Descripción por defecto
    return <span>{description}</span>;
};



/**
 * Página de actividad del sistema
 * Muestra todos los eventos y actividades del sistema con filtros y búsqueda
 */
export default function ActivityIndex({ activities, filters, options }: ActivityPageProps) {

    // Estado local para los filtros
    const [localFilters, setLocalFilters] = useState({
        search: filters.search || '',
        event_types: filters.event_type ? filters.event_type.split(',').filter(Boolean) : [] as string[],
        user_ids: filters.user_id ? filters.user_id.split(',').filter(Boolean) : [] as string[],
        dateRange: undefined as DateRange | undefined,
        per_page: filters.per_page || 10,
    });

    // Estado para el loading
    const [isLoading, setIsLoading] = useState(false);

    // Estado para los diálogos
    const [eventTypesOpen, setEventTypesOpen] = useState(false);
    const [usersOpen, setUsersOpen] = useState(false);
    const [dateRangeOpen, setDateRangeOpen] = useState(false);

    // Estado para búsqueda en usuarios
    const [userSearchTerm, setUserSearchTerm] = useState('');
    const [searchValue, setSearchValue] = useState(filters.search || '');

    // Las notificaciones flash se manejan automáticamente por el layout

    // Sincronizar searchValue con filtros actuales al cargar
    useEffect(() => {
        setSearchValue(filters.search || '');
    }, [filters.search]);

    /**
     * Función para aplicar filtros (búsqueda global)
     */
    const applyFilters = () => {
        setIsLoading(true);
        
        // Actualizar localFilters con el valor actual de búsqueda
        const updatedFilters = {
            ...localFilters,
            search: searchValue
        };

        // Actualizar el estado local para reflejar los filtros aplicados
        setLocalFilters(updatedFilters);

        const filterParams = {
            search: searchValue,
            event_type: updatedFilters.event_types.join(','),
            user_id: updatedFilters.user_ids.join(','),
            start_date: updatedFilters.dateRange?.from ? format(updatedFilters.dateRange.from, 'yyyy-MM-dd') : '',
            end_date: updatedFilters.dateRange?.to ? format(updatedFilters.dateRange.to, 'yyyy-MM-dd') : '',
            per_page: updatedFilters.per_page,
        };

        router.get('/activity', filterParams, {
            preserveState: true,
            preserveScroll: true,
            onSuccess: (page) => {
                setIsLoading(false);
                // Verificar si no hay resultados
                const activities = page.props.activities as { total?: number };
                if (activities && activities.total === 0) {
                    // Verificar si hay filtros aplicados
                    const hasFilters = Object.entries(filterParams).some(([key, val]) => {
                        if (key === 'per_page') return false; // Ignorar per_page
                        return val !== '' && val !== undefined && val !== null;
                    });
                    
                    if (hasFilters) {
                        let message = "No se encontraron resultados";
                        let description = "Intenta ajustar los criterios de búsqueda";
                        
                        if (searchValue.trim() !== '') {
                            message = `No se encontraron resultados para: "${searchValue}"`;
                            description = "Intenta con otros términos de búsqueda";
                        }
                        
                        toast.info(message, { description });
                    }
                }
            },
            onError: () => {
                setIsLoading(false);
                toast.error("Error al cargar los datos de actividad");
            }
        });
    };

    /**
     * Función para limpiar filtros
     */
    const clearFilters = () => {
        setSearchValue('');
        setLocalFilters({
            search: '',
            event_types: [],
            user_ids: [],
            dateRange: undefined,
            per_page: 10,
        });
        setUserSearchTerm('');

        router.get('/activity', {
            search: '',
            event_type: '',
            user_id: '',
            start_date: '',
            end_date: '',
            per_page: 10,
        }, {
            preserveState: true,
            preserveScroll: true,
            onSuccess: (page) => {
                // Verificar si no hay resultados
                const activities = page.props.activities as { total?: number };
                if (activities && activities.total === 0) {
                    toast.info("No se encontraron resultados", {
                        description: "No hay actividades registradas en el sistema"
                    });
                }
            }
        });
    };

    /**
     * Función para manejar cambios en checkboxes de tipos de evento
     */
    const handleEventTypeChange = (eventType: string, checked: boolean) => {
        if (checked) {
            setLocalFilters(prev => ({
                ...prev,
                event_types: [...prev.event_types, eventType]
            }));
        } else {
            setLocalFilters(prev => ({
                ...prev,
                event_types: prev.event_types.filter(type => type !== eventType)
            }));
        }
    };

    /**
     * Función para manejar cambios en checkboxes de usuarios
     */
    const handleUserChange = (userId: string, checked: boolean) => {
        if (checked) {
            setLocalFilters(prev => ({
                ...prev,
                user_ids: [...prev.user_ids, userId]
            }));
        } else {
            setLocalFilters(prev => ({
                ...prev,
                user_ids: prev.user_ids.filter(id => id !== userId)
            }));
        }
    };



    /**
     * Obtener texto para mostrar tipos de evento seleccionados
     */
    const getEventTypesText = () => {
        if (localFilters.event_types.length === 0) return 'Seleccionar tipos...';
        if (localFilters.event_types.length === 1) return options.event_types[localFilters.event_types[0]];
        if (localFilters.event_types.length <= 3) {
            return localFilters.event_types.map(type => options.event_types[type]).join(', ');
        }
        return `${localFilters.event_types.length} tipos seleccionados`;
    };

    /**
     * Obtener texto para mostrar usuarios seleccionados
     */
    const getUsersText = () => {
        if (localFilters.user_ids.length === 0) return 'Seleccionar usuarios...';
        if (localFilters.user_ids.length === 1) {
            const user = options.users.find(u => u.id.toString() === localFilters.user_ids[0]);
            return user ? user.name : 'Usuario no encontrado';
        }
        if (localFilters.user_ids.length <= 3) {
            return localFilters.user_ids.map(id => {
                const user = options.users.find(u => u.id.toString() === id);
                return user ? user.name : 'Usuario no encontrado';
            }).join(', ');
        }
        return `${localFilters.user_ids.length} usuarios seleccionados`;
    };



    /**
     * Filtrar usuarios por término de búsqueda
     */
    const filteredUsers = options.users.filter(user => 
        user.name.toLowerCase().includes(userSearchTerm.toLowerCase()) ||
        user.email.toLowerCase().includes(userSearchTerm.toLowerCase())
    );

    /**
     * Verificar si hay filtros activos
     */
    const hasActiveFilters = () => {
        return (
            searchValue.trim() !== '' ||
            localFilters.event_types.length > 0 ||
            localFilters.user_ids.length > 0 ||
            localFilters.dateRange?.from ||
            localFilters.dateRange?.to
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Actividad" />
            
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-6 overflow-x-auto">
                {/* Tabla de actividades unificada */}
                <Card className="border border-muted/50 shadow-sm">
                    <CardHeader className="pb-6">
                        <div className="flex flex-col space-y-4">
                            <div className="flex items-start justify-between">
                                {/* Estadísticas compactas integradas */}
                                <div className="flex items-center gap-4 text-sm text-muted-foreground">
                                    <span className="flex items-center gap-1">
                                        <Users className="h-3 w-3 text-primary" />
                                        <span>actividades <span className="font-medium text-foreground">{activities.total}</span></span>
                                    </span>
                                    <span className="text-muted-foreground/50">•</span>
                                    <span className="flex items-center gap-1">
                                        <CalendarIcon className="h-3 w-3 text-blue-600" />
                                        <span>mostrando <span className="font-medium text-foreground">{activities.from}</span> a <span className="font-medium text-foreground">{activities.to}</span></span>
                                    </span>
                                </div>
                            </div>
                            
                            {/* Filtros integrados en el header */}
                            <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4 pt-2">
                                {/* Tipo de Evento con Dialog */}
                                <div className="space-y-2">
                                    <Label className="text-sm font-medium text-muted-foreground">Tipo de Evento</Label>
                                    <Dialog open={eventTypesOpen} onOpenChange={setEventTypesOpen}>
                                        <DialogTrigger asChild>
                                            <Button 
                                                variant="outline" 
                                                className="w-full justify-between h-9 text-left font-normal transition-all duration-200 focus:ring-2 focus:ring-primary/20 focus:border-primary"
                                            >
                                                <span className="truncate">{getEventTypesText()}</span>
                                                <Filter className="ml-2 h-4 w-4 text-muted-foreground" />
                                            </Button>
                                        </DialogTrigger>
                                        <DialogContent className="max-w-md">
                                            <DialogHeader>
                                                <DialogTitle>Seleccionar Tipos de Evento</DialogTitle>
                                                <DialogDescription>
                                                    Marca los tipos de evento que deseas filtrar
                                                </DialogDescription>
                                            </DialogHeader>
                                            <ScrollArea className="h-64">
                                                <div className="space-y-3 p-2">
                                                    {Object.entries(options.event_types).map(([key, label]) => (
                                                        <div key={key} className="flex items-center space-x-3">
                                                            <Checkbox
                                                                id={`event-${key}`}
                                                                checked={localFilters.event_types.includes(key)}
                                                                onCheckedChange={(checked) => 
                                                                    handleEventTypeChange(key, checked as boolean)
                                                                }
                                                            />
                                                            <Label 
                                                                htmlFor={`event-${key}`}
                                                                className="text-sm cursor-pointer"
                                                            >
                                                                {label}
                                                            </Label>
                                                        </div>
                                                    ))}
                                                </div>
                                            </ScrollArea>
                                        </DialogContent>
                                    </Dialog>
                                </div>

                                {/* Usuario con Dialog */}
                                <div className="space-y-2">
                                    <Label className="text-sm font-medium text-muted-foreground">Usuario</Label>
                                    <Dialog open={usersOpen} onOpenChange={setUsersOpen}>
                                        <DialogTrigger asChild>
                                            <Button 
                                                variant="outline" 
                                                className="w-full justify-between h-9 text-left font-normal transition-all duration-200 focus:ring-2 focus:ring-primary/20 focus:border-primary"
                                            >
                                                <span className="truncate">{getUsersText()}</span>
                                                <Users className="ml-2 h-4 w-4 text-muted-foreground" />
                                            </Button>
                                        </DialogTrigger>
                                        <DialogContent className="max-w-md">
                                            <DialogHeader>
                                                <DialogTitle>Seleccionar Usuarios</DialogTitle>
                                                <DialogDescription>
                                                    Marca los usuarios que deseas filtrar
                                                </DialogDescription>
                                            </DialogHeader>
                                            {/* Buscador de usuarios */}
                                            <div className="mb-4">
                                                <Input
                                                    placeholder="Buscar usuarios..."
                                                    value={userSearchTerm}
                                                    onChange={(e) => setUserSearchTerm(e.target.value)}
                                                    className="w-full"
                                                />
                                            </div>
                                            <ScrollArea className="h-64">
                                                <div className="space-y-3 p-2">
                                                    {filteredUsers.map((user) => (
                                                        <div key={user.id} className="flex items-center space-x-3">
                                                            <Checkbox
                                                                id={`user-${user.id}`}
                                                                checked={localFilters.user_ids.includes(user.id.toString())}
                                                                onCheckedChange={(checked) => 
                                                                    handleUserChange(user.id.toString(), checked as boolean)
                                                                }
                                                            />
                                                            <Label 
                                                                htmlFor={`user-${user.id}`}
                                                                className="text-sm cursor-pointer"
                                                            >
                                                                <div>
                                                                    <div className="font-medium">{user.name}</div>
                                                                    <div className="text-xs text-muted-foreground">{user.email}</div>
                                                                </div>
                                                            </Label>
                                                        </div>
                                                    ))}
                                                </div>
                                            </ScrollArea>
                                        </DialogContent>
                                    </Dialog>
                                </div>

                                {/* Rango de Fechas con inputs nativos mejorados */}
                                <div className="space-y-2">
                                    <Label className="text-sm font-medium text-muted-foreground">Rango de Fechas</Label>
                                    <Dialog open={dateRangeOpen} onOpenChange={setDateRangeOpen}>
                                        <DialogTrigger asChild>
                                            <Button 
                                                variant="outline" 
                                                className="w-full justify-between h-9 text-left font-normal transition-all duration-200 focus:ring-2 focus:ring-primary/20 focus:border-primary"
                                            >
                                                <span className="truncate">
                                                    {localFilters.dateRange?.from && localFilters.dateRange?.to 
                                                        ? `${format(localFilters.dateRange.from, "dd/MM/yyyy", { locale: es })} - ${format(localFilters.dateRange.to, "dd/MM/yyyy", { locale: es })}`
                                                        : "Seleccionar fechas..."
                                                    }
                                                </span>
                                                <CalendarIcon className="ml-2 h-4 w-4 text-muted-foreground" />
                                            </Button>
                                        </DialogTrigger>
                                        <DialogContent className="max-w-md">
                                            <DialogHeader>
                                                <DialogTitle>Seleccionar Rango de Fechas</DialogTitle>
                                                <DialogDescription>
                                                    Selecciona el período de fechas para filtrar los eventos
                                                </DialogDescription>
                                            </DialogHeader>
                                            <div className="space-y-4 p-4">
                                                {/* Fecha de inicio */}
                                                <div className="space-y-2">
                                                    <Label className="text-sm font-medium text-foreground">Fecha de inicio</Label>
                                                    <Input
                                                        type="date"
                                                        value={localFilters.dateRange?.from ? format(localFilters.dateRange.from, 'yyyy-MM-dd') : ''}
                                                        onChange={(e) => {
                                                            const date = e.target.value ? new Date(e.target.value) : undefined;
                                                            setLocalFilters(prev => ({
                                                                ...prev,
                                                                dateRange: {
                                                                    from: date,
                                                                    to: prev.dateRange?.to && date && date > prev.dateRange.to ? undefined : prev.dateRange?.to
                                                                }
                                                            }));
                                                        }}
                                                        max={localFilters.dateRange?.to ? format(localFilters.dateRange.to, 'yyyy-MM-dd') : format(new Date(), 'yyyy-MM-dd')}
                                                        className="h-9 text-sm transition-all duration-200 focus:ring-2 focus:ring-primary/20 focus:border-primary"
                                                    />
                                                </div>
                                                
                                                {/* Fecha de fin */}
                                                <div className="space-y-2">
                                                    <Label className="text-sm font-medium text-foreground">Fecha de fin</Label>
                                                    <Input
                                                        type="date"
                                                        value={localFilters.dateRange?.to ? format(localFilters.dateRange.to, 'yyyy-MM-dd') : ''}
                                                        onChange={(e) => {
                                                            const date = e.target.value ? new Date(e.target.value) : undefined;
                                                            setLocalFilters(prev => ({
                                                                ...prev,
                                                                dateRange: {
                                                                    from: prev.dateRange?.from,
                                                                    to: date
                                                                }
                                                            }));
                                                        }}
                                                        min={localFilters.dateRange?.from ? format(localFilters.dateRange.from, 'yyyy-MM-dd') : undefined}
                                                        max={format(new Date(), 'yyyy-MM-dd')}
                                                        className="h-9 text-sm transition-all duration-200 focus:ring-2 focus:ring-primary/20 focus:border-primary"
                                                    />
                                                </div>
                                            </div>
                                        </DialogContent>
                                    </Dialog>
                                </div>

                                {/* Por página */}
                                <div className="space-y-2">
                                    <Label className="text-sm font-medium text-muted-foreground">Registros por página</Label>
                                    <Select
                                        value={localFilters.per_page.toString()}
                                        onValueChange={(value) => {
                                            const newPerPage = parseInt(value);
                                            setLocalFilters(prev => ({ 
                                                ...prev, 
                                                per_page: newPerPage 
                                            }));
                                            
                                            // Aplicar filtros automáticamente cuando cambia la paginación
                                            const filterParams = {
                                                search: searchValue,
                                                event_type: localFilters.event_types.join(','),
                                                user_id: localFilters.user_ids.join(','),
                                                start_date: localFilters.dateRange?.from ? format(localFilters.dateRange.from, 'yyyy-MM-dd') : '',
                                                end_date: localFilters.dateRange?.to ? format(localFilters.dateRange.to, 'yyyy-MM-dd') : '',
                                                per_page: newPerPage,
                                            };
                                            
                                            router.get('/activity', filterParams, {
                                                preserveState: true,
                                                preserveScroll: true,
                                            });
                                        }}
                                    >
                                        <SelectTrigger className="w-full h-9 transition-all duration-200 focus:ring-2 focus:ring-primary/20 focus:border-primary">
                                            <SelectValue />
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

                            {/* Barra de búsqueda y botones en la misma fila */}
                            <div className="flex flex-col lg:flex-row gap-4 pt-4">
                                {/* Barra de búsqueda */}
                                <div className="flex-1 space-y-2 max-w-md">
                                    <Label className="text-sm font-medium text-muted-foreground">Buscar</Label>
                                    <div className="relative">
                                        <Input
                                            placeholder="Buscar por nombre, descripción, tipo de evento..."
                                            value={searchValue}
                                            onChange={(e) => setSearchValue(e.target.value)}
                                            className="w-full h-9 pl-9 text-base transition-all duration-200 focus:ring-2 focus:ring-primary/20 focus:border-primary"
                                        />
                                        <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                                </div>
                            </div>

                            {/* Botones de acción */}
                                <div className="flex flex-col sm:flex-row gap-2 lg:flex-shrink-0 lg:items-end">
                                <Button 
                                    onClick={applyFilters}
                                        className="px-6 py-2 font-medium transition-all duration-200 w-full sm:w-auto h-9"
                                    size="default"
                                >
                                    <Search className="mr-2 h-4 w-4" />
                                    Aplicar Filtros
                                </Button>
                                {hasActiveFilters() && (
                                    <Button 
                                        variant="outline" 
                                        onClick={clearFilters}
                                            className="px-6 py-2 font-medium transition-all duration-200 w-full sm:w-auto h-9"
                                        size="default"
                                    >
                                        <X className="mr-2 h-4 w-4" />
                                        Limpiar Filtros
                                    </Button>
                                )}
                                </div>
                            </div>
                        </div>
                    </CardHeader>

                    <CardContent>
                        {/* Vista de tabla para desktop, cards para mobile/tablet */}
                        {isLoading ? (
                            <ActivitySkeleton rows={localFilters.per_page} />
                        ) : activities.data && Array.isArray(activities.data) && activities.data.length > 0 ? (
                            <>
                                <div className="hidden lg:block">
                                    {/* Tabla minimalista para desktop */}
                                    <div className="overflow-x-auto">
                                        <table className="w-full">
                                            <thead>
                                                <tr className="border-b border-border">
                                                    <th className="text-left py-3 px-4 font-medium text-sm text-muted-foreground">
                                                        Usuario
                                                    </th>
                                                    <th className="text-left py-3 px-4 font-medium text-sm text-muted-foreground">
                                                        Actividad
                                                    </th>
                                                    <th className="text-left py-3 px-4 font-medium text-sm text-muted-foreground">
                                                        Descripción
                                                    </th>
                                                    <th className="text-left py-3 px-4 font-medium text-sm text-muted-foreground">
                                                        Fecha
                                                    </th>
                                                </tr>
                                            </thead>
                                            <tbody className="divide-y divide-border/50">
                                                {activities.data.map((activity: ActivityData) => (
                                                    <tr key={activity.id} className="hover:bg-muted/30 transition-colors">
                                                        {/* Columna Usuario */}
                                                        <td className="py-4 px-4">
                                                            <div className="flex items-center gap-3">
                                                                <div className="w-10 h-10 rounded-full bg-primary/10 flex items-center justify-center flex-shrink-0">
                                                                    <Users className="w-5 h-5 text-primary" />
                                                                </div>
                                                                <div className="min-w-0">
                                                                    <div className="font-medium text-sm text-foreground truncate">
                                                                        {activity.user.name}
                                                                    </div>
                                                                    <div className="text-sm text-muted-foreground truncate">
                                                                        {activity.user.email}
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </td>

                                                        {/* Columna Actividad */}
                                                        <td className="py-4 px-4">
                                                            <Badge className={`${getActivityTypeColor(activity.event_type)} px-3 py-1 text-xs font-medium`}>
                                                                {getActivityTypeText(activity.event_type)}
                                                            </Badge>
                                                        </td>

                                                        {/* Columna Descripción */}
                                                        <td className="py-4 px-4">
                                                            <div className="text-sm text-muted-foreground max-w-xs">
                                                                {getEnhancedDescription(activity)}
                                                            </div>
                                                        </td>

                                                        {/* Columna Fecha */}
                                                        <td className="py-4 px-4">
                                                            <div className="text-sm text-muted-foreground">
                                                                {formatDate(activity.created_at)}
                                                            </div>
                                                        </td>
                                                    </tr>
                                                ))}
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                {/* Vista de cards para mobile/tablet */}
                                <div className="lg:hidden">
                                    <div className="grid gap-3 md:gap-4">
                                        {activities.data.map((activity: ActivityData) => (
                                    <div key={activity.id} className="bg-card border border-border rounded-lg p-4 space-y-3 hover:bg-muted/50 transition-colors">
                                        {/* Header con usuario y tipo de actividad */}
                                        <div className="flex items-center justify-between">
                                            <div className="flex items-center space-x-3 flex-1 min-w-0">
                                                <div className="w-8 h-8 rounded-full bg-primary/10 flex items-center justify-center flex-shrink-0">
                                                    <Users className="w-4 h-4 text-primary" />
                                                </div>
                                                <div className="min-w-0 flex-1">
                                                    <div className="font-medium text-sm break-words">{activity.user.name}</div>
                                                    <div className="text-sm text-muted-foreground break-words">
                                                        {activity.user.email}
                                                    </div>
                                                </div>
                                            </div>
                                            <Badge className={`${getActivityTypeColor(activity.event_type)} px-3 py-1 text-xs font-medium flex-shrink-0 ml-2`}>
                                                {getActivityTypeText(activity.event_type)}
                                            </Badge>
                                        </div>
                                        
                                        {/* Descripción mejorada con colores */}
                                        <div className="text-sm text-muted-foreground break-words leading-relaxed line-clamp-3">
                                            {getEnhancedDescription(activity)}
                                        </div>
                                        
                                        {/* Fecha */}
                                        <div className="flex items-center justify-between text-sm text-muted-foreground pt-2 border-t border-border">
                                            <span>Fecha: {formatDate(activity.created_at)}</span>
                                        </div>
                                    </div>
                                        ))}
                                    </div>
                                </div>
                            </>
                            ) : (
                                <div className="text-center text-muted-foreground py-8">
                                    <div className="flex flex-col items-center space-y-2">
                                        <Inbox className="h-12 w-12 text-muted-foreground/30" />
                                        <span className="text-base">No hay actividades para mostrar</span>
                                    </div>
                                </div>
                            )}

                        {/* Paginación */}
                        {activities.last_page > 1 && (
                            <div className="mt-8">
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
                                                        start_date: filters.start_date || '',
                                                        end_date: filters.end_date || '',
                                                        per_page: filters.per_page || 10,
                                                    };

                                                    router.get('/activity', pageParams, {
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
                                                            router.get('/activity', {
                                                                page: 1,
                                                                search: filters.search,
                                                                event_type: filters.event_type,
                                                                user_id: filters.user_id,
                                                                start_date: filters.start_date,
                                                                end_date: filters.end_date,
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
                                                                start_date: filters.start_date || '',
                                                                end_date: filters.end_date || '',
                                                                per_page: filters.per_page || 10,
                                                            };

                                                            router.get('/activity', pageParams, {
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
                                                            router.get('/activity', {
                                                                page: activities.last_page,
                                                                search: filters.search,
                                                                event_type: filters.event_type,
                                                                user_id: filters.user_id,
                                                                start_date: filters.start_date,
                                                                end_date: filters.end_date,
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
                                                    router.get('/activity', {
                                                        page: activities.current_page + 1,
                                                        search: filters.search,
                                                        event_type: filters.event_type,
                                                        user_id: filters.user_id,
                                                        start_date: filters.start_date,
                                                        end_date: filters.end_date,
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
                                
                                <div className="text-center text-sm text-muted-foreground mt-6">
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