import { showNotification } from '@/hooks/useNotifications';
import { useForm } from '@inertiajs/react';
import React from 'react';

import { CreatePageLayout } from '@/components/create-page-layout';
import { FormSection } from '@/components/form-section';
import { CreateRolesSkeleton } from '@/components/skeletons';
import { Checkbox } from '@/components/ui/checkbox';
import { FormError } from '@/components/ui/form-error';
import { FormField } from '@/components/ui/form-field';
import { Input } from '@/components/ui/input';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Textarea } from '@/components/ui/textarea';
import { Shield } from 'lucide-react';

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

/**
 * Props de la página
 */
interface CreateRolePageProps {
    permissions: Record<string, Permission[]>;
}

/**
 * Página de creación de roles
 */
export default function CreateRole({ permissions }: CreateRolePageProps) {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        description: '',
        permissions: [] as string[],
    });

    /**
     * Maneja el envío del formulario
     */
    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/roles', {
            onSuccess: () => {
                // Éxito manejado automáticamente por el layout
            },
            onError: (errors) => {
                // Los errores de validación se muestran automáticamente
                // Los errores del servidor se manejan por el layout
                if (Object.keys(errors).length === 0) {
                    showNotification.error('Error del servidor al crear el rol. Inténtalo de nuevo.');
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

    return (
        <CreatePageLayout
            title="Crear Nuevo Rol"
            description="Define un nuevo rol con permisos específicos para los usuarios"
            backHref="/roles"
            onSubmit={handleSubmit}
            processing={processing}
            pageTitle="Crear Rol"
            loading={processing}
            loadingSkeleton={CreateRolesSkeleton}
        >
            <FormSection icon={Shield} title="Información del Rol" description="Datos básicos del nuevo rol">
                <FormField label="Nombre del Rol" error={errors.name} required>
                    <Input id="name" type="text" value={data.name} onChange={(e) => setData('name', e.target.value)} placeholder="Ej: usuario" />
                </FormField>

                <FormField label="Descripción" error={errors.description}>
                    <Textarea
                        id="description"
                        value={data.description}
                        onChange={(e) => setData('description', e.target.value)}
                        placeholder="Describe las responsabilidades y alcance de este rol..."
                        className="min-h-[100px]"
                    />
                </FormField>
            </FormSection>

            <FormSection icon={Shield} title="Permisos del Rol" description="Selecciona las acciones que este rol puede realizar en cada página">
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
        </CreatePageLayout>
    );
}
