import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { route } from 'ziggy-js';
import { useState, useEffect } from 'react';
import { toast } from 'sonner';

import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { ScrollArea } from '@/components/ui/scroll-area';
import { Shield, Plus, Search, Users, UserCheck, X, ArrowUp, ArrowDown, ArrowUpDown } from 'lucide-react';
import { RolesSkeleton } from '@/components/skeletons';
import { ActionsMenu } from '@/components/ActionsMenu';
import { PaginationWrapper } from '@/components/PaginationWrapper';


/**
 * Breadcrumbs para la navegación de roles
 */
const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Roles',
        href: '/roles',
    },
    {
        title: 'Roles del Sistema',
        href: '/roles',
    },
];

interface Permission {
    id: number;
    name: string;
    guard_name: string;
}

interface Role {
    id: number;
    name: string;
    description: string | null;
    is_system: boolean;
    guard_name: string;
    created_at: string;
    updated_at: string;
    permissions: Permission[];
    users_count: number;
    users: User[];
}

interface User {
    id: number;
    name: string;
    email: string;
    roles: Role[];
}

interface RolesIndexProps {
    roles: {
        data: Role[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        from: number;
        to: number;
    };
    filters: {
        search: string;
        per_page: number;
        sort_field?: string;
        sort_direction?: 'asc' | 'desc';
    };
    roleStats?: {
        total: number;
        system: number;
        created: number;
    };
}

export default function RolesIndex({ roles, filters, roleStats }: RolesIndexProps) {
    const [searchValue, setSearchValue] = useState(filters.search || '');
    const [perPage, setPerPage] = useState(filters.per_page.toString());
    const [deletingRole, setDeletingRole] = useState<number | null>(null);
    const [selectedRole, setSelectedRole] = useState<Role | null>(null);
    const [showDeleteDialog, setShowDeleteDialog] = useState(false);
    const [showUsersModal, setShowUsersModal] = useState(false);
    const [usersInRole, setUsersInRole] = useState<User[]>([]);
    
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
        
        router.get(route('roles.index'), {
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
        setIsLoading(true);
        router.get(route('roles.index'), 
            { 
                search: searchValue,
                per_page: perPage 
            }, 
            { 
                preserveState: true,
                preserveScroll: true,
                replace: true,
                onSuccess: () => {
                    setIsLoading(false);
                },
                onError: () => {
                    setIsLoading(false);
                }
            }
        );
    };

    // Función para limpiar la búsqueda
    const handleClear = () => {
        setSearchValue('');
        setIsLoading(true);
        router.get(route('roles.index'), 
            { 
                per_page: perPage 
            }, 
            { 
                preserveState: true,
                preserveScroll: true,
                replace: true,
                onSuccess: () => {
                    setIsLoading(false);
                },
                onError: () => {
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

    // Auto-actualizar perPage eliminado para evitar conflictos con paginación
    // El cambio de perPage se maneja directamente en el onChange del Select

    const openDeleteDialog = (role: Role) => {
        setSelectedRole(role);
        setShowDeleteDialog(true);
    };

    const closeDeleteDialog = () => {
        setSelectedRole(null);
        setShowDeleteDialog(false);
        setDeletingRole(null);
    };

    const handleDeleteRole = async () => {
        if (!selectedRole) return;

        setDeletingRole(selectedRole.id);
        try {
            router.delete(route('roles.destroy', selectedRole.id), {
                onSuccess: () => {
                    closeDeleteDialog();
                },
                onError: () => {
                    setDeletingRole(null);
                }
            });
        } catch (error) {
            setDeletingRole(null);
            console.error('Error al eliminar rol:', error);
        }
    };

    const openUsersModal = async (role: Role) => {
        setSelectedRole(role);
        try {
            // Los usuarios ya vienen del backend en role.users
            if (role.users && Array.isArray(role.users) && role.users.length > 0) {
                setUsersInRole(role.users);
                setShowUsersModal(true);
            } else {
                setUsersInRole([]);
                setShowUsersModal(true);
            }
        } catch (error) {
            console.error('Error al cargar usuarios:', error);
            toast.error('Error al cargar usuarios del rol');
        }
    };

    const closeUsersModal = () => {
        setSelectedRole(null);
        setShowUsersModal(false);
        setUsersInRole([]);
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Roles del Sistema" />
            
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-6">
                {/* Encabezado */}
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-3xl font-bold tracking-tight">Roles del Sistema</h1>
                        <p className="text-muted-foreground">
                            Gestiona los roles y permisos de los usuarios
                        </p>
                    </div>
                    <Link href={route('roles.create')}>
                        <Button>
                            <Plus className="mr-2 h-4 w-4" />
                            Crear Rol
                        </Button>
                    </Link>
                </div>

                {/* Tabla de roles */}
                <Card className="border border-muted/50 shadow-sm">
                    <CardHeader className="pb-6">
                        <div className="flex flex-col space-y-4">
                            <div className="flex items-start justify-between">
                                {/* Estadísticas compactas integradas */}
                                <div className="flex items-center gap-4 text-sm text-muted-foreground">
                                    <span className="flex items-center gap-1">
                                        <Shield className="h-3 w-3 text-primary" />
                                        <span>roles <span className="font-medium text-foreground">{roleStats?.total || roles.total || 0}</span></span>
                                    </span>
                                    <span className="text-muted-foreground/50">•</span>
                                    <span className="flex items-center gap-1">
                                        <UserCheck className="h-3 w-3 text-blue-600" />
                                        <span>del sistema <span className="font-medium text-foreground">{roleStats?.system || roles.data.filter(role => role.is_system).length || 0}</span></span>
                                    </span>
                                    <span className="text-muted-foreground/50">•</span>
                                    <span className="flex items-center gap-1">
                                        <Users className="h-3 w-3 text-green-600" />
                                        <span>creados <span className="font-medium text-foreground">{roleStats?.created || roles.data.filter(role => !role.is_system).length || 0}</span></span>
                                    </span>
                                </div>
                            </div>

                            {/* Filtros integrados en el header */}
                            <div className="flex items-center gap-4 pt-2">
                                <div className="flex items-center gap-2 flex-1 max-w-lg">
                                    <div className="relative flex-1">
                                        <Input
                                            placeholder="Buscar roles..."
                                            value={searchValue}
                                            onChange={(e) => setSearchValue(e.target.value)}
                                            onKeyPress={handleKeyPress}
                                            className="h-9 pl-9 transition-all duration-200 focus:ring-2 focus:ring-primary/20 focus:border-primary"
                                        />
                                        <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                                    </div>
                                    <Button
                                        onClick={handleSearch}
                                        size="sm"
                                        className="h-9 px-3"
                                    >
                                        <Search className="h-4 w-4 mr-1" />
                                        Buscar
                                    </Button>
                                    {(searchValue || filters.search) && (
                                        <Button
                                            onClick={handleClear}
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
                                    <Select value={perPage} onValueChange={(value) => {
                                        setPerPage(value);
                                        router.get(route('roles.index'), 
                                            { 
                                                search: filters.search,
                                                per_page: value 
                                            }, 
                                            { 
                                                preserveState: true,
                                                preserveScroll: true,
                                                replace: true 
                                            }
                                        );
                                    }}>
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
                        {/* Vista de tabla para desktop, cards para mobile/tablet */}
                        {isLoading ? (
                            <RolesSkeleton rows={10} />
                        ) : roles.data.length > 0 ? (
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
                                                            Rol
                                                            {getSortIcon('name')}
                                                        </button>
                                                    </th>
                                                    <th className="text-left py-3 px-4 font-medium text-sm text-muted-foreground">
                                                        Descripción
                                                    </th>
                                                    <th className="text-left py-3 px-4 font-medium text-sm text-muted-foreground">
                                                        <button
                                                            onClick={() => handleSort('created_at')}
                                                            className="flex items-center gap-2 hover:text-foreground transition-colors"
                                                        >
                                                            Fecha de Creación
                                                            {getSortIcon('created_at')}
                                                        </button>
                                                    </th>
                                                    <th className="text-left py-3 px-4 font-medium text-sm text-muted-foreground">
                                                        Usuarios
                                                    </th>
                                                    <th className="text-right py-3 px-4 font-medium text-sm text-muted-foreground">
                                                        Acciones
                                                    </th>
                                                </tr>
                                            </thead>
                                            <tbody className="divide-y divide-border/50">
                                                {roles.data.map((role) => (
                                                    <tr key={role.id} className="hover:bg-muted/30 transition-colors">
                                                        {/* Columna Rol con permisos debajo */}
                                                        <td className="py-4 px-4">
                                                            <div className="flex items-center gap-3">
                                                                <div className="w-10 h-10 rounded-full bg-primary/10 flex items-center justify-center flex-shrink-0">
                                                                    <Shield className="w-5 h-5 text-primary" />
                                                                </div>
                                                                <div className="min-w-0">
                                                                    <div className="font-medium text-sm text-foreground truncate">
                                                                        {role.name}
                                                                    </div>
                                                                    <div className="flex items-center gap-2 mt-1">
                                                                        {role.is_system && (
                                                                            <Badge variant="secondary" className="text-xs px-2 py-0.5">
                                                                                Sistema
                                                                            </Badge>
                                                                        )}
                                                                        <span className="text-xs text-muted-foreground">
                                                                            {role.permissions.length} permiso(s)
                                                                        </span>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </td>

                                                        {/* Columna Descripción */}
                                                        <td className="py-4 px-4">
                                                            <div className="text-sm text-muted-foreground max-w-xs">
                                                                {role.description ? role.description : 'Sin descripción'}
                                                            </div>
                                                        </td>

                                                        {/* Columna Fecha de Creación */}
                                                        <td className="py-4 px-4">
                                                            <div className="text-sm text-muted-foreground">
                                                                {new Date(role.created_at).toLocaleDateString('es-ES', {
                                                                    day: '2-digit',
                                                                    month: '2-digit', 
                                                                    year: 'numeric'
                                                                })}
                                                            </div>
                                                        </td>

                                                        {/* Columna Usuarios */}
                                                        <td className="py-4 px-4">
                                                            <Button
                                                                variant="ghost"
                                                                size="sm"
                                                                onClick={() => openUsersModal(role)}
                                                                disabled={role.users_count === 0}
                                                                className="h-8 px-3 text-sm font-medium"
                                                                title={`Ver usuarios con rol ${role.name}`}
                                                            >
                                                                <Users className="w-4 h-4 mr-2" />
                                                                {role.users_count} usuario(s)
                                                            </Button>
                                                        </td>

                                                        {/* Columna Acciones */}
                                                        <td className="py-4 px-4">
                                                            <div className="flex items-center justify-end">
                                                                <ActionsMenu
                                                                    editHref={`/roles/${role.id}/edit`}
                                                                    onDelete={() => openDeleteDialog(role)}
                                                                    canEdit={role.name === 'admin' || !role.is_system}
                                                                    canDelete={!role.is_system}
                                                                    isDeleting={deletingRole === role.id}
                                                                    editTitle="Editar rol"
                                                                    deleteTitle="Eliminar rol"
                                                                />
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
                                        {roles.data.map((role) => (
                                            <div key={role.id} className="bg-card border border-border rounded-lg p-4 space-y-3 hover:bg-muted/50 transition-colors">
                                                {/* Header con rol y estado */}
                                                <div className="flex items-center justify-between">
                                                    <div className="flex items-center space-x-3 flex-1 min-w-0">
                                                        <div className="w-8 h-8 rounded-full bg-primary/10 flex items-center justify-center flex-shrink-0">
                                                            <Shield className="w-4 h-4 text-primary" />
                                                        </div>
                                                        <div className="min-w-0 flex-1">
                                                            <div className="flex items-center gap-2">
                                                                <span className="font-medium text-sm break-words">{role.name}</span>
                                                                {role.is_system && (
                                                                    <Badge variant="secondary" className="text-xs px-2 py-0.5">
                                                                        Sistema
                                                                    </Badge>
                                                                )}
                                                            </div>
                                                            <div className="text-xs text-muted-foreground mt-1">
                                                                {role.permissions.length} permiso(s) • {role.users_count} usuario(s)
                                                            </div>
                                                            <div className="text-xs text-muted-foreground">
                                                                Creado: {new Date(role.created_at).toLocaleDateString('es-ES', {
                                                                    day: '2-digit',
                                                                    month: '2-digit', 
                                                                    year: 'numeric'
                                                                })}
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                {/* Descripción */}
                                                <div className="text-sm text-muted-foreground break-words leading-relaxed line-clamp-3">
                                                    {role.description ? role.description : 'Sin descripción'}
                                                </div>

                                                {/* Usuarios y acciones */}
                                                <div className="flex items-center justify-between pt-2 border-t border-border">
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        onClick={() => openUsersModal(role)}
                                                        disabled={role.users_count === 0}
                                                        className="h-8 px-3 text-sm font-medium"
                                                        title={`Ver usuarios con rol ${role.name}`}
                                                    >
                                                        <Users className="w-4 h-4 mr-2" />
                                                        {role.users_count} usuario(s)
                                                    </Button>
                                                    
                                                    <ActionsMenu
                                                        editHref={`/roles/${role.id}/edit`}
                                                        onDelete={() => openDeleteDialog(role)}
                                                        canEdit={role.name === 'admin' || !role.is_system}
                                                        canDelete={!role.is_system}
                                                        isDeleting={deletingRole === role.id}
                                                        editTitle="Editar rol"
                                                        deleteTitle="Eliminar rol"
                                                    />
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                </div>
                            </>
                        ) : (
                            <div className="text-center py-12 text-muted-foreground">
                                <div className="flex flex-col items-center space-y-3">
                                    <Shield className="w-12 h-12 text-muted-foreground/50" />
                                    <div className="space-y-1">
                                        <p className="text-lg font-medium">No se encontraron roles</p>
                                        <p className="text-sm">
                                            {filters.search 
                                                ? `No hay roles que coincidan con "${filters.search}"`
                                                : 'No hay roles disponibles en el sistema'
                                            }
                                        </p>
                                    </div>
                                    {filters.search && (
                                        <Button
                                            onClick={handleClear}
                                            variant="outline"
                                            size="sm"
                                        >
                                            <X className="h-4 w-4 mr-2" />
                                            Limpiar búsqueda
                                        </Button>
                                    )}
                                </div>
                            </div>
                        )}
                        
                        <PaginationWrapper
                            data={roles}
                            routeName={route('roles.index')}
                            filters={{
                                search: filters.search,
                                per_page: filters.per_page,
                                sort_field: sortField,
                                sort_direction: sortDirection
                            }}
                        />
                    </CardContent>
                </Card>

                {/* Modal para mostrar usuarios del rol */}
                <Dialog open={showUsersModal} onOpenChange={closeUsersModal}>
                    <DialogContent className="max-w-2xl">
                        <DialogHeader>
                            <DialogTitle>
                                Usuarios con rol "{selectedRole?.name}"
                            </DialogTitle>
                            <DialogDescription>
                                Lista de usuarios que tienen asignado este rol.
                            </DialogDescription>
                        </DialogHeader>
                        
                        <ScrollArea className="max-h-96">
                            <div className="space-y-3">
                                {usersInRole.length > 0 ? (
                                    usersInRole.map((user) => (
                                        <div key={user.id} className="flex items-center justify-between p-3 bg-muted/50 rounded-lg hover:bg-muted/70 transition-colors">
                                            <div className="flex items-center space-x-3">
                                                <div className="w-8 h-8 rounded-full bg-primary/10 flex items-center justify-center">
                                                    <Users className="w-4 h-4 text-primary" />
                                                </div>
                                                <div>
                                                    <div className="font-medium text-sm">{user.name}</div>
                                                    <div className="text-xs text-muted-foreground">{user.email}</div>
                                                </div>
                                            </div>
                                        </div>
                                    ))
                                ) : (
                                    <div className="text-center py-8 text-muted-foreground">
                                        <div className="flex flex-col items-center space-y-2">
                                            <Users className="w-8 h-8 text-muted-foreground/50" />
                                            <span>No hay usuarios asignados a este rol</span>
                                        </div>
                                    </div>
                                )}
                            </div>
                        </ScrollArea>

                        <div className="flex justify-end pt-4 border-t border-border">
                            <Button variant="outline" onClick={closeUsersModal}>
                                Cerrar
                            </Button>
                        </div>
                    </DialogContent>
                </Dialog>

                {/* Dialog de confirmación para eliminar */}
                <Dialog open={showDeleteDialog} onOpenChange={closeDeleteDialog}>
                    <DialogContent>
                        <DialogHeader>
                            <DialogTitle>Eliminar Rol</DialogTitle>
                            <DialogDescription>
                                ¿Estás seguro de que deseas eliminar el rol <strong>"{selectedRole?.name}"</strong>?
                                Esta acción no se puede deshacer.
                            </DialogDescription>
                        </DialogHeader>
                        <DialogFooter>
                            <Button variant="outline" onClick={closeDeleteDialog}>
                                Cancelar
                            </Button>
                            <Button 
                                variant="destructive"
                                onClick={handleDeleteRole}
                                disabled={deletingRole !== null}
                            >
                                {deletingRole ? 'Eliminando...' : 'Eliminar'}
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>
            </div>
        </AppLayout>
    );
}