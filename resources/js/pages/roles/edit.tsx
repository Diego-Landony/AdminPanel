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
import { FormField } from '@/components/ui/form-field';
import { FormError } from '@/components/ui/form-error';
import { BreadcrumbItem } from '@/types';
import { ArrowLeft, Save, Users } from 'lucide-react';
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

    const [isUserSheetOpen, setIsUserSheetOpen] = useState(false);
    const [searchTerm, setSearchTerm] = useState('');
    


    // Solo manejar notificaciones del servidor (NO errores de validación)
    // Las notificaciones flash se manejan automáticamente por el layout

    /**
     * Maneja el envío del formulario
     */
    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        patch(`/roles/${role.id}`, {
            onSuccess: () => {
                // Éxito manejado automáticamente por el layout
            },
            onError: (errors) => {
                // Los errores de validación se muestran automáticamente
                // Los errores del servidor se manejan por el layout
                if (Object.keys(errors).length === 0) {
                    toast.error('Error del servidor al actualizar el rol. Inténtalo de nuevo.');
                }
            }
        });
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
     * Obtiene el nombre legible del grupo
     */
    const getGroupDisplayName = (group: string): string => {
        const groupNames: Record<string, string> = {
            'dashboard': 'Panel de Control',
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
                        {role.name === 'admin' && (
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
                    {/* Contenedor con ancho máximo para hacer el contenido más angosto */}
                    <div className="max-w-4xl mx-auto">
                        {/* Botón de gestión de usuarios en la parte superior izquierda */}
                        <div className="mb-6">
                            <Sheet open={isUserSheetOpen} onOpenChange={setIsUserSheetOpen}>
                                <SheetTrigger asChild>
                                    <Button variant="outline" size="sm">
                                        <Users className="h-4 w-4 mr-2" />
                                        Gestionar Usuarios del Rol
                                    </Button>
                                </SheetTrigger>
                                <SheetContent className="w-[400px] sm:w-[540px]">
                                    <SheetHeader className="pb-4">
                                        <SheetTitle className="text-lg">Gestionar Usuarios del Rol</SheetTitle>
                                        <SheetDescription className="text-sm">
                                            Selecciona los usuarios que tendrán este rol
                                        </SheetDescription>
                                    </SheetHeader>
                                    
                                    <div className="space-y-4">
                                        {/* Buscador compacto */}
                                        <div className="relative">
                                            <Input
                                                type="text"
                                                placeholder="Buscar usuarios..."
                                                value={searchTerm}
                                                onChange={(e) => setSearchTerm(e.target.value)}
                                                className="h-9 text-sm"
                                            />
                                            <Users className="absolute right-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                                        </div>

                                        {/* Lista de usuarios compacta */}
                                        <div className="border rounded-lg overflow-hidden">
                                            <ScrollArea className="h-[350px]">
                                                <div className="p-2">
                                                    {filteredUsers.map((user) => (
                                                        <div 
                                                            key={user.id} 
                                                            className={`flex items-center gap-3 p-2 rounded-md transition-colors ${
                                                                selectedUsers.includes(user.id) 
                                                                    ? 'bg-primary/5 border border-primary/20' 
                                                                    : 'hover:bg-muted/50'
                                                            }`}
                                                        >
                                                            <Checkbox
                                                                id={`user-${user.id}`}
                                                                checked={selectedUsers.includes(user.id)}
                                                                onCheckedChange={(checked) => handleUserChange(user.id, checked as boolean)}
                                                                className="data-[state=checked]:bg-primary data-[state=checked]:border-primary"
                                                            />
                                                            <div className="flex-1 min-w-0">
                                                                <Label 
                                                                    htmlFor={`user-${user.id}`}
                                                                    className="text-sm font-medium cursor-pointer block"
                                                                >
                                                                    {user.name}
                                                                </Label>
                                                                <p className="text-xs text-muted-foreground truncate">
                                                                    {user.email}
                                                                </p>
                                                            </div>
                                                        </div>
                                                    ))}
                                                </div>
                                            </ScrollArea>
                                        </div>

                                        {/* Footer compacto */}
                                        <div className="flex items-center justify-between pt-3 border-t">
                                            <span className="text-xs text-muted-foreground">
                                                Los cambios se guardan automáticamente
                                            </span>
                                            <Button 
                                                variant="outline" 
                                                size="sm"
                                                onClick={() => setIsUserSheetOpen(false)}
                                                className="ml-auto"
                                            >
                                                Cerrar
                                            </Button>
                                        </div>
                                    </div>
                                </SheetContent>
                            </Sheet>
                        </div>

                        {/* Información básica del rol */}
                        <div className="space-y-6">
                            <FormField
                                label="Nombre del Rol"
                                error={errors.name}
                            >
                                <Input
                                    id="name"
                                    type="text"
                                    value={data.name}
                                    onChange={e => setData('name', e.target.value)}
                                    placeholder="ej: Gerente"
                                />
                            </FormField>

                            <FormField
                                label="Descripción"
                                error={errors.description}
                            >
                                <Textarea
                                    id="description"
                                    value={data.description}
                                    onChange={e => setData('description', e.target.value)}
                                    placeholder="Describe las responsabilidades y alcance de este rol..."
                                    className="min-h-[100px]"
                                />
                            </FormField>
                        </div>

                        {/* Permisos */}
                        <div className="space-y-6">
                            <div className="space-y-2">
                                <h3 className="text-lg font-semibold">Permisos del Rol</h3>
                                <p className="text-sm text-muted-foreground">
                                    {role.name === 'admin' 
                                        ? 'Este rol tiene automáticamente todos los permisos del sistema'
                                        : 'Selecciona las acciones que este rol puede realizar en cada página'
                                    }
                                </p>
                            </div>
                            
                            {role.name === 'admin' && (
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
                                                                disabled={role.name === 'admin'}
                                                                className={role.name === 'admin' ? 'opacity-50 cursor-not-allowed' : ''}
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
                                                                disabled={role.name === 'admin'}
                                                                className={role.name === 'admin' ? 'opacity-50 cursor-not-allowed' : ''}
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
                                                                disabled={role.name === 'admin'}
                                                                className={role.name === 'admin' ? 'opacity-50 cursor-not-allowed' : ''}
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
                                                                disabled={role.name === 'admin'}
                                                                className={role.name === 'admin' ? 'opacity-50 cursor-not-allowed' : ''}
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
                                <FormError message={errors.permissions} />
                            )}
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

