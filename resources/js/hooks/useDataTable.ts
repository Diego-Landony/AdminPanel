import { router } from '@inertiajs/react';
import { useCallback, useEffect, useState } from 'react';

/**
 * Configuración de criterio de ordenamiento
 */
export interface SortCriterion {
    field: string;
    direction: 'asc' | 'desc';
}

/**
 * Configuración del hook useDataTable
 */
export interface DataTableConfig {
    /** URL del endpoint para hacer las peticiones */
    endpoint: string;
    /** Número de items por página por defecto */
    defaultPerPage?: number;
    /** Campo de ordenamiento por defecto */
    defaultSortField?: string;
    /** Dirección de ordenamiento por defecto */
    defaultSortDirection?: 'asc' | 'desc';
    /** Criterios múltiples de ordenamiento por defecto */
    defaultMultipleSortCriteria?: SortCriterion[];
    /** Preservar state en navegación */
    preserveState?: boolean;
    /** Usar replace en lugar de push */
    replace?: boolean;
    /** Sincronizar con URL params */
    syncWithUrl?: boolean;
    /** Debounce delay para búsqueda en ms */
    searchDebounceMs?: number;
}

/**
 * Estado del data table
 */
export interface DataTableState {
    /** Término de búsqueda actual */
    search: string;
    /** Número de items por página */
    perPage: number;
    /** Página actual */
    currentPage: number;
    /** Campo de ordenamiento simple */
    sortField: string | null;
    /** Dirección de ordenamiento simple */
    sortDirection: 'asc' | 'desc';
    /** Criterios múltiples de ordenamiento */
    multipleSortCriteria: SortCriterion[];
    /** Indica si está cargando */
    isLoading: boolean;
    /** Filtros adicionales */
    filters: Record<string, unknown>;
}

/**
 * Acciones del data table
 */
export interface DataTableActions {
    /** Actualizar término de búsqueda */
    setSearch: (search: string) => void;
    /** Actualizar items por página */
    setPerPage: (perPage: number) => void;
    /** Ir a una página específica */
    goToPage: (page: number) => void;
    /** Establecer ordenamiento simple */
    setSort: (field: string, direction?: 'asc' | 'desc') => void;
    /** Toggle dirección de ordenamiento */
    toggleSort: (field: string) => void;
    /** Agregar criterio de ordenamiento múltiple */
    addSortCriterion: (field: string, direction: 'asc' | 'desc') => void;
    /** Remover criterio de ordenamiento múltiple */
    removeSortCriterion: (field: string) => void;
    /** Limpiar todos los ordenamientos múltiples */
    clearMultipleSort: () => void;
    /** Actualizar filtros */
    setFilters: (filters: Record<string, unknown>) => void;
    /** Actualizar un filtro específico */
    updateFilter: (key: string, value: unknown) => void;
    /** Aplicar filtros y búsqueda */
    applyFilters: () => void;
    /** Limpiar todos los filtros */
    clearFilters: () => void;
    /** Refrescar datos */
    refresh: () => void;
    /** Reset a estado inicial */
    reset: () => void;
}

/**
 * Hook personalizado para manejo completo de data tables
 * Incluye paginación, ordenamiento (simple y múltiple), búsqueda y filtros
 *
 * @example
 * ```tsx
 * const [state, actions] = useDataTable({
 *   endpoint: '/users',
 *   defaultPerPage: 10,
 *   defaultSortField: 'created_at',
 *   defaultSortDirection: 'desc',
 *   syncWithUrl: true,
 * });
 *
 * // Usar state
 * console.log(state.search, state.perPage, state.sortField);
 *
 * // Usar actions
 * actions.setSearch('john');
 * actions.setSort('name', 'asc');
 * actions.applyFilters();
 * ```
 */
