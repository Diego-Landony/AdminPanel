import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/react';
import { route } from 'ziggy-js';
import { useState, useCallback } from 'react';
import { toast } from 'sonner';

import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { ScrollArea } from '@/components/ui/scroll-area';
import { DeleteConfirmationDialog } from '@/components/DeleteConfirmationDialog';
import { Shield, UserCheck } from 'lucide-react';
import { EntityInfoCell } from '@/components/EntityInfoCell';
import { StandardMobileCard } from '@/components/StandardMobileCard';
import { RolesSkeleton } from '@/components/skeletons';
import { TableActions } from '@/components/TableActions';
import { DataTable } from '@/components/DataTable';
import {
  ResponsiveCard,
  ResponsiveCardHeader,
  ResponsiveCardContent,
  DataField,
  CardActions,
} from '@/components/CardLayout';

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
    custom: number;
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
 * Componente para renderizar la información básica del rol
 */
const RoleInfoCell: React.FC<{ role: Role }> = ({ role }) => {
  const badges = role.is_system ? (
    <Badge variant="secondary" className="text-xs px-2 py-0.5">
      Sistema
    </Badge>
  ) : undefined;

  return (
    <EntityInfoCell
      icon={Shield}
      primaryText={role.name}
      secondaryText={`${role.permissions.length} permiso(s) • ${role.users_count} usuario(s)`}
      badges={badges}
    />
  );
};

/**
 * Componente para la mobile card del rol
 */
const RoleMobileCard: React.FC<{ role: Role; onDelete: (role: Role) => void; isDeleting: boolean }> = ({ role, onDelete, isDeleting }) => {
  const [showUsersModal, setShowUsersModal] = useState(false);
  const [selectedRole, setSelectedRole] = useState<Role | null>(null);

  const openUsersModal = (role: Role) => {
    setSelectedRole(role);
    setShowUsersModal(true);
  };

  const closeUsersModal = () => {
    setShowUsersModal(false);
    setSelectedRole(null);
  };

  return (
    <>
      <StandardMobileCard
        icon={Shield}
        title={
          <div className="flex items-center gap-2">
            <span>{role.name}</span>
            {role.is_system && (
              <Badge variant="secondary" className="text-xs px-2 py-0.5">
                Sistema
              </Badge>
            )}
          </div>
        }
        subtitle={
          <span className="text-xs">
            {role.permissions.length} permiso(s) • {role.users_count} usuario(s)
          </span>
        }
        dataFields={[
          {
            label: "Descripción",
            value: role.description || 'Sin descripción'
          },
          {
            label: "Usuarios con este rol",
            value: role.users_count > 0 ? (
              <Button
                variant="outline"
                size="sm"
                onClick={() => openUsersModal(role)}
                className="h-7 px-3 text-xs"
              >
                <UserCheck className="w-3 h-3 mr-1" />
                Ver {role.users_count} usuario(s)
              </Button>
            ) : (
              <span className="text-xs text-muted-foreground">Ningún usuario asignado</span>
            )
          },
          {
            label: "Fecha de Creación",
            value: formatDate(role.created_at)
          }
        ]}
        actions={{
          editHref: route('roles.edit', role.id),
          onDelete: () => onDelete(role),
          isDeleting,
          editTooltip: "Editar rol",
          deleteTooltip: "Eliminar rol",
          canDelete: !role.is_system,
          showDelete: !role.is_system
        }}
      />

      {/* Modal para mostrar usuarios */}
      <Dialog open={showUsersModal} onOpenChange={closeUsersModal}>
        <DialogContent className="max-w-2xl">
          <DialogHeader>
            <DialogTitle>
              Usuarios con rol "{selectedRole?.name}"
            </DialogTitle>
          </DialogHeader>
          <ScrollArea className="max-h-96">
            <div className="space-y-3">
              {selectedRole?.users?.map((user) => (
                <div key={user.id} className="flex items-center justify-between p-3 rounded-lg border">
                  <div>
                    <div className="font-medium">{user.name}</div>
                    <div className="text-sm text-muted-foreground">{user.email}</div>
                  </div>
                </div>
              ))}
              {(!selectedRole?.users || selectedRole.users.length === 0) && (
                <div className="text-center text-muted-foreground py-8">
                  No hay usuarios con este rol
                </div>
              )}
            </div>
          </ScrollArea>
        </DialogContent>
      </Dialog>
    </>
  );
};

/**
 * Página principal de roles
 */
