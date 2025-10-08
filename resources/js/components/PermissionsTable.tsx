import React from 'react';
import { Checkbox } from '@/components/ui/checkbox';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { systemPages, groupIcons } from '@/components/app-sidebar';

/**
 * Interfaz para los permisos del backend
 */
interface Permission {
    id: number;
    name: string;
    display_name: string;
    description: string | null;
    group: string;
}

interface PermissionsTableProps {
    selectedPermissions: string[];
    onPermissionChange: (permissionName: string, checked: boolean) => void;
    permissions: Record<string, Permission[]>;
    disabled?: boolean;
}

/**
 * Tabla de permisos usando los datos del backend
 */
export function PermissionsTable({ selectedPermissions, onPermissionChange, permissions, disabled = false }: PermissionsTableProps) {
    // Reorganizar permisos según la estructura del sidebar
    const organizedPermissions = React.useMemo(() => {
        const result: Record<string, Array<{ resource: string; title: string; permissions: Permission[] }>> = {
            '__individual__': []
        };

        // Aplanar todos los permisos
        const allPermissions = Object.values(permissions).flat();

        // Procesar cada página del sidebar
        systemPages.forEach((page) => {
            // Buscar permisos que correspondan a esta página
            const pagePerms = allPermissions.filter(perm => perm.name.startsWith(page.name + '.'));

            if (pagePerms.length > 0) {
                const item = {
                    resource: page.name,
                    title: page.title,
                    permissions: pagePerms
                };

                if (page.group) {
                    if (!result[page.group]) {
                        result[page.group] = [];
                    }
                    result[page.group].push(item);
                } else {
                    result['__individual__'].push(item);
                }
            }
        });

        return result;
    }, [permissions]);

    return (
        <div className="overflow-hidden rounded-lg border">
            <Table>
                <TableHeader>
                    <TableRow className="bg-muted/50">
                        <TableHead className="w-1/3">Página</TableHead>
                        <TableHead className="w-16 text-center">Ver</TableHead>
                        <TableHead className="w-16 text-center">Crear</TableHead>
                        <TableHead className="w-16 text-center">Editar</TableHead>
                        <TableHead className="w-16 text-center">Eliminar</TableHead>
                    </TableRow>
                </TableHeader>
            </Table>

            <div className="max-h-[500px] overflow-y-auto">
                <Table>
                    <TableBody>
                        {/* Páginas individuales (sin grupo) */}
                        {organizedPermissions['__individual__']?.map((item) => {
                            const page = systemPages.find(p => p.name === item.resource);
                            const Icon = page?.icon;

                            return (
                                <TableRow key={item.resource} className="hover:bg-muted/20">
                                    <TableCell className="font-medium">
                                        <div className="flex items-center gap-2">
                                            {Icon && <Icon className="h-4 w-4 text-muted-foreground" />}
                                            {item.title}
                                        </div>
                                    </TableCell>
                                    {['view', 'create', 'edit', 'delete'].map((action) => {
                                        const permName = `${item.resource}.${action}`;
                                        const permExists = item.permissions.some(p => p.name === permName);

                                        return (
                                            <TableCell key={action} className="text-center">
                                                {permExists ? (
                                                    <Checkbox
                                                        checked={selectedPermissions.includes(permName)}
                                                        onCheckedChange={(checked) =>
                                                            onPermissionChange(permName, checked as boolean)
                                                        }
                                                        disabled={disabled}
                                                    />
                                                ) : (
                                                    <span className="text-muted-foreground">-</span>
                                                )}
                                            </TableCell>
                                        );
                                    })}
                                </TableRow>
                            );
                        })}

                        {/* Grupos (exactamente como sidebar) */}
                        {Object.entries(organizedPermissions)
                            .filter(([name]) => name !== '__individual__')
                            .map(([groupName, items]) => {
                                const GroupIcon = groupIcons[groupName];

                                return (
                                    <React.Fragment key={groupName}>
                                        {/* Header del grupo con icono */}
                                        <TableRow className="bg-muted/30">
                                            <TableCell colSpan={5} className="font-semibold">
                                                <div className="flex items-center gap-2">
                                                    {GroupIcon && <GroupIcon className="h-4 w-4" />}
                                                    {groupName}
                                                </div>
                                            </TableCell>
                                        </TableRow>

                                        {/* Páginas del grupo (sin iconos, como sidebar) */}
                                        {items.map((item) => (
                                            <TableRow key={item.resource} className="hover:bg-muted/20">
                                                <TableCell className="pl-8">{item.title}</TableCell>
                                                {['view', 'create', 'edit', 'delete'].map((action) => {
                                                    const permName = `${item.resource}.${action}`;
                                                    const permExists = item.permissions.some(p => p.name === permName);

                                                    return (
                                                        <TableCell key={action} className="text-center">
                                                            {permExists ? (
                                                                <Checkbox
                                                                    checked={selectedPermissions.includes(permName)}
                                                                    onCheckedChange={(checked) =>
                                                                        onPermissionChange(permName, checked as boolean)
                                                                    }
                                                                    disabled={disabled}
                                                                />
                                                            ) : (
                                                                <span className="text-muted-foreground">-</span>
                                                            )}
                                                        </TableCell>
                                                    );
                                                })}
                                            </TableRow>
                                        ))}
                                    </React.Fragment>
                                );
                            })}
                    </TableBody>
                </Table>
            </div>
        </div>
    );
}
