import { DataTableSkeleton } from './DataTableSkeleton';

/**
 * Skeleton específico para la tabla de Combinados
 * 5 columnas: Combinado | Precio | Vigencia | Estado | Acciones
 */
export function CombinadosSkeleton() {
    return <DataTableSkeleton rows={5} columns={5} hasAvatar={true} hasActions={true} />;
}
