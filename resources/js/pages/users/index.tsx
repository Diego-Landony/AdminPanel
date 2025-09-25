import { type BreadcrumbItem } from '@/types';
import { Head, router, Link } from '@inertiajs/react';
import { useState, useEffect, useCallback } from 'react';
import { toast } from 'sonner';


import AppLayout from '@/layouts/app-layout';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';


import { Shield, Plus, Search, Users, Clock, Circle, X, RefreshCw, Trash2, ArrowUp, ArrowDown, ArrowUpDown } from 'lucide-react';
import { UsersSkeleton } from '@/components/skeletons';
import { ActionsMenu } from '@/components/ActionsMenu';
import { PaginationWrapper } from '@/components/PaginationWrapper';

/**
 * Breadcrumbs para la navegación de usuarios
 */
const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Usuarios',
        href: '/users',
    },
    {
        title: 'Gestión de usuarios',
        href: '/users',
    },
];

/**
 * Interfaz para los roles
 */
interface Role {
    id: number;
    name: string;
    is_system: boolean;
}

/**
 * Interfaz para los datos del usuario
 */
interface User {
    id: number;
    name: string;
    email: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
    last_activity: string | null;
    is_online: boolean;
    status: string;
    roles: Role[];
}

/**
 * Interfaz para las props de la página
 */
