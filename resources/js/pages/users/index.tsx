import { type BreadcrumbItem } from '@/types';
import { Head, router, Link } from '@inertiajs/react';
import { useState, useCallback } from 'react';
import { toast } from 'sonner';

import AppLayout from '@/layouts/app-layout';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';

import { Shield, Users, Clock, Circle, Trash2 } from 'lucide-react';
import { UsersSkeleton } from '@/components/skeletons';
import { ActionsMenu } from '@/components/ActionsMenu';
import { DataTable } from '@/components/DataTable';
import {
  ResponsiveCard,
  ResponsiveCardHeader,
  ResponsiveCardContent,
  DataField,
  CardActions,
  BadgeGroup
} from '@/components/CardLayout';

/**
 * Breadcrumbs para la navegación de usuarios
 */
const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Usuarios',
    href: '/users',
  },
  {
    title: 'Gestión de usuarios',
    href: '/users',
  },
];

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
 * Obtiene el color del badge según el estado del usuario
 */
const getStatusColor = (status: string): string => {
  switch (status) {
    case 'online':
      return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300 border border-green-200 dark:border-green-700';
    case 'recent':
      return 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300 border border-blue-200 dark:border-blue-700';
    case 'offline':
      return 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300 border border-gray-200 dark:border-gray-600';
    case 'never':
      return 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300 border border-red-200 dark:border-red-700';
    default:
      return 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300 border border-gray-200 dark:border-gray-600';
  }
};

/**
 * Obtiene el texto del estado
 */
const getStatusText = (status: string): string => {
  switch (status) {
    case 'online': return 'En línea';
    case 'recent': return 'Reciente';
    case 'offline': return 'Desconectado';
    case 'never': return 'Nunca conectado';
    default: return 'Desconocido';
  }
};

/**
 * Obtiene el icono del estado
 */
const getStatusIcon = (status: string) => {
  switch (status) {
    case 'online': return <Circle className="w-2 h-2 fill-current" />;
    case 'recent': return <Clock className="w-3 h-3" />;
    case 'offline': return <Circle className="w-2 h-2 fill-muted-foreground" />;
    default: return <Circle className="w-2 h-2" />;
  }
};

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
const UserCell: React.FC<{ user: User }> = ({ user }) => (
  <div className="flex items-center gap-3">
    <div className="w-10 h-10 rounded-full bg-primary/10 flex items-center justify-center flex-shrink-0">
      <Users className="w-5 h-5 text-primary" />
    </div>
    <div className="min-w-0">
      <div className="font-medium text-sm text-foreground">
        {user.name}
      </div>
      <div className="text-sm text-muted-foreground">
        {user.email}
      </div>
    </div>
  </div>
);

/**
 * Componente para renderizar badges de roles
 */
const RoleBadges: React.FC<{ roles: Role[] }> = ({ roles }) => (
  <div className="flex flex-wrap gap-1">
    {roles.length > 0 ? (
      roles.slice(0, 2).map((role) => (
        <Badge
          key={role.id}
          variant={role.is_system ? "secondary" : "default"}
          className="text-xs px-2 py-1"
        >
          {role.name}
        </Badge>
      ))
    ) : (
      <span className="text-xs text-muted-foreground">Sin roles</span>
    )}
    {roles.length > 2 && (
      <Badge variant="outline" className="text-xs px-2 py-1">
        +{roles.length - 2}
      </Badge>
    )}
  </div>
);

/**
 * Componente para renderizar el estado del usuario
 */
const StatusBadge: React.FC<{ status: string }> = ({ status }) => (
  <Badge className={`${getStatusColor(status)} px-3 py-1 text-xs font-medium`}>
    <span className="mr-2">{getStatusIcon(status)}</span>
    {getStatusText(status)}
  </Badge>
);

/**
 * Componente para la mobile card del usuario
 */
const UserMobileCard: React.FC<{ user: User }> = ({ user }) => {
  const [deletingUser, setDeletingUser] = useState<number | null>(null);

  const handleDelete = useCallback(async (userId: number) => {
    try {
      await router.delete(`/users/${userId}`, {
        onSuccess: () => {
          toast.success('Usuario eliminado correctamente');
          setDeletingUser(null);
        },
        onError: () => {
          toast.error('Error al eliminar el usuario');
        }
      });
    } catch (error) {
      toast.error('Error al eliminar el usuario');
    }
  }, []);

  return (
    <ResponsiveCard>
      <ResponsiveCardHeader
        icon={<Users className="w-4 h-4 text-primary" />}
        title={user.name}
        subtitle={user.email}
        badge={{
          children: (
            <>
              <span className="mr-2">{getStatusIcon(user.status)}</span>
              {getStatusText(user.status)}
            </>
          ),
          className: getStatusColor(user.status)
        }}
      />

      <ResponsiveCardContent>
        <DataField
          label="Roles"
          value={
            <BadgeGroup>
              {user.roles.length > 0 ? (
                user.roles.map((role) => (
                  <Badge
                    key={role.id}
                    variant={role.is_system ? "secondary" : "default"}
                    className="text-xs"
                  >
                    {role.name}
                  </Badge>
                ))
              ) : (
                <span className="text-xs text-muted-foreground">Sin roles</span>
              )}
            </BadgeGroup>
          }
        />

        <DataField
          label="Verificación"
          value={
            <Badge variant={user.email_verified_at ? "default" : "destructive"} className="text-xs">
              {user.email_verified_at ? 'Verificado' : 'Sin verificar'}
            </Badge>
          }
        />

        <DataField
          label="Última actividad"
          value={user.last_activity ? formatDate(user.last_activity) : 'Nunca'}
        />

        <DataField
          label="Creado"
          value={formatDate(user.created_at)}
        />
      </ResponsiveCardContent>

      <CardActions>
        <ActionsMenu
          editHref={`/users/${user.id}/edit`}
          onDelete={() => setDeletingUser(user.id)}
          isDeleting={deletingUser === user.id}
          editTitle="Editar usuario"
          deleteTitle="Eliminar usuario"
        />

        <Dialog open={deletingUser === user.id} onOpenChange={(open) => !open && setDeletingUser(null)}>
          <DialogTrigger asChild>
            <div style={{ display: 'none' }} />
          </DialogTrigger>
          <DialogContent>
            <DialogHeader>
              <DialogTitle>Confirmar eliminación</DialogTitle>
              <DialogDescription>
                ¿Estás seguro de que quieres eliminar al usuario "{user.name}"?
                Esta acción no se puede deshacer.
              </DialogDescription>
            </DialogHeader>
            <DialogFooter>
              <Button
                variant="outline"
                onClick={() => setDeletingUser(null)}
              >
                Cancelar
              </Button>
              <Button
                variant="destructive"
                onClick={() => handleDelete(user.id)}
                disabled={deletingUser === user.id}
              >
                {deletingUser === user.id ? (
                  <div className="h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent mr-2" />
                ) : (
                  <Trash2 className="h-4 w-4 mr-2" />
                )}
                Eliminar
              </Button>
            </DialogFooter>
          </DialogContent>
        </Dialog>
      </CardActions>
    </ResponsiveCard>
  );
};