export default function RolesIndex({ roles, filters, roleStats }: RolesIndexProps) {
  const [showUsersModal, setShowUsersModal] = useState(false);
  const [selectedRole, setSelectedRole] = useState<Role | null>(null);
  const [deletingRole, setDeletingRole] = useState<number | null>(null);
  const [roleToDelete, setRoleToDelete] = useState<Role | null>(null);
  const [showDeleteDialog, setShowDeleteDialog] = useState(false);

  const openUsersModal = useCallback((role: Role) => {
    setSelectedRole(role);
    setShowUsersModal(true);
  }, []);

  const closeUsersModal = useCallback(() => {
    setShowUsersModal(false);
    setSelectedRole(null);
  }, []);

  const openDeleteDialog = useCallback((role: Role) => {
    setRoleToDelete(role);
    setShowDeleteDialog(true);
  }, []);

  const closeDeleteDialog = useCallback(() => {
    setRoleToDelete(null);
    setShowDeleteDialog(false);
    setDeletingRole(null);
  }, []);

  const handleDeleteRole = async () => {
    if (!roleToDelete) return;

    setDeletingRole(roleToDelete.id);
    router.delete(route('roles.destroy', roleToDelete.id), {
      onSuccess: () => {
        closeDeleteDialog();
        toast.success('Rol eliminado correctamente');
      },
      onError: (error) => {
        setDeletingRole(null);
        if (error.message) {
          toast.error(error.message);
        }
      }
    });
  };

  // Definición de columnas para la tabla
  const columns = [
    {
      key: 'role',
      title: 'Rol',
      width: 'lg' as const,
      sortable: true,
      render: (role: Role) => <RoleInfoCell role={role} />
    },
    {
      key: 'description',
      title: 'Descripción',
      width: 'xl' as const,
      truncate: 50,
      render: (role: Role) => (
        <span className="text-sm text-muted-foreground">
          {role.description || 'Sin descripción'}
        </span>
      )
    },
    {
      key: 'created_at',
      title: 'Fecha de Creación',
      width: 'md' as const,
      sortable: true,
      render: (role: Role) => (
        <span className="text-sm text-muted-foreground">
          {formatDate(role.created_at)}
        </span>
      )
    },
    {
      key: 'users',
      title: 'Usuarios',
      width: 'sm' as const,
      textAlign: 'center' as const,
      render: (role: Role) => (
        role.users_count > 0 ? (
          <Button
            variant="outline"
            size="sm"
            onClick={() => openUsersModal(role)}
            className="h-8 px-3 text-xs"
          >
            <UserCheck className="w-3 h-3 mr-1" />
            {role.users_count}
          </Button>
        ) : (
          <span className="text-xs text-muted-foreground">0</span>
        )
      )
    },
    {
      key: 'actions',
      title: 'Acciones',
      width: 'xs' as const,
      textAlign: 'right' as const,
      render: (role: Role) => (
        <TableActions
          editHref={route('roles.edit', role.id)}
          onDelete={() => openDeleteDialog(role)}
          isDeleting={deletingRole === role.id}
          editTooltip="Editar rol"
          deleteTooltip="Eliminar rol"
          canDelete={!role.is_system}
          showDelete={!role.is_system}
        />
      )
    }
  ];

  // Stats para mostrar en el header
  const stats = roleStats ? [
    {
      title: 'roles',
      value: roleStats.total,
      icon: <Shield className="h-3 w-3 text-primary" />
    },
    {
      title: 'sistema',
      value: roleStats.system,
      icon: <Shield className="h-3 w-3 text-blue-600" />
    },
    {
      title: 'personalizados',
      value: roleStats.custom,
      icon: <UserCheck className="h-3 w-3 text-green-600" />
    }
  ] : undefined;

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Roles" />

      <DataTable
        title="Roles del Sistema"
        description="Administra los roles y permisos de los usuarios."
        data={roles}
        columns={columns}
        stats={stats}
        filters={filters}
        createUrl={route('roles.create')}
        createLabel="Crear Rol"
        searchPlaceholder="Buscar roles..."
        loadingSkeleton={RolesSkeleton}
        renderMobileCard={(role) => (
          <RoleMobileCard
            role={role}
            onDelete={openDeleteDialog}
            isDeleting={deletingRole === role.id}
          />
        )}
        routeName="/roles"
        breakpoint="md"
      />

      {/* Modal para mostrar usuarios del rol */}
      <Dialog open={showUsersModal} onOpenChange={closeUsersModal}>
        <DialogTrigger asChild>
          <div style={{ display: 'none' }} />
        </DialogTrigger>
        <DialogContent className="max-w-2xl">
          <DialogHeader>
            <DialogTitle>
              Usuarios con rol "{selectedRole?.name}"
            </DialogTitle>
          </DialogHeader>
          <ScrollArea className="max-h-96">
            <div className="space-y-3">
              {selectedRole?.users?.map((user) => (
                <div key={user.id} className="flex items-center justify-between p-3 rounded-lg border">
                  <div>
                    <div className="font-medium">{user.name}</div>
                    <div className="text-sm text-muted-foreground">{user.email}</div>
                  </div>
                  <div className="flex flex-wrap gap-1">
                    {user.roles.map((role) => (
                      <Badge key={role.id} variant="outline" className="text-xs">
                        {role.name}
                      </Badge>
                    ))}
                  </div>
                </div>
              ))}
              {(!selectedRole?.users || selectedRole.users.length === 0) && (
                <div className="text-center text-muted-foreground py-8">
                  No hay usuarios con este rol
                </div>
              )}
            </div>
          </ScrollArea>
        </DialogContent>
      </Dialog>

      {/* Modal de confirmación de eliminación */}
      <DeleteConfirmationDialog
        isOpen={showDeleteDialog}
        onClose={closeDeleteDialog}
        onConfirm={handleDeleteRole}
        isDeleting={deletingRole !== null}
        entityName={roleToDelete?.name || ''}
        entityType="rol"
        customMessage={
          roleToDelete && roleToDelete.users_count > 0 ?
          `¿Estás seguro de que quieres eliminar el rol "${roleToDelete.name}"? Este rol está asignado a ${roleToDelete.users_count} usuario${roleToDelete.users_count !== 1 ? 's' : ''} y será removido de ${roleToDelete.users_count === 1 ? 'ese usuario' : 'esos usuarios'}. Esta acción no se puede deshacer.` :
          undefined
        }
      />
    </AppLayout>
  );
}