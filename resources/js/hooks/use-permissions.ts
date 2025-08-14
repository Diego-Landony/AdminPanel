import { usePage } from '@inertiajs/react';

/**
 * Hook para verificar permisos del usuario
 * 
 * Sistema dinámico que detecta automáticamente páginas y permisos
 */
export function usePermissions() {
    const { props } = usePage();
    const user = (props as any).auth?.user;

    /**
     * Verifica si el usuario tiene un permiso específico
     */
    const hasPermission = (permission: string): boolean => {
        // Si no hay usuario, no tiene permisos
        if (!user) return false;

        // Si no tiene roles, solo puede acceder al dashboard
        if (!user.roles || user.roles.length === 0) {
            return permission === 'dashboard.view';
        }

        // Verificar si alguno de sus roles tiene el permiso
        return user.roles.some((role: any) => 
            role.permissions && role.permissions.includes(permission)
        );
    };

    /**
     * Verifica si el usuario tiene un rol específico
     */
    const hasRole = (roleName: string): boolean => {
        if (!user || !user.roles) return false;
        return user.roles.some((role: any) => role.name === roleName);
    };

    /**
     * Verifica si el usuario es administrador
     */
    const isAdmin = (): boolean => {
        return hasRole('admin');
    };

    /**
     * Obtiene todos los permisos del usuario
     */
    const getAllPermissions = (): string[] => {
        if (!user || !user.roles) return ['dashboard.view'];
        
        const permissions = new Set<string>();
        permissions.add('dashboard.view'); // Siempre incluir dashboard
        
        user.roles.forEach((role: any) => {
            if (role.permissions) {
                role.permissions.forEach((permission: string) => {
                    permissions.add(permission);
                });
            }
        });
        
        return Array.from(permissions);
    };

    /**
     * Verifica si el usuario tiene algún permiso de un grupo específico
     */
    const hasAnyPermissionInGroup = (group: string): boolean => {
        const permissions = getAllPermissions();
        return permissions.some(permission => permission.startsWith(`${group}.`));
    };

    /**
     * Obtiene los permisos agrupados por página/funcionalidad
     */
    const getGroupedPermissions = (): Record<string, string[]> => {
        const permissions = getAllPermissions();
        const grouped: Record<string, string[]> = {};
        
        permissions.forEach(permission => {
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
        user
    };
}

