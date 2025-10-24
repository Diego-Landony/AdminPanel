import { usePage } from '@inertiajs/react';

interface User {
    is_admin?: boolean;
    permissions?: string[]; // Array plano de permisos
    roles?: string[]; // Array simple de nombres de roles
}

interface PageProps {
    auth?: {
        user?: User;
    };
}

/**
 * Hook para verificar permisos del usuario
 *
 * Optimizado con estructura plana de permisos
 */
export function usePermissions() {
    const { props } = usePage();
    const user = (props as PageProps).auth?.user;

    /**
     * Verifica si el usuario tiene un permiso específico
     * Admin siempre tiene acceso a todo (bypass automático)
     */
    const hasPermission = (permission: string): boolean => {
        // Si no hay usuario, no tiene permisos
        if (!user) return false;

        // Admin siempre tiene todos los permisos (bypass automático)
        if (user.is_admin) return true;

        // Verificar directamente en el array de permisos
        if (!user.permissions || user.permissions.length === 0) {
            return permission === 'dashboard.view';
        }

        // Wildcard para admin
        if (user.permissions.includes('*')) return true;

        return user.permissions.includes(permission);
    };

    /**
     * Verifica si el usuario tiene un rol específico
     */
    const hasRole = (roleName: string): boolean => {
        if (!user || !user.roles) return false;
        return user.roles.includes(roleName);
    };

    /**
     * Verifica si el usuario es administrador
     */
    const isAdmin = (): boolean => {
        return hasRole('admin');
    };

    /**
     * Obtiene todos los permisos del usuario
     * Admin tiene wildcard (*) = todos los permisos
     */
    const getAllPermissions = (): string[] => {
        if (!user) return ['dashboard.view'];

        // Admin tiene wildcard (todos los permisos)
        if (user.is_admin) return ['*'];

        // Retornar directamente el array de permisos
        return user.permissions || ['dashboard.view'];
    };

    /**
     * Verifica si el usuario tiene algún permiso de un grupo específico
     */
    const hasAnyPermissionInGroup = (group: string): boolean => {
        const permissions = getAllPermissions();
        return permissions.some((permission) => permission.startsWith(`${group}.`));
    };

    /**
     * Obtiene los permisos agrupados por página/funcionalidad
     */
    const getGroupedPermissions = (): Record<string, string[]> => {
        const permissions = getAllPermissions();
        const grouped: Record<string, string[]> = {};

        permissions.forEach((permission) => {
            const [group] = permission.split('.');
            if (!grouped[group]) {
                grouped[group] = [];
            }
            grouped[group].push(permission);
        });

        return grouped;
    };

    /**
     * Verifica si el usuario puede realizar una acción específica en una página
     */
    const canPerformAction = (page: string, action: string): boolean => {
        return hasPermission(`${page}.${action}`);
    };

    return {
        hasPermission,
        hasRole,
        isAdmin,
        getAllPermissions,
        hasAnyPermissionInGroup,
        getGroupedPermissions,
        canPerformAction,
        user,
    };
}