/**
 * Página principal de usuarios
 */
export default function UsersIndex({ users, total_users, verified_users, online_users, filters }: UsersPageProps) {
  const [deletingUser, setDeletingUser] = useState<number | null>(null);

  const handleDelete = useCallback(async (userId: number) => {
    try {
      await router.delete(`/users/${userId}`, {
        onSuccess: () => {
          toast.success('Usuario eliminado correctamente');
          setDeletingUser(null);
        },
        onError: () => {
          toast.error('Error al eliminar el usuario');
        }
      });
    } catch (error) {
      toast.error('Error al eliminar el usuario');
    }
  }, []);

  // Definición de columnas para la tabla
  const columns = [
    {
      key: 'user',
      title: 'Usuario',
      width: 'lg' as const,
      sortable: true,
      render: (user: User) => <UserCell user={user} />
    },
    {
      key: 'roles',
      title: 'Roles',
      width: 'md' as const,
      render: (user: User) => <RoleBadges roles={user.roles} />
    },
    {
      key: 'info',
      title: 'Información',
      width: 'lg' as const,
      render: (user: User) => (
        <div className="space-y-1 text-sm">
          <div className="flex items-center gap-2">
            <span className="text-muted-foreground">Estado:</span>
            <Badge variant={user.email_verified_at ? "default" : "destructive"} className="text-xs">
              {user.email_verified_at ? 'Verificado' : 'Sin verificar'}
            </Badge>
          </div>
          <div className="text-muted-foreground">
            Última actividad: {user.last_activity ? formatDate(user.last_activity) : 'Nunca'}
          </div>
          <div className="text-muted-foreground">
            Creado: {formatDate(user.created_at)}
          </div>
        </div>
      )
    },
    {
      key: 'status',
      title: 'Estado',
      width: 'sm' as const,
      textAlign: 'center' as const,
      sortable: true,
      render: (user: User) => <StatusBadge status={user.status} />
    },
    {
      key: 'actions',
      title: 'Acciones',
      width: 'xs' as const,
      textAlign: 'right' as const,
      render: (user: User) => (
        <>
          <ActionsMenu
            editHref={`/users/${user.id}/edit`}
            onDelete={() => setDeletingUser(user.id)}
            isDeleting={deletingUser === user.id}
            editTitle="Editar usuario"
            deleteTitle="Eliminar usuario"
          />

          <Dialog open={deletingUser === user.id} onOpenChange={(open) => !open && setDeletingUser(null)}>
            <DialogTrigger asChild>
              <div style={{ display: 'none' }} />
            </DialogTrigger>
            <DialogContent>
              <DialogHeader>
                <DialogTitle>Confirmar eliminación</DialogTitle>
                <DialogDescription>
                  ¿Estás seguro de que quieres eliminar al usuario "{user.name}"?
                  Esta acción no se puede deshacer.
                </DialogDescription>
              </DialogHeader>
              <DialogFooter>
                <Button
                  variant="outline"
                  onClick={() => setDeletingUser(null)}
                >
                  Cancelar
                </Button>
                <Button
                  variant="destructive"
                  onClick={() => handleDelete(user.id)}
                  disabled={deletingUser === user.id}
                >
                  {deletingUser === user.id ? (
                    <div className="h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent mr-2" />
                  ) : (
                    <Trash2 className="h-4 w-4 mr-2" />
                  )}
                  Eliminar
                </Button>
              </DialogFooter>
            </DialogContent>
          </Dialog>
        </>
      )
    }
  ];

  // Stats para mostrar en el header
  const stats = [
    {
      title: 'usuarios',
      value: total_users,
      icon: <Users className="h-3 w-3 text-primary" />
    },
    {
      title: 'en línea',
      value: online_users,
      icon: <Clock className="h-3 w-3 text-green-600" />
    },
    {
      title: 'desconectados',
      value: total_users - online_users,
      icon: <Users className="h-3 w-3 text-red-600" />
    }
  ];

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
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
        renderMobileCard={(user) => <UserMobileCard user={user} />}
        routeName="/users"
        breakpoint="md"
      />
    </AppLayout>
  );
}