export function useDataTable(config: DataTableConfig): [DataTableState, DataTableActions] {
    const {
        endpoint,
        defaultPerPage = 10,
        defaultSortField = null,
        defaultSortDirection = 'asc',
        defaultMultipleSortCriteria = [],
        preserveState = true,
        replace = true,
        syncWithUrl = true,
        searchDebounceMs = 300,
    } = config;

    // Initialize state from URL params if syncWithUrl is enabled
    const getInitialState = useCallback((): DataTableState => {
        if (syncWithUrl && typeof window !== 'undefined') {
            const params = new URLSearchParams(window.location.search);

            return {
                search: params.get('search') || '',
                perPage: parseInt(params.get('per_page') || String(defaultPerPage), 10),
                currentPage: parseInt(params.get('page') || '1', 10),
                sortField: params.get('sort_field') || defaultSortField,
                sortDirection: (params.get('sort_direction') as 'asc' | 'desc') || defaultSortDirection,
                multipleSortCriteria: params.get('sort_criteria') ? JSON.parse(params.get('sort_criteria')!) : defaultMultipleSortCriteria,
                isLoading: false,
                filters: {},
            };
        }

        return {
            search: '',
            perPage: defaultPerPage,
            currentPage: 1,
            sortField: defaultSortField,
            sortDirection: defaultSortDirection,
            multipleSortCriteria: defaultMultipleSortCriteria,
            isLoading: false,
            filters: {},
        };
    }, [syncWithUrl, defaultPerPage, defaultSortField, defaultSortDirection, defaultMultipleSortCriteria]);

    const [state, setState] = useState<DataTableState>(getInitialState);
    const [debouncedSearch, setDebouncedSearch] = useState(state.search);

    // Debounce search
    useEffect(() => {
        const handler = setTimeout(() => {
            setDebouncedSearch(state.search);
        }, searchDebounceMs);

        return () => clearTimeout(handler);
    }, [state.search, searchDebounceMs]);

    // Build query params for API request
    const buildQueryParams = useCallback(
        (currentState: DataTableState) => {
            const params: Record<string, string | number> = {
                page: currentState.currentPage,
                per_page: currentState.perPage,
            };

            if (debouncedSearch.trim()) {
                params.search = debouncedSearch.trim();
            }

            // Add sorting
            if (currentState.multipleSortCriteria.length > 0) {
                params.sort_criteria = JSON.stringify(currentState.multipleSortCriteria);
            } else if (currentState.sortField) {
                params.sort_field = currentState.sortField;
                params.sort_direction = currentState.sortDirection;
            }

            // Add filters
            Object.entries(currentState.filters).forEach(([key, value]) => {
                if (value !== undefined && value !== null && value !== '') {
                    params[key] = typeof value === 'string' || typeof value === 'number' ? value : String(value);
                }
            });

            return params;
        },
        [debouncedSearch],
    );

    // Make API request
    const makeRequest = useCallback(
        (newState: DataTableState) => {
            setState((prev) => ({ ...prev, isLoading: true }));
            const params = buildQueryParams(newState);

            router.visit(endpoint, {
                method: 'get',
                data: params,
                preserveState,
                replace,
                onFinish: () => setState((prev) => ({ ...prev, isLoading: false })),
            });
        },
        [endpoint, preserveState, replace, buildQueryParams],
    );

    // Actions
    const setSearch = useCallback((search: string) => {
        setState((prev) => ({ ...prev, search, currentPage: 1 }));
    }, []);

    const setPerPage = useCallback(
        (perPage: number) => {
            const newState = { ...state, perPage, currentPage: 1 };
            setState(newState);
            makeRequest(newState);
        },
        [state, makeRequest],
    );

    const goToPage = useCallback(
        (page: number) => {
            const newState = { ...state, currentPage: page };
            setState(newState);
            makeRequest(newState);
        },
        [state, makeRequest],
    );

    const setSort = useCallback(
        (field: string, direction: 'asc' | 'desc' = 'asc') => {
            const newState = {
                ...state,
                sortField: field,
                sortDirection: direction,
                multipleSortCriteria: [],
                currentPage: 1,
            };
            setState(newState);
            makeRequest(newState);
        },
        [state, makeRequest],
    );

    const toggleSort = useCallback(
        (field: string) => {
            const newDirection = state.sortField === field && state.sortDirection === 'asc' ? 'desc' : 'asc';
            setSort(field, newDirection);
        },
        [state.sortField, state.sortDirection, setSort],
    );

    const addSortCriterion = useCallback(
        (field: string, direction: 'asc' | 'desc') => {
            const existing = state.multipleSortCriteria.find((c) => c.field === field);

            const newCriteria = existing
                ? state.multipleSortCriteria.map((c) => (c.field === field ? { field, direction } : c))
                : [...state.multipleSortCriteria, { field, direction }];

            const newState = {
                ...state,
                multipleSortCriteria: newCriteria,
                sortField: null,
                currentPage: 1,
            };
            setState(newState);
            makeRequest(newState);
        },
        [state, makeRequest],
    );

    const removeSortCriterion = useCallback(
        (field: string) => {
            const newCriteria = state.multipleSortCriteria.filter((c) => c.field !== field);
            const newState = {
                ...state,
                multipleSortCriteria: newCriteria,
                currentPage: 1,
            };
            setState(newState);
            makeRequest(newState);
        },
        [state, makeRequest],
    );

    const clearMultipleSort = useCallback(() => {
        const newState = {
            ...state,
            multipleSortCriteria: [],
            currentPage: 1,
        };
        setState(newState);
        makeRequest(newState);
    }, [state, makeRequest]);

    const setFilters = useCallback((filters: Record<string, unknown>) => {
        setState((prev) => ({ ...prev, filters, currentPage: 1 }));
    }, []);

    const updateFilter = useCallback((key: string, value: unknown) => {
        setState((prev) => ({
            ...prev,
            filters: { ...prev.filters, [key]: value },
            currentPage: 1,
        }));
    }, []);

    const applyFilters = useCallback(() => {
        makeRequest(state);
    }, [state, makeRequest]);

    const clearFilters = useCallback(() => {
        const newState = getInitialState();
        setState(newState);
        makeRequest(newState);
    }, [getInitialState, makeRequest]);

    const refresh = useCallback(() => {
        makeRequest(state);
    }, [state, makeRequest]);

    const reset = useCallback(() => {
        const newState = getInitialState();
        setState(newState);
        makeRequest(newState);
    }, [getInitialState, makeRequest]);

    // Auto-apply when debounced search changes
    useEffect(() => {
        if (debouncedSearch !== state.search) {
            return; // Still debouncing
        }

        const newState = { ...state, currentPage: 1 };
        makeRequest(newState);
    }, [debouncedSearch]); // eslint-disable-line react-hooks/exhaustive-deps

    const actions: DataTableActions = {
        setSearch,
        setPerPage,
        goToPage,
        setSort,
        toggleSort,
        addSortCriterion,
        removeSortCriterion,
        clearMultipleSort,
        setFilters,
        updateFilter,
        applyFilters,
        clearFilters,
        refresh,
        reset,
    };

    return [state, actions];
}
