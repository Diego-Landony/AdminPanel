import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Badge } from '@/components/ui/badge';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import {
    Pagination,
    PaginationContent,
    PaginationEllipsis,
    PaginationItem,
    PaginationLink,
    PaginationNext,
    PaginationPrevious,
} from "@/components/ui/pagination";
import { BreadcrumbItem } from '@/types';
import { Shield, Plus, Edit, Trash2, Users, AlertTriangle } from 'lucide-react';
import { toast } from 'sonner';

/**
 * Breadcrumbs para la navegación de roles
 */
const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Usuarios',
        href: '/users',
    },
    {
        title: 'Roles',
        href: '/roles',
    },
];

/**
 * Interfaz para los datos del rol
 */
interface User {
    id: number;
    name: string;
    email: string;
}

interface Role {
    id: number;
    name: string;
    description: string | null;
    is_system: boolean;
    permissions: string[];
    users_count: number;
    users: User[];
    created_at: string;
    updated_at: string;
}

/**
 * Props de la página
 */
interface RolesPageProps {
    roles: {
        data: Role[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        from: number;
        to: number;
    };
    permissions: Record<string, Array<{
        name: string;
        description: string | null;
        group: string;
    }>>;
    filters: {
        search: string;
        per_page: number;
    };
}

/**
 * Página principal de gestión de roles
 */
export default function RolesIndex({ roles, permissions, filters }: RolesPageProps) {
    const [deletingRole, setDeletingRole] = useState<number | null>(null);
    const [roleToDelete, setRoleToDelete] = useState<Role | null>(null);
    const [showDeleteDialog, setShowDeleteDialog] = useState(false);
    const [showUsersModal, setShowUsersModal] = useState(false);
    const [selectedRole, setSelectedRole] = useState<Role | null>(null);

    /**
     * Abre el dialog de confirmación para eliminar un rol
     */
    const openDeleteDialog = (role: Role) => {
        setRoleToDelete(role);
        setShowDeleteDialog(true);
    };

    /**
     * Confirma y elimina el rol
     */
    const confirmDeleteRole = () => {
        if (roleToDelete) {
            setDeletingRole(roleToDelete.id);
            setShowDeleteDialog(false);
            
            // Agregar headers CSRF y manejo de errores mejorado
            router.delete(`/roles/${roleToDelete.id}`, {
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
                onFinish: () => {
                    setDeletingRole(null);
                    setRoleToDelete(null);
                    toast.success('Rol eliminado exitosamente');
                },
                onError: (errors) => {
                    setDeletingRole(null);
                    console.error('Error eliminando rol:', errors);
                    // Mostrar notificación de error
                    toast.error('Error al eliminar el rol');
                }
            });
        }
    };

    /**
     * Cancela la eliminación
     */
    const cancelDelete = () => {
        setShowDeleteDialog(false);
        setRoleToDelete(null);
    };

    /**
     * Abre el modal de usuarios para un rol específico
     */
    const openUsersModal = (role: Role) => {
        setSelectedRole(role);
        setShowUsersModal(true);
    };

    /**
     * Cierra el modal de usuarios
     */
    const closeUsersModal = () => {
        setShowUsersModal(false);
        setSelectedRole(null);
    };



    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Roles" />
            
            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 overflow-x-auto">
                {/* Encabezado */}
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight">Roles del Sistema</h1>
                        <p className="text-muted-foreground">
                            Gestiona los roles y permisos de los usuarios
                        </p>
                    </div>
                    <Button asChild>
                        <Link href="/roles/create">
                            <Plus className="mr-2 h-4 w-4" />
                            Crear Rol
                        </Link>
                    </Button>
                </div>

                {/* Estadísticas */}
                <div className="grid gap-4 md:grid-cols-3 mb-6">
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Total de Roles</CardTitle>
                            <Shield className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{roles.total}</div>
                            <p className="text-xs text-muted-foreground">
                                Roles configurados en el sistema
                            </p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Roles del Sistema</CardTitle>
                            <Shield className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">
                                {roles.data.filter(role => role.is_system).length}
                            </div>
                            <p className="text-xs text-muted-foreground">
                                Roles predefinidos del sistema
                            </p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Roles Personalizados</CardTitle>
                            <Shield className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">
                                {roles.data.filter(role => !role.is_system).length}
                            </div>
                            <p className="text-xs text-muted-foreground">
                                Roles creados por administradores
                            </p>
                        </CardContent>
                    </Card>
                </div>

                {/* Filtros de Búsqueda */}
                <Card>
                    <CardHeader>
                        <CardTitle>Filtros de Búsqueda</CardTitle>
                        <CardDescription>
                            Filtra los roles por nombre o descripción
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div className="grid gap-4 md:grid-cols-2">
                            <div>
                                <label className="text-sm font-medium">Buscar</label>
                                <input
                                    type="text"
                                    placeholder="Buscar por nombre o descripción..."
                                    value={filters.search}
                                    onChange={(e) => {
                                        router.get('/roles', { 
                                            search: e.target.value,
                                            per_page: filters.per_page
                                        }, { 
                                            preserveState: true,
                                            preserveScroll: true 
                                        });
                                    }}
                                    className="mt-1 w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                />
                            </div>
                            <div>
                                <label className="text-sm font-medium">Por página</label>
                                <select
                                    value={filters.per_page}
                                    onChange={(e) => {
                                        router.get('/roles', { 
                                            search: filters.search,
                                            per_page: parseInt(e.target.value)
                                        }, { 
                                            preserveState: true,
                                            preserveScroll: true 
                                        });
                                    }}
                                    className="mt-1 w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                >
                                    <option value={10}>10</option>
                                    <option value={25}>25</option>
                                    <option value={50}>50</option>
                                    <option value={100}>100</option>
                                </select>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* Tabla de Roles */}
                <Card>
                    <CardHeader>
                        <CardTitle>Lista de Roles</CardTitle>
                        <CardDescription>
                            Todos los roles configurados en el sistema con sus permisos y usuarios
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead className="w-1/3">Rol</TableHead>
                                    <TableHead className="w-1/3">Descripción</TableHead>
                                    <TableHead className="w-24 text-center">Usuarios</TableHead>
                                    <TableHead className="w-32 text-center">Acciones</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {roles.data.map((role) => (
                                    <TableRow key={role.id}>
                                        <TableCell className="max-w-0 w-1/3">
                                            <div className="flex items-center space-x-3">
                                                <div className="w-8 h-8 rounded-full bg-primary/10 flex items-center justify-center flex-shrink-0">
                                                    <Shield className="w-4 h-4 text-primary" />
                                                </div>
                                                <div className="min-w-0 flex-1">
                                                    <div className="font-medium text-sm truncate">{role.name}</div>
                                                    {role.is_system && (
                                                        <div className="text-xs text-muted-foreground">Sistema</div>
                                                    )}
                                                </div>
                                            </div>
                                        </TableCell>
                                        <TableCell className="max-w-0 w-1/3">
                                            <div className="text-sm text-muted-foreground break-words leading-relaxed">
                                                {role.description ? (
                                                    <div className="whitespace-normal">
                                                        {role.description}
                                                    </div>
                                                ) : (
                                                    <span className="italic">Sin descripción</span>
                                                )}
                                            </div>
                                        </TableCell>
                                        <TableCell className="w-24 text-center">
                                            <button 
                                                className="inline-flex items-center gap-1 text-sm font-medium text-blue-600 hover:text-blue-800 hover:underline transition-colors"
                                                onClick={() => openUsersModal(role)}
                                                disabled={role.users_count === 0}
                                                title={`Ver usuarios con rol ${role.name}`}
                                            >
                                                <Users className="w-3 h-3" />
                                                {role.users_count}
                                            </button>

                                        </TableCell>
                                        <TableCell className="w-32">
                                            <div className="flex items-center justify-center space-x-1">
                                                {/* Permitir editar el rol Administrador, pero no otros roles del sistema */}
                                                {(role.name === 'Administrador' || !role.is_system) && (
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        asChild
                                                        className="h-8 w-8 p-0"
                                                        title={`Editar rol ${role.name}`}
                                                    >
                                                        <Link href={`/roles/${role.id}/edit`}>
                                                            <Edit className="h-4 w-4" />
                                                        </Link>
                                                    </Button>
                                                )}
                                                
                                                {/* Solo permitir eliminar roles que no sean del sistema */}
                                                {!role.is_system && (
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        onClick={() => openDeleteDialog(role)}
                                                        disabled={deletingRole === role.id}
                                                        className="h-8 w-8 p-0 text-red-600 hover:text-red-700 hover:bg-red-50"
                                                        title={`Eliminar rol ${role.name}`}
                                                    >
                                                        {deletingRole === role.id ? (
                                                            <div className="h-4 w-4 animate-spin rounded-full border-2 border-current border-t-transparent" />
                                                        ) : (
                                                            <Trash2 className="h-4 w-4" />
                                                        )}
                                                    </Button>
                                                )}
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                        
                        {/* Paginación */}
                        {roles.last_page > 1 && (
                            <div className="mt-6">
                                <Pagination>
                                    <PaginationContent>
                                        <PaginationItem>
                                            <PaginationPrevious 
                                                href="#" 
                                                onClick={(e) => {
                                                    e.preventDefault();
                                                    router.get('/roles', { 
                                                        page: roles.current_page - 1,
                                                        search: filters.search,
                                                        per_page: filters.per_page
                                                    }, { 
                                                        preserveState: true,
                                                        preserveScroll: true 
                                                    });
                                                }}
                                                className={roles.current_page <= 1 ? 'pointer-events-none opacity-50' : ''}
                                            />
                                        </PaginationItem>
                                        
                                        {/* Primera página */}
                                        {roles.current_page > 3 && (
                                            <>
                                                <PaginationItem>
                                                    <PaginationLink 
                                                        href="#" 
                                                        onClick={(e) => {
                                                            e.preventDefault();
                                                            router.get('/roles', { 
                                                                page: 1,
                                                                search: filters.search,
                                                                per_page: filters.per_page
                                                            }, { 
                                                                preserveState: true,
                                                                preserveScroll: true 
                                                            });
                                                        }}
                                                    >
                                                        1
                                                    </PaginationLink>
                                                </PaginationItem>
                                                {roles.current_page > 4 && (
                                                    <PaginationItem>
                                                        <PaginationEllipsis />
                                                    </PaginationItem>
                                                )}
                                            </>
                                        )}
                                        
                                        {/* Páginas alrededor de la actual */}
                                        {Array.from({ length: Math.min(3, roles.last_page) }, (_, i) => {
                                            const page = roles.current_page - 1 + i;
                                            if (page < 1 || page > roles.last_page) return null;
                                            
                                            return (
                                                <PaginationItem key={page}>
                                                    <PaginationLink 
                                                        href="#" 
                                                        onClick={(e) => {
                                                            e.preventDefault();
                                                            router.get('/roles', { 
                                                                page: page,
                                                                search: filters.search,
                                                                per_page: filters.per_page
                                                            }, { 
                                                                preserveState: true,
                                                                preserveScroll: true 
                                                            });
                                                        }}
                                                        isActive={page === roles.current_page}
                                                    >
                                                        {page}
                                                    </PaginationLink>
                                                </PaginationItem>
                                            );
                                        })}
                                        
                                        {/* Última página */}
                                        {roles.current_page < roles.last_page - 2 && (
                                            <>
                                                {roles.current_page < roles.last_page - 3 && (
                                                    <PaginationItem>
                                                        <PaginationEllipsis />
                                                    </PaginationItem>
                                                )}
                                                <PaginationItem>
                                                    <PaginationLink 
                                                        href="#" 
                                                        onClick={(e) => {
                                                            e.preventDefault();
                                                            router.get('/roles', { 
                                                                page: roles.last_page,
                                                                search: filters.search,
                                                                per_page: filters.per_page
                                                            }, { 
                                                                preserveState: true,
                                                                preserveScroll: true 
                                                            });
                                                        }}
                                                    >
                                                        {roles.last_page}
                                                    </PaginationLink>
                                                </PaginationItem>
                                            </>
                                        )}
                                        
                                        <PaginationItem>
                                            <PaginationNext 
                                                href="#" 
                                                onClick={(e) => {
                                                    e.preventDefault();
                                                    router.get('/roles', { 
                                                        page: roles.current_page + 1,
                                                        search: filters.search,
                                                        per_page: filters.per_page
                                                    }, { 
                                                        preserveState: true,
                                                        preserveScroll: true 
                                                    });
                                                }}
                                                className={roles.current_page >= roles.last_page ? 'pointer-events-none opacity-50' : ''}
                                            />
                                        </PaginationItem>
                                    </PaginationContent>
                                </Pagination>
                                
                                <div className="text-center text-sm text-muted-foreground mt-4">
                                    Página {roles.current_page} de {roles.last_page} - 
                                    Mostrando {roles.from} a {roles.to} de {roles.total} roles
                                </div>
                            </div>
                        )}
                    </CardContent>
                </Card>

                {/* Dialog de confirmación para eliminar rol */}
                <Dialog open={showDeleteDialog} onOpenChange={setShowDeleteDialog}>
                    <DialogContent className="sm:max-w-[425px]">
                        <DialogHeader>
                            <DialogTitle className="flex items-center gap-2">
                                <AlertTriangle className="h-5 w-5 text-red-500" />
                                Confirmar Eliminación
                            </DialogTitle>
                            <DialogDescription>
                                {roleToDelete && (
                                    <>
                                        ¿Estás seguro de que quieres eliminar el rol <strong>"{roleToDelete.name}"</strong>?
                                        {roleToDelete.users_count > 0 ? (
                                            <div className="mt-2 p-3 bg-yellow-50 border border-yellow-200 rounded-md">
                                                <div className="flex items-center gap-2 text-yellow-800">
                                                    <AlertTriangle className="h-4 w-4" />
                                                    <span className="font-medium">Advertencia Importante</span>
                                                </div>
                                                <p className="text-sm text-yellow-700 mt-1">
                                                    Este rol está asignado a <strong>{roleToDelete.users_count} usuario(s)</strong>. 
                                                    Si lo eliminas, todos estos usuarios <strong>perderán este rol automáticamente</strong>.
                                                </p>
                                                <div className="mt-2 text-xs text-yellow-600">
                                                    ⚠️ <strong>Consecuencia:</strong> Los usuarios quedarán sin este rol y podrían 
                                                    perder acceso a funcionalidades del sistema.
                                                </div>
                                            </div>
                                        ) : (
                                            <p className="mt-2 text-sm">
                                                Esta acción no se puede deshacer.
                                            </p>
                                        )}
                                    </>
                                )}
                            </DialogDescription>
                        </DialogHeader>
                        <DialogFooter>
                            <Button variant="outline" onClick={cancelDelete}>
                                Cancelar
                            </Button>
                            <Button 
                                variant="destructive" 
                                onClick={confirmDeleteRole}
                                disabled={deletingRole !== null}
                            >
                                {deletingRole !== null ? 'Eliminando...' : 'Eliminar Rol'}
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>

                {/* Modal de usuarios del rol */}
                <Dialog open={showUsersModal} onOpenChange={setShowUsersModal}>
                    <DialogContent className="sm:max-w-[500px]">
                        <DialogHeader>
                            <DialogTitle className="flex items-center gap-2">
                                <Users className="h-5 w-5 text-blue-500" />
                                Usuarios con rol "{selectedRole?.name}"
                            </DialogTitle>
                            <DialogDescription>
                                {selectedRole && selectedRole.users.length > 0 ? (
                                    `${selectedRole.users.length} usuario(s) tiene(n) asignado este rol`
                                ) : (
                                    'No hay usuarios asignados a este rol'
                                )}
                            </DialogDescription>
                        </DialogHeader>
                        
                        {selectedRole && selectedRole.users.length > 0 && (
                            <div className="max-h-96 overflow-y-auto">
                                <div className="space-y-2">
                                    {selectedRole.users.map((user) => (
                                        <div key={user.id} className="flex items-center gap-3 p-3 border rounded-lg hover:bg-gray-50">
                                            <div className="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                                                <span className="text-sm font-medium text-blue-600">
                                                    {user.name.charAt(0).toUpperCase()}
                                                </span>
                                            </div>
                                            <div className="flex-1 min-w-0">
                                                <div className="text-sm font-medium text-gray-900 truncate">
                                                    {user.name}
                                                </div>
                                                <div className="text-sm text-gray-500 truncate">
                                                    {user.email}
                                                </div>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        )}

                        <DialogFooter>
                            <Button variant="outline" onClick={closeUsersModal}>
                                Cerrar
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>
            </div>
        </AppLayout>
    );
}
