import { router } from '@inertiajs/react';
import { useCallback, useState } from 'react';

export interface TableFiltersConfig<T = Record<string, unknown>> {
    initialFilters: T;
    endpoint: string;
    defaultPerPage?: number;
    preserveState?: boolean;
    replace?: boolean;
}

export interface TableFiltersState<T = Record<string, unknown>> {
    filters: T;
    search: string;
    perPage: number;
    isLoading: boolean;
    isRefreshing: boolean;
}

export interface TableFiltersActions<T = Record<string, unknown>> {
    setFilters: (filters: Partial<T>) => void;
    setSearch: (search: string) => void;
    setPerPage: (perPage: number) => void;
    applyFilters: () => void;
    clearFilters: () => void;
    refresh: () => void;
    updateFilter: <K extends keyof T>(key: K, value: T[K]) => void;
}

/**
 * Custom hook for managing table filters, search, and pagination
 * Provides consistent state management and API calls across different tables
 */
export function useTableFilters<T extends Record<string, unknown> = Record<string, unknown>>(
    config: TableFiltersConfig<T>
): [TableFiltersState<T>, TableFiltersActions<T>] {
    const {
        initialFilters,
        endpoint,
        defaultPerPage = 10,
        preserveState = true,
        replace = true,
    } = config;

    // State management
    const [filters, setFiltersState] = useState<T>(initialFilters);
    const [search, setSearchState] = useState<string>('');
    const [perPage, setPerPageState] = useState<number>(defaultPerPage);
    const [isLoading, setIsLoading] = useState<boolean>(false);
    const [isRefreshing, setIsRefreshing] = useState<boolean>(false);

    // Build filter payload for API request
    const buildFilterPayload = useCallback((currentFilters: T, currentSearch: string, currentPerPage: number) => {
        const payload: Record<string, unknown> = {
            per_page: currentPerPage,
        };

        // Add search if not empty
        if (currentSearch.trim()) {
            payload.search = currentSearch.trim();
        }

        // Add other filters, removing empty/undefined values
        Object.entries(currentFilters).forEach(([key, value]) => {
            if (value !== undefined && value !== null && value !== '') {
                // Handle arrays - join them or check if not empty
                if (Array.isArray(value)) {
                    if (value.length > 0) {
                        payload[key] = value.join(',');
                    }
                } else {
                    payload[key] = value;
                }
            }
        });

        return payload;
    }, []);

    // Apply filters to the server
    const applyFilters = useCallback(() => {
        setIsLoading(true);
        const payload = buildFilterPayload(filters, search, perPage);

        router.post(endpoint, payload, {
            preserveState,
            replace,
            onFinish: () => setIsLoading(false),
        });
    }, [filters, search, perPage, endpoint, preserveState, replace, buildFilterPayload]);

    // Clear all filters and apply immediately
    const clearFilters = useCallback(() => {
        setSearchState('');
        setPerPageState(defaultPerPage);
        setFiltersState(initialFilters);

        setIsLoading(true);
        const payload = buildFilterPayload(initialFilters, '', defaultPerPage);

        router.post(endpoint, payload, {
            preserveState,
            replace,
            onFinish: () => setIsLoading(false),
        });
    }, [initialFilters, defaultPerPage, endpoint, preserveState, replace, buildFilterPayload]);

    // Refresh current filters
    const refresh = useCallback(() => {
        setIsRefreshing(true);
        const payload = buildFilterPayload(filters, search, perPage);

        router.post(endpoint, payload, {
            preserveState,
            replace,
            onFinish: () => setIsRefreshing(false),
        });
    }, [filters, search, perPage, endpoint, preserveState, replace, buildFilterPayload]);

    // Actions
    const setFilters = useCallback((newFilters: Partial<T>) => {
        setFiltersState(prev => ({ ...prev, ...newFilters }));
    }, []);

    const setSearch = useCallback((newSearch: string) => {
        setSearchState(newSearch);
    }, []);

    const setPerPage = useCallback((newPerPage: number) => {
        setPerPageState(newPerPage);
    }, []);

    const updateFilter = useCallback(<K extends keyof T>(key: K, value: T[K]) => {
        setFiltersState(prev => ({ ...prev, [key]: value }));
    }, []);

    const state: TableFiltersState<T> = {
        filters,
        search,
        perPage,
        isLoading,
        isRefreshing,
    };

    const actions: TableFiltersActions<T> = {
        setFilters,
        setSearch,
        setPerPage,
        applyFilters,
        clearFilters,
        refresh,
        updateFilter,
    };

    return [state, actions];
}

/**
 * Specialized hook for activity filters with type safety
 */
export interface ActivityFilters {
    event_types: string[];
    user_ids: string[];
    dateRange?: {
        from: Date;
        to: Date;
    };
    start_date?: string;
    end_date?: string;
}

export function useActivityFilters(initialPerPage: number = 10) {
    return useTableFilters<ActivityFilters>({
        initialFilters: {
            event_types: [],
            user_ids: [],
            dateRange: undefined,
        },
        endpoint: '/activity',
        defaultPerPage: initialPerPage,
    });
}

/**
 * Specialized hook for user management filters
 */
export interface UserFilters {
    roles: string[];
    status: string[];
    sort_field?: string;
    sort_direction?: 'asc' | 'desc';
}

export function useUserFilters(initialPerPage: number = 10) {
    return useTableFilters<UserFilters>({
        initialFilters: {
            roles: [],
            status: [],
        },
        endpoint: '/users',
        defaultPerPage: initialPerPage,
    });
}

/**
 * Specialized hook for customer filters
 */
export interface CustomerFilters {
    customer_types: string[];
    status: string[];
    sort_field?: string;
    sort_direction?: 'asc' | 'desc';
}

export function useCustomerFilters(initialPerPage: number = 10) {
    return useTableFilters<CustomerFilters>({
        initialFilters: {
            customer_types: [],
            status: [],
        },
        endpoint: '/customers',
        defaultPerPage: initialPerPage,
    });
}