interface UsersPageProps {
    users: {
        data: User[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        from: number;
        to: number;
    };
    total_users: number;
    verified_users: number;
    online_users: number;
    filters: {
        search: string | null;
        per_page: number;
        sort_field?: string;
        sort_direction?: 'asc' | 'desc';
    };
}

/**
 * Obtiene el color del badge según el estado del usuario
 */
const getStatusColor = (status: string): string => {
    switch (status) {
        case 'online':
            return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300 border border-green-200 dark:border-green-700';
        case 'recent':
            return 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300 border border-blue-200 dark:border-blue-700';
        case 'offline':
            return 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300 border border-gray-200 dark:border-gray-600';
        case 'never':
            return 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300 border border-red-200 dark:border-red-700';
        default:
            return 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300 border border-gray-200 dark:border-gray-600';
    }
};

/**
 * Obtiene el texto del estado del usuario
 */
const getStatusText = (status: string): string => {
    switch (status) {
        case 'online':
            return 'En línea';
        case 'recent':
            return 'Reciente';
        case 'offline':
            return 'Desconectado';
        case 'never':
            return 'Nunca';
        default:
            return 'Nunca';
    }
};

/**
 * Obtiene el icono del estado del usuario
 */
const getStatusIcon = (status: string): React.ReactElement => {
    switch (status) {
        case 'online':
            return <Circle className="h-2 w-2 text-green-600" />;
        case 'recent':
            return <Circle className="h-2 w-2 text-blue-600" />;
        case 'offline':
            return <Circle className="h-2 w-2 text-gray-400" />;
        case 'never':
            return <Circle className="h-2 w-2 text-red-600" />;
        default:
            return <Circle className="h-2 w-2 text-gray-400" />;
    }
};

/**
 * Formatea la fecha de manera legible en hora de Guatemala
 */
const formatDate = (dateString: string | null): string => {
    if (!dateString) return 'Nunca';
    
    try {
        const date = new Date(dateString);
        
        // Usar la API nativa para zona horaria de Guatemala
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
 * Página de gestión de usuarios
 * Muestra una lista de todos los usuarios del sistema con su información de actividad
 * Usa paginación de Inertia sin estado local para evitar conflictos
 * Mantiene la sesión del usuario activa cada 30 segundos
 */
export default function UsersIndex({ users, total_users, online_users, filters }: UsersPageProps) {

    
    // Usar directamente los props de Inertia para datos principales (igual que en actividad)
    // Solo mantener estado local para UI y filtros temporales
    
    // Estado para filtros y búsqueda
    const [searchValue, setSearchValue] = useState(filters.search || '');
    const [perPage, setPerPage] = useState(filters.per_page || 10);
    const [isSearching, setIsSearching] = useState(false);
    
    // Estado para eliminación
    const [deletingUser, setDeletingUser] = useState<number | null>(null);
    
    // Estado para sincronización automática
    const [lastSync, setLastSync] = useState(new Date());
    const [isSyncing, setIsSyncing] = useState(false);
    
    // Estado para el loading
    const [isLoading, setIsLoading] = useState(false);
    
    // Estado para ordenamiento
    const [sortField, setSortField] = useState<string | null>(filters.sort_field || null);
    const [sortDirection, setSortDirection] = useState<'asc' | 'desc'>(filters.sort_direction || 'asc');

    // Sincronizar estado de ordenamiento con los filtros del backend
    useEffect(() => {
        setSortField(filters.sort_field || null);
        setSortDirection(filters.sort_direction || 'asc');
    }, [filters.sort_field, filters.sort_direction]);

    // Función para manejar ordenamiento
    const handleSort = (field: string) => {
        const newDirection = sortField === field && sortDirection === 'asc' ? 'desc' : 'asc';
        setSortField(field);
        setSortDirection(newDirection);

        // Obtener parámetros actuales y agregar ordenamiento
        const searchParams = new URLSearchParams(window.location.search);
        const currentParams = Object.fromEntries(searchParams.entries());
        
        router.get(route('users.index'), {
            ...currentParams,
            sort_field: field,
            sort_direction: newDirection,
            page: 1 // Resetear a página 1 cuando se ordena
        }, {
            preserveState: true,
            preserveScroll: true,
            replace: true
        });
    };

    // Función para obtener el icono de ordenamiento
    const getSortIcon = (field: string) => {
        if (sortField !== field) {
            return <ArrowUpDown className="h-3 w-3 text-gray-400 hover:text-gray-600" />;
        }
        return sortDirection === 'asc' 
            ? <ArrowUp className="h-3 w-3 text-primary" />
            : <ArrowDown className="h-3 w-3 text-primary" />;
    };

    // Función para ejecutar búsqueda manualmente
    const handleSearch = () => {
        setIsSearching(true);
        setIsLoading(true);
        router.get(route('users.index'), 
            { 
                search: searchValue,
                per_page: perPage 
            }, 
            { 
                preserveState: true,
                preserveScroll: true,
                replace: true,
                onSuccess: () => {
                    setIsSearching(false);
                    setIsLoading(false);
                },
                onError: () => {
                    setIsSearching(false);
                    setIsLoading(false);
                }
            }
        );
    };

    // Función para limpiar la búsqueda
    const handleClear = () => {
        setSearchValue('');
        setIsSearching(true);
        setIsLoading(true);
        router.get(route('users.index'), 
            { 
                per_page: perPage 
            }, 
            { 
                preserveState: true,
                preserveScroll: true,
                replace: true,
                onSuccess: () => {
                    setIsSearching(false);
                    setIsLoading(false);
                },
                onError: () => {
                    setIsSearching(false);
                    setIsLoading(false);
                }
            }
        );
    };

    // Función para manejar Enter en el campo de búsqueda
    const handleKeyPress = (e: React.KeyboardEvent) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            handleSearch();
        }
    };

    // Auto-actualizar perPage cuando cambie
    useEffect(() => {
        if (perPage !== filters.per_page) {
            router.get(route('users.index'), 
                { 
                    search: filters.search,
                    per_page: perPage 
                }, 
                { 
                    preserveState: true,
                    preserveScroll: true,
                    replace: true 
                }
            );
        }
    }, [perPage, filters.per_page, filters.search]);

    /**
     * Función para eliminar usuario
     */
    const handleDeleteUser = (user: User) => {
        if (deletingUser) return;
        
        setDeletingUser(user.id);
        
        router.delete(route('users.destroy', user.id), {
            onSuccess: () => {
                setDeletingUser(null);
            },
            onError: (errors) => {
                setDeletingUser(null);
                // Los errores de validación se muestran automáticamente
                // Los errores del servidor se manejan por el layout
                if (Object.keys(errors).length === 0) {
                    toast.error('Error del servidor al eliminar el usuario. Inténtalo de nuevo.');
                }
            }
        });
    };

    /**
     * Función para actualizar los datos de usuarios (sincronización manual)
     * Preserva los filtros de búsqueda actuales y la página actual
     */
    const refreshUserData = useCallback(() => {
        setIsSyncing(true);
        // Obtener parámetros actuales de la URL para preservar estado
        const searchParams = new URLSearchParams(window.location.search);
        const currentParams = Object.fromEntries(searchParams.entries());
        
        router.get(route('users.index'), currentParams, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            onSuccess: () => {
                setLastSync(new Date());
                setIsSyncing(false);
            },
            onError: () => {
                setIsSyncing(false);
            }
        });
    }, []);

