import React, { useState } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';

import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Checkbox } from '@/components/ui/checkbox';
import { Sheet, SheetContent, SheetDescription, SheetHeader, SheetTitle, SheetTrigger } from '@/components/ui/sheet';
import { ScrollArea } from '@/components/ui/scroll-area';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { BreadcrumbItem } from '@/types';
import { Shield, ArrowLeft, Save, Users, Settings } from 'lucide-react';
import { toast } from 'sonner';

/**
 * Breadcrumbs para la navegación de edición de roles
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
    {
        title: 'Editar Rol',
        href: '#',
    },
];

/**
 * Interfaz para los permisos agrupados
 */
interface Permission {
    id: number;
    name: string;
    display_name: string;
    description: string | null;
    group: string;
}

interface User {
    id: number;
    name: string;
    email: string;
}

/**
 * Interfaz para el rol
 */
interface Role {
    id: number;
    name: string;
    description: string | null;
    permissions: string[];
    users: User[];
}

/**
 * Props de la página
 */
interface EditRolePageProps {
    role: Role;
    permissions: Record<string, Permission[]>;
    all_users: User[];
}

/**
 * Página de edición de roles
 */
export default function EditRole({ role, permissions, all_users }: EditRolePageProps) {
    const { data, setData, patch, processing, errors } = useForm({
        name: role.name,
        description: role.description || '',
        permissions: role.permissions,
    });

    const [selectedUsers, setSelectedUsers] = useState<number[]>(
        role.users.map(user => user.id)
    );
    const [savingUsers, setSavingUsers] = useState(false);
    const [isUserSheetOpen, setIsUserSheetOpen] = useState(false);
    const [searchTerm, setSearchTerm] = useState('');

    /**
     * Maneja el envío del formulario
     */
    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        patch(`/roles/${role.id}`);
    };

    /**
     * Maneja la selección/deselección de permisos
     */
    const handlePermissionChange = (permissionName: string, checked: boolean) => {
        if (checked) {
            setData('permissions', [...data.permissions, permissionName]);
        } else {
            setData('permissions', data.permissions.filter(p => p !== permissionName));
        }
    };

    /**
     * Verifica si un permiso está seleccionado
     */
    const isPermissionSelected = (permissionName: string): boolean => {
        return data.permissions.includes(permissionName);
    };

    /**
     * Selecciona/deselecciona todos los permisos de un grupo
     */
    const handleGroupToggle = (groupPermissions: Permission[], checked: boolean) => {
        const groupPermissionNames = groupPermissions.map(p => p.name);
        
        if (checked) {
            const newPermissions = [...data.permissions];
            groupPermissionNames.forEach(permission => {
                if (!newPermissions.includes(permission)) {
                    newPermissions.push(permission);
                }
            });
            setData('permissions', newPermissions);
        } else {
            setData('permissions', data.permissions.filter(p => !groupPermissionNames.includes(p)));
        }
    };

    /**
     * Verifica si todos los permisos de un grupo están seleccionados
     */
    const isGroupSelected = (groupPermissions: Permission[]): boolean => {
        return groupPermissions.every(p => data.permissions.includes(p.name));
    };

    /**
     * Obtiene el nombre legible del grupo
     */
    const getGroupDisplayName = (group: string): string => {
        const groupNames: Record<string, string> = {
            'dashboard': 'Dashboard',
            'users': 'Usuarios',
            'activity': 'Actividad',
            'roles': 'Roles y Permisos',
            'settings': 'Configuración',
        };
        return groupNames[group] || group.charAt(0).toUpperCase() + group.slice(1);
    };

    /**
     * Maneja el cambio de usuarios asignados y guarda automáticamente
     */
    const handleUserChange = async (userId: number, checked: boolean): Promise<void> => {
        // Actualizar el estado local inmediatamente para feedback visual
        const newSelectedUsers = checked 
            ? [...selectedUsers, userId]
            : selectedUsers.filter(id => id !== userId);
        
        setSelectedUsers(newSelectedUsers);

        // Guardar automáticamente en el servidor
        try {
            const response = await fetch(`/roles/${role.id}/users`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
                body: JSON.stringify({ users: newSelectedUsers }),
            });

            if (response.ok) {
                if (checked) {
                    toast.success('Usuario agregado al rol');
                } else {
                    toast.success('Usuario removido del rol');
                }
            } else {
                // Revertir el cambio si falla
                setSelectedUsers(selectedUsers);
                const errorData = await response.json();
                toast.error(errorData.error || 'Error al actualizar usuarios del rol');
            }
        } catch (error) {
            // Revertir el cambio si falla
            setSelectedUsers(selectedUsers);
            console.error('Error saving users:', error);
            toast.error('Error de conexión al actualizar usuarios');
        }
    };

    /**
     * Guarda los usuarios asignados al rol
     */
    const saveUsers = async (): Promise<void> => {
        setSavingUsers(true);
        try {
            const response = await fetch(`/roles/${role.id}/users`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
                body: JSON.stringify({ users: selectedUsers }),
            });

            if (response.ok) {
                toast.success('Usuarios del rol actualizados correctamente');
                setIsUserSheetOpen(false); // Cierra el Sheet
            } else {
                const errorData = await response.json();
                toast.error(errorData.error || 'Error al actualizar usuarios del rol');
            }
        } catch (error) {
            console.error('Error saving users:', error);
            toast.error('Error de conexión al actualizar usuarios');
        } finally {
            setSavingUsers(false);
        }
    };

    /**
     * Filtra usuarios basado en el término de búsqueda
     */
    const filteredUsers = all_users.filter(user =>
        user.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
        user.email.toLowerCase().includes(searchTerm.toLowerCase())
    );

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Editar Rol: ${role.name}`} />
            
            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 overflow-x-auto">
                {/* Encabezado */}
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight">Editar Rol: {role.name}</h1>
                        <p className="text-muted-foreground">
                            Modifica los permisos y la información de este rol
                        </p>
                        {role.name === 'Administrador' && (
                            <div className="mt-2 p-3 bg-blue-50 border border-blue-200 rounded-lg">
                                <p className="text-sm text-blue-800">
                                    <strong>Rol del Sistema:</strong> Este rol tiene acceso completo a todas las funcionalidades 
                                    y se actualiza automáticamente con nuevos permisos.
                                </p>
                            </div>
                        )}
                    </div>
                    <Button variant="outline" asChild>
                        <Link href="/roles">
                            <ArrowLeft className="mr-2 h-4 w-4" />
                            Volver
                        </Link>
                    </Button>
                </div>

                <form onSubmit={handleSubmit} className="space-y-6">
                    {/* Información básica del rol */}
                    <div className="space-y-6">
                        <div className="space-y-3">
                            <Label htmlFor="name" className="text-base font-medium">Nombre del Rol</Label>
                            <Input
                                id="name"
                                type="text"
                                value={data.name}
                                onChange={e => setData('name', e.target.value)}
                                placeholder="ej: Gerente"
                                className={`h-11 ${errors.name ? 'border-red-500' : ''}`}
                            />
                            {errors.name && (
                                <p className="text-sm text-red-500 mt-1">{errors.name}</p>
                            )}
                        </div>

                        <div className="space-y-3">
                            <Label htmlFor="description" className="text-base font-medium">Descripción</Label>
                            <Textarea
                                id="description"
                                value={data.description}
                                onChange={e => setData('description', e.target.value)}
                                placeholder="Describe las responsabilidades y alcance de este rol..."
                                className={`min-h-[100px] ${errors.description ? 'border-red-500' : ''}`}
                            />
                            {errors.description && (
                                <p className="text-sm text-red-500 mt-1">{errors.description}</p>
                            )}
                        </div>
                    </div>

                    {/* Permisos */}
                    <div className="space-y-6">
                        <div className="space-y-2">
                            <h3 className="text-lg font-semibold">Permisos del Rol</h3>
                            <p className="text-sm text-muted-foreground">
                                {role.name === 'Administrador' 
                                    ? 'Este rol tiene automáticamente todos los permisos del sistema'
                                    : 'Selecciona las acciones que este rol puede realizar en cada página'
                                }
                            </p>
                        </div>
                            {role.name === 'Administrador' && (
                                <div className="mb-4 p-3 bg-green-50 border border-green-200 rounded-lg">
                                    <p className="text-sm text-green-800">
                                        <strong>Permisos Automáticos:</strong> El rol Administrador tiene acceso completo 
                                        a todas las funcionalidades del sistema y se actualiza automáticamente cuando se 
                                        agregan nuevas páginas o funcionalidades.
                                    </p>
                                </div>
                            )}
                            
                            {/* Tabla compacta de permisos */}
                            <div className="overflow-x-auto">
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead className="w-1/3">Página</TableHead>
                                            <TableHead className="w-16 text-center">Ver</TableHead>
                                            <TableHead className="w-16 text-center">Crear</TableHead>
                                            <TableHead className="w-16 text-center">Editar</TableHead>
                                            <TableHead className="w-16 text-center">Eliminar</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {Object.entries(permissions).map(([group, groupPermissions]) => {
                                            // Agrupar permisos por acción
                                            const actions = {
                                                view: groupPermissions.find(p => p.name.endsWith('.view')),
                                                create: groupPermissions.find(p => p.name.endsWith('.create')),
                                                edit: groupPermissions.find(p => p.name.endsWith('.edit')),
                                                delete: groupPermissions.find(p => p.name.endsWith('.delete'))
                                            };

                                            return (
                                                <TableRow key={group}>
                                                    <TableCell className="font-medium">
                                                        {getGroupDisplayName(group)}
                                                    </TableCell>
                                                    <TableCell className="text-center">
                                                        {actions.view && (
                                                            <Checkbox
                                                                id={actions.view?.name || ''}
                                                                checked={isPermissionSelected(actions.view?.name || '')}
                                                                onCheckedChange={(checked) => 
                                                                    handlePermissionChange(actions.view?.name || '', checked as boolean)
                                                                }
                                                                disabled={role.name === 'Administrador'}
                                                                className={role.name === 'Administrador' ? 'opacity-50 cursor-not-allowed' : ''}
                                                            />
                                                        )}
                                                    </TableCell>
                                                    <TableCell className="text-center">
                                                        {actions.create && (
                                                            <Checkbox
                                                                id={actions.create?.name || ''}
                                                                checked={isPermissionSelected(actions.create?.name || '')}
                                                                onCheckedChange={(checked) => 
                                                                    handlePermissionChange(actions.create?.name || '', checked as boolean)
                                                                }
                                                                disabled={role.name === 'Administrador'}
                                                                className={role.name === 'Administrador' ? 'opacity-50 cursor-not-allowed' : ''}
                                                            />
                                                        )}
                                                    </TableCell>
                                                    <TableCell className="text-center">
                                                        {actions.edit && (
                                                            <Checkbox
                                                                id={actions.edit?.name || ''}
                                                                checked={isPermissionSelected(actions.edit?.name || '')}
                                                                onCheckedChange={(checked) => 
                                                                    handlePermissionChange(actions.edit?.name || '', checked as boolean)
                                                                }
                                                                disabled={role.name === 'Administrador'}
                                                                className={role.name === 'Administrador' ? 'opacity-50 cursor-not-allowed' : ''}
                                                            />
                                                        )}
                                                    </TableCell>
                                                    <TableCell className="text-center">
                                                        {actions.delete && (
                                                            <Checkbox
                                                                id={actions.delete?.name || ''}
                                                                checked={isPermissionSelected(actions.delete?.name || '')}
                                                                onCheckedChange={(checked) => 
                                                                    handlePermissionChange(actions.delete?.name || '', checked as boolean)
                                                                }
                                                                disabled={role.name === 'Administrador'}
                                                                className={role.name === 'Administrador' ? 'opacity-50 cursor-not-allowed' : ''}
                                                            />
                                                        )}
                                                    </TableCell>
                                                </TableRow>
                                            );
                                        })}
                                    </TableBody>
                                </Table>
                            </div>
                            
                            {errors.permissions && (
                                <p className="text-sm text-red-500 mt-2">{errors.permissions}</p>
                            )}
                        </div>

                    {/* Usuarios asignados al rol */}
                    <div className="space-y-6">
                        <div className="space-y-2">
                            <h3 className="text-lg font-semibold flex items-center gap-2">
                                <Users className="h-5 w-5" />
                                Usuarios del Rol
                            </h3>
                            <p className="text-sm text-muted-foreground">
                                Gestiona los usuarios que tienen asignado este rol
                            </p>
                        </div>
                        
                        <div className="flex items-center justify-between p-4 bg-gray-50 rounded-lg border">
                            <div className="flex items-center gap-3">
                                <div className="w-3 h-3 bg-blue-600 rounded-full"></div>
                                <div>
                                    <span className="text-sm font-medium text-gray-900">
                                        {selectedUsers.length} usuario(s) asignado(s)
                                    </span>
                                    <div className="text-xs text-gray-500">
                                        • {selectedUsers.length} seleccionado(s)
                                    </div>
                                </div>
                            </div>
                            
                            <Sheet open={isUserSheetOpen} onOpenChange={setIsUserSheetOpen}>
                                <SheetTrigger asChild>
                                    <Button variant="outline" size="sm">
                                        <Settings className="h-4 w-4 mr-2" />
                                        Gestionar Usuarios
                                    </Button>
                                </SheetTrigger>
                                <SheetContent className="w-[400px] sm:w-[540px]">
                                    <SheetHeader>
                                        <SheetTitle>Gestionar Usuarios del Rol</SheetTitle>
                                        <SheetDescription>
                                            Selecciona los usuarios que tendrán este rol
                                        </SheetDescription>
                                    </SheetHeader>
                                    
                                    <div className="mt-6 space-y-4">
                                        {/* Buscador */}
                                        <div className="relative">
                                            <Input
                                                type="text"
                                                placeholder="Buscar usuarios..."
                                                value={searchTerm}
                                                onChange={(e) => setSearchTerm(e.target.value)}
                                                className="pr-8"
                                            />
                                            <Users className="absolute right-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-400" />
                                        </div>

                                        {/* Lista de usuarios con scroll */}
                                        <ScrollArea className="h-[400px] w-full rounded-md border p-4">
                                            <div className="space-y-2">
                                                {filteredUsers.map((user) => (
                                                    <div 
                                                        key={user.id} 
                                                        className={`flex items-center gap-3 p-3 rounded-lg border transition-all duration-200 ${
                                                            selectedUsers.includes(user.id) 
                                                                ? 'border-blue-200 bg-blue-50' 
                                                                : 'border-gray-200 hover:border-gray-300 hover:bg-gray-50'
                                                        }`}
                                                    >
                                                        <Checkbox
                                                            id={`user-${user.id}`}
                                                            checked={selectedUsers.includes(user.id)}
                                                            onCheckedChange={(checked) => handleUserChange(user.id, checked as boolean)}
                                                            className="data-[state=checked]:bg-blue-600 data-[state=checked]:border-blue-600"
                                                        />
                                                        <div className="flex-1 min-w-0">
                                                            <Label 
                                                                htmlFor={`user-${user.id}`}
                                                                className="text-sm font-medium cursor-pointer block text-gray-900"
                                                            >
                                                                {user.name}
                                                            </Label>
                                                            <p className="text-xs text-gray-500 truncate">
                                                                {user.email}
                                                            </p>
                                                        </div>
                                                        {selectedUsers.includes(user.id) && (
                                                            <div className="flex-shrink-0">
                                                                <div className="w-2 h-2 bg-blue-600 rounded-full"></div>
                                                            </div>
                                                        )}
                                                    </div>
                                                ))}
                                            </div>
                                        </ScrollArea>

                                        {/* Información y botón de cerrar */}
                                        <div className="flex justify-between items-center pt-4 border-t">
                                            <div className="text-sm text-gray-500">
                                                Los cambios se guardan automáticamente
                                            </div>
                                            <Button 
                                                variant="outline"
                                                onClick={() => setIsUserSheetOpen(false)}
                                            >
                                                Cerrar
                                            </Button>
                                        </div>
                                    </div>
                                </SheetContent>
                            </Sheet>
                        </div>
                    </div>

                    {/* Botones de acción */}
                    <div className="flex items-center justify-end space-x-2">
                        <Button type="button" variant="outline" asChild>
                            <Link href="/roles">Cancelar</Link>
                        </Button>
                        <Button type="submit" disabled={processing}>
                            <Save className="mr-2 h-4 w-4" />
                            {processing ? 'Guardando...' : 'Guardar Cambios'}
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}

