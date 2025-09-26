import { showNotification } from '@/hooks/useNotifications';
import { Head, router } from '@inertiajs/react';
import { useCallback, useState } from 'react';

import { DeleteConfirmationDialog } from '@/components/DeleteConfirmationDialog';
import { Badge } from '@/components/ui/badge';
import AppLayout from '@/layouts/app-layout';

import { BadgeGroup } from '@/components/CardLayout';
import { DataTable } from '@/components/DataTable';
import { EntityInfoCell } from '@/components/EntityInfoCell';
import { UsersSkeleton } from '@/components/skeletons';
import { StandardMobileCard } from '@/components/StandardMobileCard';
import { StatusBadge, USER_STATUS_CONFIGS } from '@/components/status-badge';
import { TableActions } from '@/components/TableActions';
import { Clock, Users } from 'lucide-react';
import { NOTIFICATIONS } from '@/constants/ui-constants';

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
 * Formatea una fecha
 */
const formatDate = (dateString: string): string => {
    return new Date(dateString).toLocaleDateString('es-ES', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};

/**
 * Componente para renderizar la columna de usuario
 */
const UserCell: React.FC<{ user: User }> = ({ user }) => <EntityInfoCell icon={Users} primaryText={user.name} secondaryText={user.email} />;

/**
 * Componente para renderizar badges de roles
 */
const RoleBadges: React.FC<{ roles: Role[] }> = ({ roles }) => (
    <div className="flex flex-wrap gap-1">
        {roles.length > 0 ? (
            roles.slice(0, 2).map((role) => (
                <Badge key={role.id} variant={role.is_system ? 'secondary' : 'default'} className="px-2 py-1 text-xs">
                    {role.name}
                </Badge>
            ))
        ) : (
            <span className="text-xs text-muted-foreground">Sin roles</span>
        )}
        {roles.length > 2 && (
            <Badge variant="outline" className="px-2 py-1 text-xs">
                +{roles.length - 2}
            </Badge>
        )}
    </div>
);

/**
 * Componente para renderizar el estado del usuario
 */
const UserStatusBadge: React.FC<{ status: string }> = ({ status }) => <StatusBadge status={status} configs={USER_STATUS_CONFIGS} />;

/**
 * Componente para la mobile card del usuario
 */
const UserMobileCard: React.FC<{ user: User; onDelete: (user: User) => void; isDeleting: boolean }> = ({ user, onDelete, isDeleting }) => {
    return (
        <StandardMobileCard
            icon={Users}
            title={user.name}
            subtitle={user.email}
            badge={{
                children: <StatusBadge status={user.status} configs={USER_STATUS_CONFIGS} />,
            }}
            dataFields={[
                {
                    label: 'Roles',
                    value: (
                        <BadgeGroup>
                            {user.roles.length > 0 ? (
                                user.roles.map((role) => (
                                    <Badge key={role.id} variant={role.is_system ? 'secondary' : 'default'} className="text-xs">
                                        {role.name}
                                    </Badge>
                                ))
                            ) : (
                                <span className="text-xs text-muted-foreground">Sin roles</span>
                            )}
                        </BadgeGroup>
                    ),
                },
                {
                    label: 'Verificación',
                    value: (
                        <Badge variant={user.email_verified_at ? 'default' : 'destructive'} className="text-xs">
                            {user.email_verified_at ? 'Verificado' : 'Sin verificar'}
                        </Badge>
                    ),
                },
                {
                    label: 'Última actividad',
                    value: user.last_activity ? formatDate(user.last_activity) : 'Nunca',
                },
                {
                    label: 'Creado',
                    value: formatDate(user.created_at),
                },
            ]}
            actions={{
                editHref: `/users/${user.id}/edit`,
                onDelete: () => onDelete(user),
                isDeleting,
                editTooltip: 'Editar usuario',
                deleteTooltip: 'Eliminar usuario',
            }}
        />
    );
};

/**
 * Página principal de usuarios
 */
export default function UsersIndex({ users, total_users, online_users, filters }: UsersPageProps) {
    const [deletingUser, setDeletingUser] = useState<number | null>(null);
    const [userToDelete, setUserToDelete] = useState<User | null>(null);
    const [showDeleteDialog, setShowDeleteDialog] = useState(false);

    const openDeleteDialog = useCallback((user: User) => {
        setUserToDelete(user);
        setShowDeleteDialog(true);
    }, []);

    const closeDeleteDialog = useCallback(() => {
        setUserToDelete(null);
        setShowDeleteDialog(false);
        setDeletingUser(null);
    }, []);

    const handleDeleteUser = async () => {
        if (!userToDelete) return;

        setDeletingUser(userToDelete.id);
        router.delete(`/users/${userToDelete.id}`, {
            onSuccess: () => {
                closeDeleteDialog();
            },
            onError: (error) => {
                setDeletingUser(null);
                if (error.message) {
                    showNotification.error(error.message);
                } else {
                    showNotification.error(NOTIFICATIONS.error.deleteUser);
                }
            },
        });
    };

    // Definición de columnas para la tabla
    const columns = [
        {
            key: 'user',
            title: 'Usuario',
            width: 'lg' as const,
            sortable: true,
            render: (user: User) => <UserCell user={user} />,
        },
        {
            key: 'roles',
            title: 'Roles',
            width: 'md' as const,
            render: (user: User) => <RoleBadges roles={user.roles} />,
        },
        {
            key: 'created_at',
            title: 'Información',
            width: 'lg' as const,
            sortable: true,
            render: (user: User) => (
                <div className="space-y-1 text-sm">
                    <div className="flex items-center gap-2">
                        <span className="text-muted-foreground">Estado:</span>
                        <Badge variant={user.email_verified_at ? 'default' : 'destructive'} className="text-xs">
                            {user.email_verified_at ? 'Verificado' : 'Sin verificar'}
                        </Badge>
                    </div>
                    <div className="text-muted-foreground">Última actividad: {user.last_activity ? formatDate(user.last_activity) : 'Nunca'}</div>
                    <div className="text-muted-foreground">Creado: {formatDate(user.created_at)}</div>
                </div>
            ),
        },
        {
            key: 'status',
            title: 'Estado',
            width: 'sm' as const,
            textAlign: 'center' as const,
            sortable: true,
            render: (user: User) => <UserStatusBadge status={user.status} />,
        },
        {
            key: 'actions',
            title: 'Acciones',
            width: 'xs' as const,
            textAlign: 'right' as const,
            render: (user: User) => (
                <TableActions
                    editHref={`/users/${user.id}/edit`}
                    onDelete={() => openDeleteDialog(user)}
                    isDeleting={deletingUser === user.id}
                    editTooltip="Editar usuario"
                    deleteTooltip="Eliminar usuario"
                />
            ),
        },
    ];

    // Stats para mostrar en el header
    const stats = [
        {
            title: 'usuarios',
            value: total_users,
            icon: <Users className="h-3 w-3 text-primary" />,
        },
        {
            title: 'en línea',
            value: online_users,
            icon: <Clock className="h-3 w-3 text-green-600" />,
        },
        {
            title: 'desconectados',
            value: total_users - online_users,
            icon: <Users className="h-3 w-3 text-red-600" />,
        },
    ];

    return (
        <AppLayout>
            <Head title="Usuarios" />

            <DataTable
                title="Gestión de Usuarios"
                description="Administra los usuarios del sistema."
                data={users}
                columns={columns}
                stats={stats}
                filters={filters}
                createUrl="/users/create"
                createLabel="Crear Usuario"
                searchPlaceholder="Buscar usuarios..."
                loadingSkeleton={UsersSkeleton}
                renderMobileCard={(user) => <UserMobileCard user={user} onDelete={openDeleteDialog} isDeleting={deletingUser === user.id} />}
                routeName="/users"
                breakpoint="md"
            />

            <DeleteConfirmationDialog
                isOpen={showDeleteDialog}
                onClose={closeDeleteDialog}
                onConfirm={handleDeleteUser}
                isDeleting={deletingUser !== null}
                entityName={userToDelete?.name || ''}
                entityType="usuario"
            />
        </AppLayout>
    );
}
