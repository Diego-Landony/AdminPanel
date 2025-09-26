import { generateBreadcrumbs } from '@/config/routes';
import { type BreadcrumbItem } from '@/types';
import { usePage } from '@inertiajs/react';
import { useMemo } from 'react';

/**
 * Opciones para el hook useBreadcrumbs
 */
interface UseBreadcrumbsOptions {
    /** Breadcrumbs manuales que reemplazarán los automáticos */
    override?: BreadcrumbItem[];
    /** Breadcrumbs adicionales que se añadirán al final */
    append?: BreadcrumbItem[];
    /** Breadcrumbs adicionales que se añadirán al inicio */
    prepend?: BreadcrumbItem[];
    /** Si se deben generar breadcrumbs automáticamente cuando no hay override */
    autoGenerate?: boolean;
}

/**
 * Hook para generar breadcrumbs automáticamente basándose en la ruta actual
 *
 * @param options - Opciones para personalizar los breadcrumbs
 * @returns Array de breadcrumbs para mostrar
 *
 * @example
 * // Uso básico - genera breadcrumbs automáticamente
 * const breadcrumbs = useBreadcrumbs();
 *
 * @example
 * // Con override manual
 * const breadcrumbs = useBreadcrumbs({
 *   override: [{ title: 'Custom', href: '/custom' }]
 * });
 *
 * @example
 * // Agregando breadcrumbs adicionales
 * const breadcrumbs = useBreadcrumbs({
 *   append: [{ title: 'Detalles', href: '/details' }]
 * });
 */
export function useBreadcrumbs(options: UseBreadcrumbsOptions = {}): BreadcrumbItem[] {
    const { url } = usePage();
    const { override, append = [], prepend = [], autoGenerate = true } = options;

    const breadcrumbs = useMemo(() => {
        let result: BreadcrumbItem[] = [];

        // Si hay override, usarlo en lugar de generar automáticamente
        if (override) {
            result = [...override];
        } else if (autoGenerate) {
            // Generar automáticamente basándose en la URL actual
            result = generateBreadcrumbs(url);
        }

        // Agregar breadcrumbs al inicio
        if (prepend.length > 0) {
            result = [...prepend, ...result];
        }

        // Agregar breadcrumbs al final
        if (append.length > 0) {
            result = [...result, ...append];
        }

        return result;
    }, [url, override, append, prepend, autoGenerate]);

    return breadcrumbs;
}

/**
 * Hook simplificado para casos donde solo necesitas breadcrumbs manuales
 *
 * @param breadcrumbs - Array de breadcrumbs manuales
 * @returns Array de breadcrumbs
 *
 * @example
 * const breadcrumbs = useManualBreadcrumbs([
 *   { title: 'Inicio', href: '/home' },
 *   { title: 'Usuarios', href: '/users' }
 * ]);
 */
export function useManualBreadcrumbs(breadcrumbs: BreadcrumbItem[]): BreadcrumbItem[] {
    return useBreadcrumbs({
        override: breadcrumbs,
        autoGenerate: false,
    });
}

/**
 * Hook para generar breadcrumbs automáticos con un breadcrumb adicional al final
 * Útil para páginas de detalle o acciones específicas
 *
 * @param additionalBreadcrumb - Breadcrumb adicional para agregar al final
 * @returns Array de breadcrumbs
 *
 * @example
 * // En una página de edición
 * const breadcrumbs = useBreadcrumbsWithAppend({
 *   title: 'Editar Usuario #123',
 *   href: '/users/123/edit'
 * });
 */
export function useBreadcrumbsWithAppend(additionalBreadcrumb: BreadcrumbItem): BreadcrumbItem[] {
    return useBreadcrumbs({
        append: [additionalBreadcrumb],
    });
}

/**
 * Hook para obtener solo el título de la página actual basándose en la ruta
 *
 * @returns Título de la página actual
 *
 * @example
 * const pageTitle = usePageTitle(); // "Usuarios", "Roles", etc.
 */
export function usePageTitle(): string {
    const { url } = usePage();

    const breadcrumbs = useMemo(() => {
        return generateBreadcrumbs(url);
    }, [url]);

    // Devolver el título del último breadcrumb (página actual)
    return breadcrumbs.length > 0 ? breadcrumbs[breadcrumbs.length - 1].title : 'Página';
}
