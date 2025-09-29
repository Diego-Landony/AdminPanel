import { useForm } from '@inertiajs/react';
import React, { useState } from 'react';
import { Users } from 'lucide-react';

import { EditPageLayout } from '@/components/edit-page-layout';
import { FormSection } from '@/components/form-section';
import { EditRolesSkeleton } from '@/components/skeletons';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { FormError } from '@/components/ui/form-error';
import { FormField } from '@/components/ui/form-field';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { ScrollArea } from '@/components/ui/scroll-area';
import { Sheet, SheetContent, SheetDescription, SheetHeader, SheetTitle, SheetTrigger } from '@/components/ui/sheet';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Textarea } from '@/components/ui/textarea';
import { showNotification } from '@/hooks/useNotifications';
import { ENTITY_ICONS } from '@/constants/section-icons';
import { PLACEHOLDERS, NOTIFICATIONS } from '@/constants/ui-constants';

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

    const [selectedUsers, setSelectedUsers] = useState<number[]>(role.users.map((user) => user.id));

    const [isUserSheetOpen, setIsUserSheetOpen] = useState(false);
    const [searchTerm, setSearchTerm] = useState('');

    // Verificar si es el rol de administrador
    const isAdminRole = role.name === 'admin';

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
                    showNotification.error(NOTIFICATIONS.error.serverRole);
                }
            },
        });
    };

    /**
     * Maneja la selección/deselección de permisos
     */
    const handlePermissionChange = (permissionName: string, checked: boolean) => {
        if (checked) {
            setData('permissions', [...data.permissions, permissionName]);
        } else {
            setData(
                'permissions',
                data.permissions.filter((p) => p !== permissionName),
            );
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
            dashboard: 'Dashboard',
            users: 'Usuarios',
            activity: 'Actividad',
            roles: 'Roles y Permisos',
            settings: 'Configuración',
        };
        return groupNames[group] || group.charAt(0).toUpperCase() + group.slice(1);
    };

    /**
     * Maneja el cambio de usuarios asignados y guarda automáticamente
     */
    const handleUserChange = async (userId: number, checked: boolean): Promise<void> => {
        // Actualizar el estado local inmediatamente para feedback visual
        const newSelectedUsers = checked ? [...selectedUsers, userId] : selectedUsers.filter((id) => id !== userId);

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
                    showNotification.success(NOTIFICATIONS.success.userAdded);
                } else {
                    showNotification.success(NOTIFICATIONS.success.userRemoved);
                }
            } else {
                // Revertir el cambio si falla
                setSelectedUsers(selectedUsers);
                const errorData = await response.json();
                showNotification.error(errorData.error || NOTIFICATIONS.error.updateUsers);
            }
        } catch (error) {
            // Revertir el cambio si falla
            setSelectedUsers(selectedUsers);
            console.error('Error saving users:', error);
            showNotification.error(NOTIFICATIONS.error.connectionUsers);
        }
    };

    /**
     * Filtra usuarios basado en el término de búsqueda
     */
    const filteredUsers = all_users.filter(
        (user) => user.name.toLowerCase().includes(searchTerm.toLowerCase()) || user.email.toLowerCase().includes(searchTerm.toLowerCase()),
    );

    return (
        <EditPageLayout
            title={`Editar Rol: ${role.name}`}
            description="Modifica los permisos y la información de este rol"
            backHref={route('roles.index')}
            onSubmit={handleSubmit}
            submitLabel={isAdminRole ? 'No Editable' : 'Actualizar'}
            processing={processing}
            disabled={isAdminRole}
            pageTitle={`Editar Rol - ${role.name}`}
            loading={processing}
            loadingSkeleton={EditRolesSkeleton}
        >
            <FormSection icon={ENTITY_ICONS.role.info} title="Información Básica">
                <FormField label="Nombre del Rol" error={errors.name}>
                    <Input
                        id="name"
                        type="text"
                        value={data.name}
                        onChange={(e) => setData('name', e.target.value)}
                        placeholder={PLACEHOLDERS.roleNameEdit}
                        disabled={isAdminRole}
                        className={isAdminRole ? 'cursor-not-allowed opacity-50' : ''}
                    />
                </FormField>

                <FormField label="Descripción" error={errors.description}>
                    <Textarea
                        id="description"
                        value={data.description}
                        onChange={(e) => setData('description', e.target.value)}
                        placeholder={PLACEHOLDERS.description}
                        className={`min-h-[100px] ${isAdminRole ? 'cursor-not-allowed opacity-50' : ''}`}
                        disabled={isAdminRole}
                    />
                </FormField>
            </FormSection>

            {/* Gestión de usuarios */}
            <div className="flex justify-start py-6">
                <Sheet open={isUserSheetOpen} onOpenChange={setIsUserSheetOpen}>
                    <SheetTrigger asChild>
                        <Button variant="outline">
                            <Users className="mr-2 h-4 w-4" />
                            Gestionar Usuarios del Rol
                        </Button>
                    </SheetTrigger>
                    <SheetContent className="w-[400px] p-4 sm:w-[540px]">
                        <SheetHeader className="pb-4">
                            <SheetTitle className="text-lg">Gestionar Usuarios del Rol</SheetTitle>
                            <SheetDescription className="text-sm">Selecciona los usuarios que tendrán este rol</SheetDescription>
                            <div className="mt-2">
                                <span className="text-xs text-muted-foreground">Los cambios se guardan automáticamente</span>
                            </div>
                        </SheetHeader>

                        <div className="space-y-4">
                            {/* Buscador compacto */}
                            <div className="relative">
                                <Input
                                    type="text"
                                    placeholder={PLACEHOLDERS.searchUsers}
                                    value={searchTerm}
                                    onChange={(e) => setSearchTerm(e.target.value)}
                                    className="h-9 text-sm"
                                />
                                <Users className="absolute top-1/2 right-3 h-4 w-4 -translate-y-1/2 transform text-muted-foreground" />
                            </div>

                            {/* Lista de usuarios compacta */}
                            <div className="overflow-hidden rounded-lg border">
                                <ScrollArea className="h-[350px]">
                                    <div className="p-2">
                                        {filteredUsers.map((user) => (
                                            <div
                                                key={user.id}
                                                className={`flex items-center gap-3 rounded-md p-2 transition-colors ${
                                                    selectedUsers.includes(user.id)
                                                        ? 'border border-primary/20 bg-primary/5'
                                                        : 'hover:bg-muted/50'
                                                }`}
                                            >
                                                <Checkbox
                                                    id={`user-${user.id}`}
                                                    checked={selectedUsers.includes(user.id)}
                                                    onCheckedChange={(checked) => handleUserChange(user.id, checked as boolean)}
                                                    className="data-[state=checked]:border-primary data-[state=checked]:bg-primary"
                                                />
                                                <div className="min-w-0 flex-1">
                                                    <Label
                                                        htmlFor={`user-${user.id}`}
                                                        className="block cursor-pointer text-sm font-medium"
                                                    >
                                                        {user.name}
                                                    </Label>
                                                    <p className="truncate text-xs text-muted-foreground">{user.email}</p>
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                </ScrollArea>
                            </div>
                        </div>
                    </SheetContent>
                </Sheet>
            </div>

            <FormSection icon={ENTITY_ICONS.role.permissions} title="Permisos del Rol" description={role.name === 'admin' ? 'Este rol tiene automáticamente todos los permisos del sistema' : 'Selecciona las acciones que este rol puede realizar en cada página'}>

                {role.name === 'admin' && (
                    <div className="mb-4 rounded-lg border border-green-200 bg-green-50 p-3">
                        <p className="text-sm text-green-800">
                            <strong>Permisos Automáticos:</strong> El rol Administrador tiene acceso completo a todas las funcionalidades
                            del sistema y se actualiza automáticamente cuando se agregan nuevas páginas o funcionalidades.
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
                                    view: groupPermissions.find((p) => p.name.endsWith('.view')),
                                    create: groupPermissions.find((p) => p.name.endsWith('.create')),
                                    edit: groupPermissions.find((p) => p.name.endsWith('.edit')),
                                    delete: groupPermissions.find((p) => p.name.endsWith('.delete')),
                                };

                                return (
                                    <TableRow key={group}>
                                        <TableCell className="font-medium">{getGroupDisplayName(group)}</TableCell>
                                        <TableCell className="text-center">
                                            {actions.view && (
                                                <Checkbox
                                                    id={actions.view?.name || ''}
                                                    checked={isPermissionSelected(actions.view?.name || '')}
                                                    onCheckedChange={(checked) =>
                                                        handlePermissionChange(actions.view?.name || '', checked as boolean)
                                                    }
                                                    disabled={role.name === 'admin'}
                                                    className={role.name === 'admin' ? 'cursor-not-allowed opacity-50' : ''}
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
                                                    className={role.name === 'admin' ? 'cursor-not-allowed opacity-50' : ''}
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
                                                    className={role.name === 'admin' ? 'cursor-not-allowed opacity-50' : ''}
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
                                                    className={role.name === 'admin' ? 'cursor-not-allowed opacity-50' : ''}
                                                />
                                            )}
                                        </TableCell>
                                    </TableRow>
                                );
                            })}
                        </TableBody>
                    </Table>
                </div>

                {errors.permissions && <FormError message={errors.permissions} />}
            </FormSection>
        </EditPageLayout>
    );
}
