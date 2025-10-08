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
                        {Object.entries(permissions).map(([groupName, groupPermissions]) => {
                            // Buscar el grupo en systemPages para obtener el icono
                            const groupPage = systemPages.find(p => p.group && p.group.toLowerCase() === groupName.toLowerCase());
                            const GroupIcon = groupPage ? groupIcons[groupPage.group] : null;

                            return (
                                <React.Fragment key={groupName}>
                                    {/* Header del grupo con icono si existe */}
                                    <TableRow className="bg-muted/30">
                                        <TableCell colSpan={5} className="font-semibold">
                                            <div className="flex items-center gap-2">
                                                {GroupIcon && <GroupIcon className="h-4 w-4" />}
                                                {groupName}
                                            </div>
                                        </TableCell>
                                    </TableRow>

                                    {/* Agrupar permisos por recurso (users.view, users.create -> users) */}
                                    {Object.entries(
                                        groupPermissions.reduce((acc, perm) => {
                                            const [resource] = perm.name.split('.');
                                            if (!acc[resource]) acc[resource] = [];
                                            acc[resource].push(perm);
                                            return acc;
                                        }, {} as Record<string, Permission[]>)
                                    ).map(([resource, perms]) => {
                                        const firstPerm = perms[0];

                                        return (
                                            <TableRow key={resource} className="hover:bg-muted/20">
                                                <TableCell className="pl-8">{firstPerm.display_name.replace(/\s*(ver|crear|editar|eliminar)\s*/i, '')}</TableCell>
                                                {['view', 'create', 'edit', 'delete'].map((action) => {
                                                    const permName = `${resource}.${action}`;
                                                    const permExists = perms.some(p => p.name === permName);

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
                                </React.Fragment>
                            );
                        })}
                    </TableBody>
                </Table>
            </div>
        </div>
    );
}
