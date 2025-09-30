import { useCallback, useState } from 'react';

/**
 * Configuración del hook useBulkActions
 */
export interface BulkActionsConfig<T = unknown> {
    /** Items disponibles para selección */
    items: T[];
    /** Función para obtener el ID único de cada item */
    getItemId: (item: T) => string | number;
    /** Callback cuando la selección cambia */
    onSelectionChange?: (selectedIds: Set<string | number>) => void;
}

/**
 * Estado de las acciones bulk
 */
export interface BulkActionsState<T = unknown> {
    /** Set de IDs seleccionados */
    selectedIds: Set<string | number>;
    /** Array de items seleccionados */
    selectedItems: T[];
    /** Número de items seleccionados */
    selectedCount: number;
    /** Indica si todos los items están seleccionados */
    allSelected: boolean;
    /** Indica si algunos items están seleccionados */
    someSelected: boolean;
    /** Indica si ningún item está seleccionado */
    noneSelected: boolean;
}

/**
 * Acciones bulk disponibles
 */
export interface BulkActionsActions {
    /** Seleccionar un item */
    selectItem: (id: string | number) => void;
    /** Deseleccionar un item */
    deselectItem: (id: string | number) => void;
    /** Toggle selección de un item */
    toggleItem: (id: string | number) => void;
    /** Seleccionar todos los items */
    selectAll: () => void;
    /** Deseleccionar todos los items */
    deselectAll: () => void;
    /** Toggle selección de todos */
    toggleAll: () => void;
    /** Verificar si un item está seleccionado */
    isSelected: (id: string | number) => boolean;
    /** Seleccionar múltiples items */
    selectMultiple: (ids: (string | number)[]) => void;
    /** Deseleccionar múltiples items */
    deselectMultiple: (ids: (string | number)[]) => void;
}

/**
 * Hook para manejar selección múltiple de items en tablas/listas
 *
 * Características:
 * - Selección/deselección individual
 * - Seleccionar/deseleccionar todo
 * - Estado de selección parcial
 * - Obtener items seleccionados
 *
 * @example
 * ```tsx
 * const [state, actions] = useBulkActions({
 *   items: users,
 *   getItemId: (user) => user.id,
 * });
 *
 * // En el header
 * <Checkbox
 *   checked={state.allSelected}
 *   indeterminate={state.someSelected && !state.allSelected}
 *   onChange={actions.toggleAll}
 * />
 *
 * // En cada fila
 * {users.map(user => (
 *   <Checkbox
 *     checked={actions.isSelected(user.id)}
 *     onChange={() => actions.toggleItem(user.id)}
 *   />
 * ))}
 *
 * // Acciones bulk
 * {state.selectedCount > 0 && (
 *   <BulkActionsBar
 *     count={state.selectedCount}
 *     onDelete={() => deleteItems(state.selectedItems)}
 *     onCancel={actions.deselectAll}
 *   />
 * )}
 * ```
 */
export function useBulkActions<T = unknown>(
    config: BulkActionsConfig<T>
): [BulkActionsState<T>, BulkActionsActions] {
    const { items, getItemId, onSelectionChange } = config;

    const [selectedIds, setSelectedIds] = useState<Set<string | number>>(new Set());

    // Notificar cambios
    const notifyChange = useCallback((newSelectedIds: Set<string | number>) => {
        onSelectionChange?.(newSelectedIds);
    }, [onSelectionChange]);

    // Seleccionar un item
    const selectItem = useCallback((id: string | number) => {
        setSelectedIds(prev => {
            const newSet = new Set(prev);
            newSet.add(id);
            notifyChange(newSet);
            return newSet;
        });
    }, [notifyChange]);

    // Deseleccionar un item
    const deselectItem = useCallback((id: string | number) => {
        setSelectedIds(prev => {
            const newSet = new Set(prev);
            newSet.delete(id);
            notifyChange(newSet);
            return newSet;
        });
    }, [notifyChange]);

    // Toggle selección de un item
    const toggleItem = useCallback((id: string | number) => {
        setSelectedIds(prev => {
            const newSet = new Set(prev);
            if (newSet.has(id)) {
                newSet.delete(id);
            } else {
                newSet.add(id);
            }
            notifyChange(newSet);
            return newSet;
        });
    }, [notifyChange]);

    // Seleccionar todos los items
    const selectAll = useCallback(() => {
        const allIds = new Set(items.map(getItemId));
        setSelectedIds(allIds);
        notifyChange(allIds);
    }, [items, getItemId, notifyChange]);

    // Deseleccionar todos
    const deselectAll = useCallback(() => {
        const emptySet = new Set<string | number>();
        setSelectedIds(emptySet);
        notifyChange(emptySet);
    }, [notifyChange]);

    // Toggle selección de todos
    const toggleAll = useCallback(() => {
        if (selectedIds.size === items.length) {
            deselectAll();
        } else {
            selectAll();
        }
    }, [selectedIds.size, items.length, selectAll, deselectAll]);

    // Verificar si un item está seleccionado
    const isSelected = useCallback((id: string | number): boolean => {
        return selectedIds.has(id);
    }, [selectedIds]);

    // Seleccionar múltiples items
    const selectMultiple = useCallback((ids: (string | number)[]) => {
        setSelectedIds(prev => {
            const newSet = new Set(prev);
            ids.forEach(id => newSet.add(id));
            notifyChange(newSet);
            return newSet;
        });
    }, [notifyChange]);

    // Deseleccionar múltiples items
    const deselectMultiple = useCallback((ids: (string | number)[]) => {
        setSelectedIds(prev => {
            const newSet = new Set(prev);
            ids.forEach(id => newSet.delete(id));
            notifyChange(newSet);
            return newSet;
        });
    }, [notifyChange]);

    // Obtener items seleccionados
    const selectedItems = items.filter(item => selectedIds.has(getItemId(item)));

    // Estados derivados
    const selectedCount = selectedIds.size;
    const allSelected = items.length > 0 && selectedCount === items.length;
    const someSelected = selectedCount > 0 && selectedCount < items.length;
    const noneSelected = selectedCount === 0;

    const state: BulkActionsState<T> = {
        selectedIds,
        selectedItems,
        selectedCount,
        allSelected,
        someSelected,
        noneSelected,
    };

    const actions: BulkActionsActions = {
        selectItem,
        deselectItem,
        toggleItem,
        selectAll,
        deselectAll,
        toggleAll,
        isSelected,
        selectMultiple,
        deselectMultiple,
    };

    return [state, actions];
}

/**
 * Helper para obtener mensaje descriptivo de selección
 */
export function getSelectionMessage(selectedCount: number, totalCount: number): string {
    if (selectedCount === 0) return '';
    if (selectedCount === 1) return '1 elemento seleccionado';
    if (selectedCount === totalCount) return `Todos los ${totalCount} elementos seleccionados`;
    return `${selectedCount} de ${totalCount} elementos seleccionados`;
}

/**
 * Helper para obtener IDs seleccionados como array
 */
export function getSelectedIdsArray(selectedIds: Set<string | number>): (string | number)[] {
    return Array.from(selectedIds);
}