    /**
     * Sincronización automática deshabilitada para evitar conflictos con paginación
     * Los datos se actualizan solo cuando el usuario hace clic en el botón de sincronización
     */
    // useEffect(() => {
    //     const interval = setInterval(() => {
    //         refreshUserData();
    //     }, 60000); // 1 minuto
    //
    //     // Limpiar el intervalo cuando el componente se desmonte
    //     return () => clearInterval(interval);
    // }, [refreshUserData]); // Solo ejecutar una vez al montar el componente

    /**
     * Efecto para mantener la sesión del usuario activa cada 30 segundos
     * Esto actualiza el last_login_at en la base de datos
     */
    useEffect(() => {
        const sessionInterval = setInterval(() => {
            // Enviar una petición silenciosa para mantener la sesión activa
            // No esperamos respuesta para evitar conflictos con Inertia
            fetch('/users/keep-alive', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                    'Accept': 'application/json',
                },
                credentials: 'same-origin'
            }).catch(() => {
                // Ignorar errores para no interrumpir la funcionalidad
            });
        }, 30000); // 30 segundos

        // Limpiar el intervalo cuando el componente se desmonte
        return () => clearInterval(sessionInterval);
    }, []);





    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Gestión de Usuarios" />
            
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-6">
                {/* Encabezado */}
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-3xl font-bold tracking-tight">Gestión de Usuarios</h1>
                        <p className="text-muted-foreground">
                            Administra los usuarios del sistema.
                        </p>
                    </div>
                    <Link href={route('users.create')}>
                        <Button>
                            <Plus className="mr-2 h-4 w-4" />
                            Crear Usuario
                        </Button>
                    </Link>
                </div>



                {/* Tabla de usuarios */}
                <Card className="border border-muted/50 shadow-sm">
                    <CardHeader className="pb-6">
                        <div className="flex flex-col space-y-4">
                            <div className="flex items-start justify-between">
                                {/* Estadísticas compactas integradas */}
                                <div className="flex items-center gap-4 text-sm text-muted-foreground">
                                    <span className="flex items-center gap-1">
                                        <Users className="h-3 w-3 text-primary" />
                                        <span>usuarios <span className="font-medium text-foreground">{total_users}</span></span>
                                    </span>
                                    <span className="text-muted-foreground/50">•</span>
                                    <span className="flex items-center gap-1">
                                        <Clock className="h-3 w-3 text-green-600" />
                                        <span>en línea <span className="font-medium text-foreground">{online_users}</span></span>
                                    </span>
                                    <span className="text-muted-foreground/50">•</span>
                                    <span className="flex items-center gap-1">
                                        <Users className="h-3 w-3 text-red-600" />
                                        <span>desconectados <span className="font-medium text-foreground">{total_users - online_users}</span></span>
                                    </span>
                                </div>
                                
                                {/* Indicador de sincronización */}
                                <div className="flex items-center gap-2 text-sm text-muted-foreground">
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        onClick={refreshUserData}
                                        disabled={isSyncing}
                                        className="h-8 px-2"
                                        title="Sincronizar datos"
                                    >
                                        {isSyncing ? (
                                            <div className="h-4 w-4 animate-spin rounded-full border-2 border-current border-t-transparent" />
                                        ) : (
                                            <RefreshCw className="h-4 w-4" />
                                        )}
                                    </Button>
                                    <span className="text-xs">
                                        Última sincronización: {lastSync.toLocaleTimeString('es-ES', { 
                                            hour: '2-digit', 
                                            minute: '2-digit',
                                            second: '2-digit'
                                        })}
                                    </span>
                                </div>
                            </div>
                            
                            {/* Filtros integrados en el header */}
                            <div className="flex items-center gap-4 pt-2">
                                <div className="flex items-center gap-2 flex-1 max-w-lg">
                                    <div className="relative flex-1">
                                        <Input
                                            placeholder="Buscar usuarios..."
                                            value={searchValue}
                                            onChange={(e) => setSearchValue(e.target.value)}
                                            onKeyPress={handleKeyPress}
                                            className="h-9 pl-9 transition-all duration-200 focus:ring-2 focus:ring-primary/20 focus:border-primary"
                                            disabled={isSearching}
                                        />
                                        <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                                    </div>
                                    <Button
                                        onClick={handleSearch}
                                        disabled={isSearching}
                                        size="sm"
                                        className="h-9 px-3"
                                    >
                                        {isSearching ? (
                                            <div className="h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent" />
                                        ) : (
                                            <>
                                                <Search className="h-4 w-4 mr-1" />
                                                Buscar
                                            </>
                                        )}
                                    </Button>
                                    {(searchValue || filters.search) && (
                                        <Button
                                            onClick={handleClear}
                                            disabled={isSearching}
                                            variant="outline"
                                            size="sm"
                                            className="h-9 px-3"
                                        >
                                            <X className="h-4 w-4 mr-1" />
                                            Limpiar
                                        </Button>
                                    )}
                                </div>
                                <div className="flex items-center gap-2">
                                    <Label className="text-sm text-muted-foreground whitespace-nowrap">
                                        Mostrar
                                    </Label>
                                    <Select
                                        value={perPage.toString()}
                                        onValueChange={(value) => {
                                            const newPerPage = parseInt(value);
                                            setPerPage(newPerPage);
                                            
                                            // La búsqueda se maneja automáticamente con el debounce
                                            // al cambiar perPage
                                        }}
                                    >
                                        <SelectTrigger className="h-9 w-20">
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="10">10</SelectItem>
                                            <SelectItem value="25">25</SelectItem>
                                            <SelectItem value="50">50</SelectItem>
                                            <SelectItem value="100">100</SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <span className="text-sm text-muted-foreground">por página</span>
                                </div>
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent>
                        {/* Skeleton mientras carga */}
                        {isLoading ? (
                            <UsersSkeleton rows={perPage} />
                        ) : (
                            <>
                                {/* Mensaje cuando no hay resultados */}
                                {searchValue && users.data.length === 0 && (
                                    <div className="text-center py-12">
                                        <Users className="h-12 w-12 text-muted-foreground mx-auto mb-4" />
                                        <h3 className="text-lg font-medium text-muted-foreground mb-2">
                                            No se encontraron coincidencias
                                        </h3>
                                        <p className="text-sm text-muted-foreground mb-4">
                                            No hay usuarios que coincidan con "{searchValue}"
                                        </p>
                                        <Button 
                                            variant="outline" 
                                            onClick={() => {
                                                setSearchValue('');
                                                // La búsqueda se maneja automáticamente con el debounce
                                            }}
                                        >
                                            Limpiar búsqueda
                                        </Button>
                                    </div>
                                )}

                                {/* Vista de tabla para desktop, cards para mobile/tablet */}
                                {users.data.length > 0 && (
                            <>
                                <div className="hidden lg:block">
                                    {/* Tabla minimalista para desktop */}
                                    <div className="overflow-x-auto">
                                        <table className="w-full">
                                            <thead>
                                                <tr className="border-b border-border">
                                                    <th className="text-left py-3 px-4 font-medium text-sm text-muted-foreground">
                                                        <button
                                                            onClick={() => handleSort('name')}
                                                            className="flex items-center gap-2 hover:text-foreground transition-colors"
                                                        >
                                                            Usuario
                                                            {getSortIcon('name')}
                                                        </button>
                                                    </th>
                                                    <th className="text-left py-3 px-4 font-medium text-sm text-muted-foreground">
                                                        Roles
                                                    </th>
                                                    <th className="text-left py-3 px-4 font-medium text-sm text-muted-foreground">
                                                        Información
                                                    </th>
                                                    <th className="text-left py-3 px-4 font-medium text-sm text-muted-foreground">
                                                        <button
                                                            onClick={() => handleSort('status')}
                                                            className="flex items-center gap-2 hover:text-foreground transition-colors"
                                                        >
                                                            Estado
                                                            {getSortIcon('status')}
                                                        </button>
                                                    </th>
                                                    <th className="text-right py-3 px-4 font-medium text-sm text-muted-foreground">
                                                        Acciones
                                                    </th>
                                                </tr>
                                            </thead>
                                            <tbody className="divide-y divide-border/50">
                                                {users.data.map((user) => (
                                                    <tr key={user.id} className="hover:bg-muted/30 transition-colors">
                                                        {/* Columna Usuario */}
                                                        <td className="py-4 px-4">
                                                            <div className="flex items-center gap-3">
                                                                <div className="w-10 h-10 rounded-full bg-primary/10 flex items-center justify-center flex-shrink-0">
                                                                    <Users className="w-5 h-5 text-primary" />
                                                                </div>
                                                                <div className="min-w-0">
                                                                    <div className="font-medium text-sm text-foreground truncate">
                                                                        {user.name}
                                                                    </div>
                                                                    <div className="text-sm text-muted-foreground truncate">
                                                                        {user.email}
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </td>

                                                        {/* Columna Roles */}
                                                        <td className="py-4 px-4">
                                                            <div className="flex flex-wrap gap-1">
                                                                {user.roles.length > 0 ? (
                                                                    user.roles.slice(0, 2).map((role) => (
                                                                        <Badge 
                                                                            key={role.id} 
                                                                            variant={role.is_system ? "secondary" : "default"}
                                                                            className="text-xs px-2 py-1"
                                                                        >
                                                                            <Shield className="w-3 h-3 mr-1" />
                                                                            {role.name}
                                                                        </Badge>
                                                                    ))
                                                                ) : (
                                                                    <span className="text-sm text-muted-foreground italic">Sin roles</span>
                                                                )}
                                                                {user.roles.length > 2 && (
                                                                    <Badge variant="outline" className="text-xs px-2 py-1">
                                                                        +{user.roles.length - 2}
                                                                    </Badge>
                                                                )}
                                                            </div>
                                                        </td>

                                                        {/* Columna Información */}
                                                        <td className="py-4 px-4">
                                                            <div className="space-y-1 text-sm text-muted-foreground">
                                                                <div className="flex items-center gap-2">
                                                                    <Clock className="w-3 h-3" />
                                                                    <span>Última actividad: {formatDate(user.last_activity)}</span>
                                                                </div>
                                                                <div className="flex items-center gap-2">
                                                                    <Users className="w-3 h-3" />
                                                                    <span>Creado: {formatDate(user.created_at)}</span>
                                                                </div>
                                                            </div>
                                                        </td>

                                                        {/* Columna Estado */}
                                                        <td className="py-4 px-4">
                                                            <Badge className={`${getStatusColor(user.status)} px-3 py-1 text-xs font-medium`}>
                                                                <span className="mr-2">{getStatusIcon(user.status)}</span>
                                                                {getStatusText(user.status)}
                                                            </Badge>
                                                        </td>

                                                        {/* Columna Acciones */}
                                                        <td className="py-4 px-4">
                                                            <div className="flex items-center justify-end">
                                                                <ActionsMenu
                                                                    editHref={route('users.edit', user.id)}
                                                                    onDelete={() => setDeletingUser(user.id)}
                                                                    isDeleting={deletingUser === user.id}
                                                                    editTitle="Editar usuario"
                                                                    deleteTitle="Eliminar usuario"
                                                                />
                                                                <Dialog open={deletingUser === user.id} onOpenChange={(open) => !open && setDeletingUser(null)}>
                                                                    <DialogTrigger asChild>
                                                                        <div style={{ display: 'none' }} />
                                                                    </DialogTrigger>
                                                                    <DialogContent>
                                                                        <DialogHeader>
                                                                            <DialogTitle>Confirmar eliminación</DialogTitle>
                                                                            <DialogDescription>
                                                                                ¿Estás seguro de que quieres eliminar al usuario "{user.name}"? 
                                                                                Esta acción no se puede deshacer.
                                                                            </DialogDescription>
                                                                        </DialogHeader>
                                                                        <DialogFooter>
                                                                            <Button 
                                                                                variant="destructive" 
                                                                                onClick={() => handleDeleteUser(user)}
                                                                                disabled={deletingUser === user.id}
                                                                                className="w-full sm:w-auto"
                                                                            >
                                                                                {deletingUser === user.id ? (
                                                                                    <>
                                                                                        <div className="h-4 w-4 animate-spin rounded-full border-2 border-current border-t-transparent mr-1" />
                                                                                        Eliminando...
                                                                                    </>
                                                                                ) : (
                                                                                    <>
                                                                                        <Trash2 className="w-4 h-4 mr-1" />
                                                                                        Eliminar
                                                                                    </>
                                                                                )}
                                                                            </Button>
                                                                        </DialogFooter>
                                                                    </DialogContent>
                                                                </Dialog>
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
                                        {users.data.map((user) => (
                                            <div key={user.id} className="bg-card border border-border rounded-lg p-4 space-y-3 hover:bg-muted/50 transition-colors">
                                                    {/* Header compacto */}
                                                <div className="flex items-center justify-between">
                                                    <div className="flex items-center space-x-3 flex-1 min-w-0">
                                                        <div className="w-8 h-8 rounded-full bg-primary/10 flex items-center justify-center flex-shrink-0">
                                                            <Users className="w-4 h-4 text-primary" />
                                                        </div>
                                                        <div className="min-w-0 flex-1">
                                                            <div className="font-medium text-sm break-words">{user.name}</div>
                                                            <div className="text-sm text-muted-foreground break-words">
                                                                {user.email}
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <Badge className={`${getStatusColor(user.status)} px-3 py-1 text-xs font-medium flex-shrink-0 ml-2`}>
                                                            <span className="mr-2">{getStatusIcon(user.status)}</span>
                                                        {getStatusText(user.status)}
                                                    </Badge>
                                                </div>
                                                
                                                    {/* Roles compactos */}
                                                <div className="flex flex-wrap gap-1">
                                                    {user.roles.length > 0 ? (
                                                        user.roles.slice(0, 3).map((role) => (
                                                            <Badge 
                                                                key={role.id} 
                                                                variant={role.is_system ? "secondary" : "default"}
                                                                className="text-xs px-2 py-0.5"
                                                            >
                                                                <Shield className="w-3 h-3 mr-1" />
                                                                {role.name}
                                                            </Badge>
                                                        ))
                                                    ) : (
                                                        <span className="text-sm text-muted-foreground">Sin roles</span>
                                                    )}
                                                    {user.roles.length > 3 && (
                                                        <span className="text-sm text-muted-foreground">+{user.roles.length - 3}</span>
                                                    )}
                                                </div>
                                                
                                                {/* Fechas */}
                                                <div className="flex items-center justify-between text-sm text-muted-foreground pt-2 border-t border-border">
                                                        <span>Última actividad: {formatDate(user.last_activity)}</span>
                                                    <span>Creado: {formatDate(user.created_at)}</span>
                                                </div>
                                                
                                                {/* Acciones */}
                                                <div className="flex items-center justify-end pt-2 border-t border-border">
                                                    <ActionsMenu
                                                        editHref={route('users.edit', user.id)}
                                                        onDelete={() => setDeletingUser(user.id)}
                                                        isDeleting={deletingUser === user.id}
                                                        editTitle="Editar usuario"
                                                        deleteTitle="Eliminar usuario"
                                                    />
                                                    <Dialog open={deletingUser === user.id} onOpenChange={(open) => !open && setDeletingUser(null)}>
                                                        <DialogTrigger asChild>
                                                            <div style={{ display: 'none' }} />
                                                        </DialogTrigger>
                                                        <DialogContent>
                                                            <DialogHeader>
                                                                    <DialogTitle>Confirmar eliminación</DialogTitle>
                                                                <DialogDescription>
                                                                        ¿Estás seguro de que quieres eliminar al usuario "{user.name}"? 
                                                                    Esta acción no se puede deshacer.
                                                                </DialogDescription>
                                                            </DialogHeader>
                                                            <DialogFooter>
                                                                <Button 
                                                                    variant="destructive"
                                                                    onClick={() => handleDeleteUser(user)}
                                                                    disabled={deletingUser === user.id}
                                                                    className="w-full sm:w-auto"
                                                                >
                                                                    {deletingUser === user.id ? (
                                                                        <>
                                                                            <div className="h-4 w-4 animate-spin rounded-full border-2 border-current border-t-transparent mr-1" />
                                                                            Eliminando...
                                                                        </>
                                                                    ) : (
                                                                        <>
                                                                            <Trash2 className="w-4 h-4 mr-1" />
                                                                            Eliminar
                                                                        </>
                                                                    )}
                                                                </Button>
                                                            </DialogFooter>
                                                        </DialogContent>
                                                    </Dialog>
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                </div>
                                
                                <PaginationWrapper
                                    data={users}
                                    routeName={route('users.index')}
                                    filters={{
                                        search: searchValue,
                                        per_page: perPage,
                                        sort_field: sortField,
                                        sort_direction: sortDirection
                                    }}
                                />
                            </>
                        )}
                            </>
                        )}
                    </CardContent>
                </Card>


            </div>
        </AppLayout>
    );
}


