import React from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import { toast } from 'sonner';
import AppLayout from '@/layouts/app-layout';

import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Checkbox } from '@/components/ui/checkbox';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { FormField } from '@/components/ui/form-field';
import { FormError } from '@/components/ui/form-error';
import { BreadcrumbItem } from '@/types';
import { ArrowLeft, Save } from 'lucide-react';

/**
 * Breadcrumbs para la navegación de creación de roles
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
        title: 'Crear Rol',
        href: '/roles/create',
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
                    toast.error('Error del servidor al crear el rol. Inténtalo de nuevo.');
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
            'dashboard': 'Dashboard',
            'users': 'Usuarios',
            'activity': 'Actividad',
            'roles': 'Roles y Permisos',
            'settings': 'Configuración',
        };
        return groupNames[group] || group.charAt(0).toUpperCase() + group.slice(1);
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Crear Rol" />
            
            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 overflow-x-auto">
                {/* Encabezado */}
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight">Crear Nuevo Rol</h1>
                        <p className="text-muted-foreground">
                            Define un nuevo rol con permisos específicos para los usuarios
                        </p>
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
                        {/* Información básica del rol */}
                        <div className="space-y-6">
                            <FormField
                                label="Nombre del Rol"
                                error={errors.name}
                                required
                            >
                                <Input
                                    id="name"
                                    type="text"
                                    value={data.name}
                                    onChange={e => setData('name', e.target.value)}
                                    placeholder="Ej: usuario"
                                    className="h-11"
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
                                    Selecciona las acciones que este rol puede realizar en cada página
                                </p>
                            </div>

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
                            {processing ? 'Creando...' : 'Crear Rol'}
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}

