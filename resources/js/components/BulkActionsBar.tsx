import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import { Download, Trash2, X } from 'lucide-react';
import React, { useEffect, useState } from 'react';

/**
 * Acción personalizada para el bulk actions bar
 */
export interface BulkAction {
    /** Etiqueta del botón */
    label: string;
    /** Icono del botón */
    icon?: React.ReactNode;
    /** Función a ejecutar */
    onClick: () => void;
    /** Variante del botón */
    variant?: 'default' | 'destructive' | 'outline' | 'secondary' | 'ghost' | 'link';
    /** Deshabilitar el botón */
    disabled?: boolean;
}

/**
 * Props del componente BulkActionsBar
 */
export interface BulkActionsBarProps {
    /** Número de elementos seleccionados */
    selectedCount: number;
    /** Total de elementos disponibles */
    totalCount?: number;
    /** Callback al cancelar selección */
    onCancel: () => void;
    /** Callback al eliminar elementos seleccionados */
    onDelete?: () => void;
    /** Callback al exportar elementos seleccionados */
    onExport?: () => void;
    /** Acciones personalizadas adicionales */
    actions?: BulkAction[];
    /** Posición de la barra */
    position?: 'top' | 'bottom' | 'fixed-bottom';
    /** Mostrar animación de entrada */
    animated?: boolean;
    /** Clase CSS adicional */
    className?: string;
}

/**
 * Componente BulkActionsBar
 *
 * Barra flotante que aparece cuando hay elementos seleccionados
 * Proporciona acciones comunes como eliminar y exportar
 *
 * @example
 * ```tsx
 * const [state, actions] = useBulkActions({
 *   items: users,
 *   getItemId: (user) => user.id,
 * });
 *
 * return (
 *   <>
 *     <DataTable ... />
 *
 *     <BulkActionsBar
 *       selectedCount={state.selectedCount}
 *       totalCount={users.length}
 *       onCancel={actions.deselectAll}
 *       onDelete={handleBulkDelete}
 *       onExport={handleBulkExport}
 *       actions={[
 *         {
 *           label: 'Asignar rol',
 *           icon: <Shield className="h-4 w-4" />,
 *           onClick: handleAssignRole,
 *           variant: 'secondary',
 *         }
 *       ]}
 *     />
 *   </>
 * );
 * ```
 */
export function BulkActionsBar({
    selectedCount,
    totalCount,
    onCancel,
    onDelete,
    onExport,
    actions = [],
    position = 'fixed-bottom',
    animated = true,
    className = '',
}: BulkActionsBarProps) {
    const [isVisible, setIsVisible] = useState(false);

    // Animar entrada/salida
    useEffect(() => {
        if (selectedCount > 0) {
            // Pequeño delay para animación
            const timer = setTimeout(() => setIsVisible(true), 10);
            return () => clearTimeout(timer);
        }

        setIsVisible(false);
        return undefined;
    }, [selectedCount]);

    // No renderizar si no hay selección
    if (selectedCount === 0) {
        return null;
    }

    // Clases de posición
    const positionClasses = {
        top: 'relative mb-4',
        bottom: 'relative mt-4',
        'fixed-bottom': 'fixed bottom-4 left-1/2 -translate-x-1/2 z-50',
    };

    // Clases de animación
    const animationClasses = animated
        ? isVisible
            ? 'opacity-100 translate-y-0 scale-100'
            : 'opacity-0 translate-y-4 scale-95'
        : '';

    const message = totalCount
        ? selectedCount === totalCount
            ? `Todos los ${totalCount} elementos seleccionados`
            : `${selectedCount} de ${totalCount} elementos seleccionados`
        : selectedCount === 1
            ? '1 elemento seleccionado'
            : `${selectedCount} elementos seleccionados`;

    return (
        <div
            className={`${positionClasses[position]} transition-all duration-300 ease-out ${animationClasses} ${className}`}
        >
            <Card className="shadow-lg">
                <div className="flex flex-col gap-4 p-4 sm:flex-row sm:items-center sm:justify-between">
                    {/* Contador y cancelar */}
                    <div className="flex items-center gap-3">
                        <div className="flex h-10 w-10 items-center justify-center rounded-full bg-primary/10 text-primary">
                            <span className="text-sm font-semibold">{selectedCount}</span>
                        </div>

                        <div className="flex-1">
                            <p className="text-sm font-medium text-foreground">
                                {message}
                            </p>
                        </div>

                        <Button
                            variant="ghost"
                            size="sm"
                            onClick={onCancel}
                            className="h-8 w-8 p-0 sm:ml-2"
                        >
                            <X className="h-4 w-4" />
                            <span className="sr-only">Cancelar selección</span>
                        </Button>
                    </div>

                    <Separator className="sm:hidden" />

                    {/* Acciones */}
                    <div className="flex flex-wrap items-center gap-2">
                        {/* Acciones personalizadas */}
                        {actions.map((action, index) => (
                            <Button
                                key={index}
                                variant={action.variant || 'secondary'}
                                size="sm"
                                onClick={action.onClick}
                                disabled={action.disabled}
                                className="gap-2"
                            >
                                {action.icon}
                                {action.label}
                            </Button>
                        ))}

                        {/* Separador si hay acciones personalizadas y acciones por defecto */}
                        {actions.length > 0 && (onExport || onDelete) && (
                            <Separator orientation="vertical" className="h-6" />
                        )}

                        {/* Exportar */}
                        {onExport && (
                            <Button
                                variant="outline"
                                size="sm"
                                onClick={onExport}
                                className="gap-2"
                            >
                                <Download className="h-4 w-4" />
                                <span className="hidden sm:inline">Exportar</span>
                            </Button>
                        )}

                        {/* Eliminar */}
                        {onDelete && (
                            <Button
                                variant="destructive"
                                size="sm"
                                onClick={onDelete}
                                className="gap-2"
                            >
                                <Trash2 className="h-4 w-4" />
                                <span className="hidden sm:inline">Eliminar</span>
                            </Button>
                        )}
                    </div>
                </div>
            </Card>
        </div>
    );
}

/**
 * Variante compacta del BulkActionsBar para espacios reducidos
 */
export function CompactBulkActionsBar({
    selectedCount,
    onCancel,
    onDelete,
    className = '',
}: {
    selectedCount: number;
    onCancel: () => void;
    onDelete?: () => void;
    className?: string;
}) {
    if (selectedCount === 0) {
        return null;
    }

    return (
        <div className={`flex items-center gap-2 rounded-md bg-muted/50 px-3 py-2 ${className}`}>
            <span className="text-sm font-medium text-foreground">
                {selectedCount} seleccionado{selectedCount !== 1 ? 's' : ''}
            </span>

            <div className="ml-auto flex items-center gap-1">
                {onDelete && (
                    <Button
                        variant="ghost"
                        size="sm"
                        onClick={onDelete}
                        className="h-7 gap-1 px-2 text-destructive hover:bg-destructive/10 hover:text-destructive"
                    >
                        <Trash2 className="h-3.5 w-3.5" />
                        <span className="text-xs">Eliminar</span>
                    </Button>
                )}

                <Button
                    variant="ghost"
                    size="sm"
                    onClick={onCancel}
                    className="h-7 w-7 p-0"
                >
                    <X className="h-3.5 w-3.5" />
                </Button>
            </div>
        </div>
    );
}
