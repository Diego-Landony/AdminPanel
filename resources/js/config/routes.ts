import { BreadcrumbItem } from '@/types';

/**
 * Configuración de rutas para breadcrumbs automáticos
 */
export interface RouteConfig {
    /** Título que se mostrará en el breadcrumb */
    title: string;
    /** Ruta padre (opcional) para crear jerarquía */
    parent?: string;
    /** Si esta ruta debe incluir el breadcrumb del padre */
    includeParent?: boolean;
    /** Descripción adicional para páginas especiales */
    description?: string;
}

/**
 * Configuración central de todas las rutas de la aplicación
 * Esto permite mantener títulos y jerarquías en un solo lugar
 */
export const routeConfig: Record<string, RouteConfig> = {
    // Página principal
    '/home': {
        title: 'Home',
    },

    // === GESTIÓN DE USUARIOS ===
    '/users': {
        title: 'Usuarios',
        parent: '/home',
        includeParent: false,
    },
    '/users/create': {
        title: 'Crear Usuario',
        parent: '/users',
        includeParent: true,
    },
    '/users/edit': {
        title: 'Editar Usuario',
        parent: '/users',
        includeParent: true,
    },

    // === GESTIÓN DE ROLES ===
    '/roles': {
        title: 'Roles',
        parent: '/home',
        includeParent: false,
    },
    '/roles/create': {
        title: 'Crear Rol',
        parent: '/roles',
        includeParent: true,
    },
    '/roles/edit': {
        title: 'Editar Rol',
        parent: '/roles',
        includeParent: true,
    },

    // === GESTIÓN DE CLIENTES ===
    '/customers': {
        title: 'Clientes',
        parent: '/home',
        includeParent: false,
    },
    '/customers/create': {
        title: 'Crear Cliente',
        parent: '/customers',
        includeParent: true,
    },
    '/customers/edit': {
        title: 'Editar Cliente',
        parent: '/customers',
        includeParent: true,
    },

    // === TIPOS DE CLIENTE ===
    '/customer-types': {
        title: 'Tipos de Cliente',
        parent: '/customers',
        includeParent: true,
    },
    '/customer-types/create': {
        title: 'Crear Tipo de Cliente',
        parent: '/customer-types',
        includeParent: true,
    },
    '/customer-types/edit': {
        title: 'Editar Tipo de Cliente',
        parent: '/customer-types',
        includeParent: true,
    },

    // === GESTIÓN DE RESTAURANTES ===
    '/restaurants': {
        title: 'Restaurantes',
        parent: '/home',
        includeParent: false,
    },
    '/restaurants/create': {
        title: 'Crear Restaurante',
        parent: '/restaurants',
        includeParent: true,
    },
    '/restaurants/edit': {
        title: 'Editar Restaurante',
        parent: '/restaurants',
        includeParent: true,
    },

    // === ACTIVIDAD ===
    '/activity': {
        title: 'Actividad',
        parent: '/home',
        includeParent: false,
    },
};

/**
 * Tipos especiales de páginas que se detectan automáticamente
 */
export const pageTypePatterns = {
    create: /\/create$/,
    edit: /\/(\d+)\/edit$/,
    show: /\/(\d+)$/,
};

/**
 * Títulos por defecto para tipos de páginas especiales
 */
export const defaultPageTitles = {
    create: 'Crear',
    edit: 'Editar',
    show: 'Ver',
};

/**
 * Normaliza una ruta para asegurar que empiece con / y elimina query parameters
 */
function normalizePath(path: string): string {
    // Eliminar query parameters y hash
    const cleanPath = path.split('?')[0].split('#')[0];
    return cleanPath.startsWith('/') ? cleanPath : `/${cleanPath}`;
}

/**
 * Genera breadcrumbs automáticamente basándose en la configuración de rutas
 */
export function generateBreadcrumbs(currentPath: string): BreadcrumbItem[] {
    const breadcrumbs: BreadcrumbItem[] = [];
    const normalizedPath = normalizePath(currentPath);

    // Función recursiva para construir la jerarquía
    function buildHierarchy(path: string): void {
        const config = routeConfig[path];

        if (!config) return;

        // Si tiene padre y debe incluirlo, primero agregamos el padre
        if (config.parent && config.includeParent) {
            buildHierarchy(config.parent);
        }

        // Agregar el breadcrumb actual
        breadcrumbs.push({
            title: config.title,
            href: path,
        });
    }

    // Intentar encontrar configuración exacta
    let config = routeConfig[normalizedPath];

    // Si no se encuentra configuración exacta, intentar detectar patrones
    if (!config) {
        // Detectar páginas create/edit/show
        const pathSegments = normalizedPath.split('/').filter(Boolean);

        if (pathSegments.length >= 2) {
            const lastSegment = pathSegments[pathSegments.length - 1];
            const basePath = '/' + pathSegments.slice(0, -1).join('/');
            const baseConfig = routeConfig[basePath];

            if (baseConfig) {
                if (lastSegment === 'create') {
                    config = {
                        title: `Crear ${baseConfig.title.slice(0, -1)}`,
                        parent: basePath,
                        includeParent: true,
                    };
                } else if (pageTypePatterns.edit.test(normalizedPath)) {
                    config = {
                        title: `Editar ${baseConfig.title.slice(0, -1)}`,
                        parent: basePath,
                        includeParent: true,
                    };
                } else if (/^\d+$/.test(lastSegment)) {
                    config = {
                        title: `Ver ${baseConfig.title.slice(0, -1)}`,
                        parent: basePath,
                        includeParent: true,
                    };
                }
            }
        }
    }

    // Construir jerarquía
    if (config) {
        if (config.parent && config.includeParent) {
            buildHierarchy(config.parent);
        }

        breadcrumbs.push({
            title: config.title,
            href: normalizedPath,
        });
    } else {
        // Fallback: generar breadcrumb básico basándose en la URL
        const pathSegments = normalizedPath.split('/').filter(Boolean);
        const title = pathSegments[pathSegments.length - 1]?.replace(/-/g, ' ')?.replace(/\b\w/g, (l) => l.toUpperCase()) || 'Página';

        breadcrumbs.push({
            title,
            href: normalizedPath,
        });
    }

    return breadcrumbs;
}

/**
 * Obtiene el título de una ruta específica
 */
export function getRouteTitle(path: string): string {
    const config = routeConfig[path];
    if (config) {
        return config.title;
    }

    // Fallback
    const pathSegments = path.split('/').filter(Boolean);
    return pathSegments[pathSegments.length - 1]?.replace(/-/g, ' ')?.replace(/\b\w/g, (l) => l.toUpperCase()) || 'Página';
}
