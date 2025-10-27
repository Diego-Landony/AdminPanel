import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { AlertCircle, Database, Inbox, Search, ShieldAlert, XCircle } from 'lucide-react';
import React from 'react';

/**
 * Variantes predefinidas de empty state
 */
export type EmptyStateVariant = 'no-data' | 'no-results' | 'error' | 'no-access' | 'empty-inbox' | 'custom';

/**
 * Configuración de iconos por variante
 */
const VARIANT_ICONS: Record<Exclude<EmptyStateVariant, 'custom'>, React.ReactNode> = {
    'no-data': <Database className="h-12 w-12 text-muted-foreground" />,
    'no-results': <Search className="h-12 w-12 text-muted-foreground" />,
    error: <AlertCircle className="h-12 w-12 text-destructive" />,
    'no-access': <ShieldAlert className="text-warning h-12 w-12" />,
    'empty-inbox': <Inbox className="h-12 w-12 text-muted-foreground" />,
};

/**
 * Configuración de títulos por defecto por variante
 */
const VARIANT_TITLES: Record<Exclude<EmptyStateVariant, 'custom'>, string> = {
    'no-data': 'No hay datos',
    'no-results': 'No se encontraron resultados',
    error: 'Ocurrió un error',
    'no-access': 'Sin acceso',
    'empty-inbox': 'Todo limpio',
};

/**
 * Configuración de descripciones por defecto por variante
 */
const VARIANT_DESCRIPTIONS: Record<Exclude<EmptyStateVariant, 'custom'>, string> = {
    'no-data': 'No hay información disponible en este momento',
    'no-results': 'Intenta ajustar tus filtros o criterios de búsqueda',
    error: 'Algo salió mal al cargar la información',
    'no-access': 'No tienes permisos para ver este contenido',
    'empty-inbox': 'No hay elementos pendientes',
};

/**
 * Props del componente EmptyState
 */
export interface EmptyStateProps {
    /** Variante predefinida del estado vacío */
    variant?: EmptyStateVariant;
    /** Icono personalizado (reemplaza el icono de la variante) */
    icon?: React.ReactNode;
    /** Título del estado vacío */
    title?: string;
    /** Descripción del estado vacío */
    description?: string;
    /** Acción primaria (botón) */
    action?: {
        label: string;
        onClick: () => void;
        icon?: React.ReactNode;
    };
    /** Acción secundaria (link o botón) */
    secondaryAction?: {
        label: string;
        onClick: () => void;
    };
    /** Mostrar como card o solo contenido */
    asCard?: boolean;
    /** Altura mínima del contenedor */
    minHeight?: string;
    /** Clase CSS adicional */
    className?: string;
}

/**
 * Componente EmptyState
 *
 * Muestra un estado vacío con icono, título, descripción y acciones opcionales
 *
 * @example
 * ```tsx
 * // Estado sin datos con acción
 * <EmptyState
 *   variant="no-data"
 *   title="No hay usuarios"
 *   description="Comienza creando tu primer usuario"
 *   action={{
 *     label: "Crear usuario",
 *     onClick: () => router.visit('/users/create'),
 *     icon: <Plus className="h-4 w-4" />
 *   }}
 * />
 *
 * // Estado de búsqueda sin resultados
 * <EmptyState
 *   variant="no-results"
 *   description="No encontramos nada con esos criterios"
 *   secondaryAction={{
 *     label: "Limpiar filtros",
 *     onClick: clearFilters
 *   }}
 * />
 *
 * // Estado personalizado
 * <EmptyState
 *   variant="custom"
 *   icon={<FileQuestion className="h-12 w-12" />}
 *   title="Archivo no encontrado"
 *   description="El archivo que buscas no existe"
 * />
 * ```
 */
export function EmptyState({
    variant = 'no-data',
    icon,
    title,
    description,
    action,
    secondaryAction,
    asCard = false,
    minHeight = '400px',
    className = '',
}: EmptyStateProps) {
    // Obtener valores por defecto según variante
    const defaultIcon = variant !== 'custom' ? VARIANT_ICONS[variant] : <XCircle className="h-12 w-12 text-muted-foreground" />;
    const defaultTitle = variant !== 'custom' ? VARIANT_TITLES[variant] : 'Sin datos';
    const defaultDescription = variant !== 'custom' ? VARIANT_DESCRIPTIONS[variant] : '';

    const finalIcon = icon || defaultIcon;
    const finalTitle = title || defaultTitle;
    const finalDescription = description || defaultDescription;

    const content = (
        <div className={`flex flex-col items-center justify-center text-center ${className}`} style={{ minHeight }}>
            {/* Icono */}
            <div className="mb-4 rounded-full bg-muted/50 p-4">{finalIcon}</div>

            {/* Título */}
            <h3 className="mb-2 text-lg font-semibold text-foreground">{finalTitle}</h3>

            {/* Descripción */}
            {finalDescription && <p className="mb-6 max-w-md text-sm text-muted-foreground">{finalDescription}</p>}

            {/* Acciones */}
            {(action || secondaryAction) && (
                <div className="flex flex-col gap-2 sm:flex-row">
                    {action && (
                        <Button onClick={action.onClick} variant="default" className="gap-2">
                            {action.icon}
                            {action.label}
                        </Button>
                    )}

                    {secondaryAction && (
                        <Button onClick={secondaryAction.onClick} variant="outline">
                            {secondaryAction.label}
                        </Button>
                    )}
                </div>
            )}
        </div>
    );

    if (asCard) {
        return (
            <Card>
                <CardContent className="p-6">{content}</CardContent>
            </Card>
        );
    }

    return content;
}

/**
 * EmptyState específico para tablas
 */
export function TableEmptyState({
    hasFilters = false,
    onClearFilters,
    onCreateNew,
    createLabel = 'Crear nuevo',
}: {
    hasFilters?: boolean;
    onClearFilters?: () => void;
    onCreateNew?: () => void;
    createLabel?: string;
}) {
    if (hasFilters) {
        return (
            <EmptyState
                variant="no-results"
                title="No se encontraron resultados"
                description="Intenta ajustar o limpiar los filtros de búsqueda"
                secondaryAction={
                    onClearFilters
                        ? {
                              label: 'Limpiar filtros',
                              onClick: onClearFilters,
                          }
                        : undefined
                }
                minHeight="300px"
            />
        );
    }

    return (
        <EmptyState
            variant="no-data"
            title="No hay elementos"
            description="Comienza creando tu primer elemento"
            action={
                onCreateNew
                    ? {
                          label: createLabel,
                          onClick: onCreateNew,
                      }
                    : undefined
            }
            minHeight="300px"
        />
    );
}

/**
 * EmptyState para errores con retry
 */
export function ErrorEmptyState({
    title = 'Error al cargar',
    description = 'Ocurrió un error al cargar la información',
    onRetry,
}: {
    title?: string;
    description?: string;
    onRetry?: () => void;
}) {
    return (
        <EmptyState
            variant="error"
            title={title}
            description={description}
            action={
                onRetry
                    ? {
                          label: 'Reintentar',
                          onClick: onRetry,
                      }
                    : undefined
            }
            minHeight="300px"
        />
    );
}
